<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{WorkshopQueue, WorkshopQueueService};

class WorkshopQueueController extends Controller
{
    /**
     * Full queue list for admin (today's jobs).
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        return response()->json($this->buildQueueData($tenantId, includeDelivered: true, includeHidden: true));
    }

    /**
     * Lightweight queue for employee mobile (non-delivered and non-hidden only).
     */
    public function mobileIndex()
    {
        $tenantId = auth()->user()->tenant_id;
        return response()->json($this->buildQueueData($tenantId, includeDelivered: false, includeHidden: false));
    }

    private function buildQueueData(int $tenantId, bool $includeDelivered, bool $includeHidden): array
    {
        $query = WorkshopQueue::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->with(['services' => fn($q) => $q->with('doneByUser:id,name')])
            ->orderBy('id');

        if (!$includeDelivered) {
            $query->where('status', '!=', 'delivered');
        }

        if (!$includeHidden) {
            $query->where('is_hidden_from_workshop', false);
        }

        return $query->get()->values()->map(function ($q, $index) {
            $total = $q->services->count();
            $done  = $q->services->where('is_done', true)->count();
            return [
                'id'             => $q->id,
                'position'       => $index + 1,
                'queue_number'   => $q->queue_number,
                'client_name'    => $q->client_name,
                'client_phone'   => $q->client_phone,
                'status'         => $q->status,
                'notes'          => $q->notes,
                'is_hidden'      => (bool) $q->is_hidden_from_workshop,
                'services'       => $q->services->map(fn($s) => [
                    'id'            => $s->id,
                    'label'         => $s->label,
                    'material_type' => $s->material_type,
                    'material_color'=> $s->material_color,
                    'quantity'      => (float) $s->quantity,
                    'unit'          => $s->unit,
                    'is_done'       => (bool) $s->is_done,
                    'done_at'       => $s->done_at?->format('H:i'),
                    'done_by'       => $s->doneByUser?->name,
                ])->values(),
                'services_total'    => $total,
                'services_done'     => $done,
                'all_done'          => $total > 0 && $done === $total,
                'waiting_since'     => $q->created_at->format('H:i'),
                'waiting_minutes'   => (int) $q->created_at->diffInMinutes(now()),
                'started_at'        => $q->started_at?->format('H:i'),
                'done_at_time'      => $q->done_at?->format('H:i'),
                'delivered_at_time' => $q->delivered_at?->format('H:i'),
            ];
        })->toArray();
    }

    /**
     * Add a new client to the queue.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_name'  => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:1000',
            'services'     => 'required|array|min:1',
            'services.*.label'          => 'required|string|max:100',
            'services.*.material_type'  => 'nullable|string|max:100',
            'services.*.material_color' => 'nullable|string|max:100',
            'services.*.quantity'       => 'required|numeric|min:0.01',
            'services.*.unit'           => 'nullable|string|max:20',
        ]);

        $tenantId = auth()->user()->tenant_id;

        DB::beginTransaction();
        try {
            $queue = WorkshopQueue::create([
                'tenant_id'    => $tenantId,
                'queue_number' => WorkshopQueue::generateNumber($tenantId),
                'client_name'  => $request->client_name,
                'client_phone' => $request->client_phone,
                'notes'        => $request->notes,
                'status'       => 'waiting',
            ]);

            foreach ($request->services as $s) {
                WorkshopQueueService::create([
                    'queue_id'       => $queue->id,
                    'label'          => $s['label'],
                    'material_type'  => $s['material_type'] ?? null,
                    'material_color' => $s['material_color'] ?? null,
                    'quantity'       => $s['quantity'],
                    'unit'           => $s['unit'] ?? 'u',
                    'is_done'        => false,
                ]);
            }

            DB::commit();
            return response()->json([
                'success'      => true,
                'queue_number' => $queue->queue_number,
                'message'      => "Client {$queue->client_name} ajouté en {$queue->queue_number}",
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Toggle a service done / not done.
     */
    public function toggleService($serviceId)
    {
        $service = WorkshopQueueService::with('queue')->findOrFail($serviceId);
        $userId  = auth()->id();

        DB::transaction(function () use ($service, $userId) {
            if ($service->is_done) {
                $service->update(['is_done' => false, 'done_at' => null, 'done_by' => null]);
            } else {
                $service->update(['is_done' => true, 'done_at' => now(), 'done_by' => $userId]);
            }

            $queue = $service->queue;
            $queue->load('services');
            $allDone = $queue->services->every(fn($s) => $s->is_done);

            if ($queue->status === 'waiting') {
                $queue->update(['status' => 'in_progress', 'started_at' => now()]);
            }

            if ($allDone && $queue->status !== 'done') {
                $queue->update(['status' => 'done', 'done_at' => now()]);
            } elseif (!$allDone && $queue->status === 'done') {
                $queue->update(['status' => 'in_progress', 'done_at' => null]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Hide job from workshop mobile view (worker is finished).
     */
    public function hideFromWorkshop($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $queue    = WorkshopQueue::where('tenant_id', $tenantId)->findOrFail($id);
        $queue->update(['is_hidden_from_workshop' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Mark client's job as delivered (handed back to client).
     */
    public function deliver($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $queue    = WorkshopQueue::where('tenant_id', $tenantId)->findOrFail($id);
        $queue->update(['status' => 'delivered', 'delivered_at' => now()]);
        return response()->json(['success' => true, 'message' => "Commande {$queue->queue_number} livrée!"]);
    }

    /**
     * Cancel delivery (return to done/ready state).
     */
    public function undeliver($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $queue    = WorkshopQueue::where('tenant_id', $tenantId)->findOrFail($id);
        $queue->update(['status' => 'done', 'delivered_at' => null]);
        return response()->json(['success' => true, 'message' => "Livraison annulée pour {$queue->queue_number}!"]);
    }

    /**
     * Delete / cancel a queue entry.
     */
    public function destroy($id)
    {
        $tenantId = auth()->user()->tenant_id;
        WorkshopQueue::where('tenant_id', $tenantId)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
