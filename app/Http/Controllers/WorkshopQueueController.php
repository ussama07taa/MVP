<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\{WorkshopQueue, WorkshopQueueService};

class WorkshopQueueController extends Controller
{
    /**
     * Full queue list for admin: all pending/active jobs + today's delivered.
     */
    public function index()
    {
        if (!in_array(auth()->user()->role, ['admin', 'cashier'])) abort(403);
        return response()->json($this->buildQueueData(includeDelivered: true, includeHidden: true));
    }

    /**
     * Lightweight queue for employee mobile (non-delivered and non-hidden only).
     */
    public function mobileIndex()
    {
        return response()->json($this->buildQueueData(includeDelivered: false, includeHidden: false));
    }

    private function buildQueueData(bool $includeDelivered, bool $includeHidden): array
    {
        // Show ALL non-delivered jobs (regardless of date) + delivered jobs from today only
        $query = WorkshopQueue::with(['services' => fn($q) => $q->with('doneByUser:id,name')])
            ->where(function ($q) {
                $q->where('status', '!=', 'delivered')
                  ->orWhereDate('delivered_at', today());
            })
            ->orderBy('is_priority', 'desc')
            ->orderBy('created_at', 'asc');

        if (!$includeDelivered) {
            $query->where('status', '!=', 'delivered');
        }

        if (!$includeHidden) {
            $query->where(function ($q) {
                $q->where('is_hidden_from_workshop', false)
                  ->orWhere('status', 'in_progress');
            });
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
                'is_priority'    => (bool) $q->is_priority,
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
                'created_date'      => $q->created_at->format('d/m'),
                'waiting_since'     => $q->created_at->isToday() ? $q->created_at->format('H:i') : $q->created_at->format('d/m H:i'),
                'waiting_minutes'   => (int) $q->created_at->diffInMinutes(now()),
                'started_at'        => $q->started_at?->format('H:i'),
                'done_at_time'      => $q->done_at?->format('H:i'),
                'delivered_at_time' => $q->delivered_at?->format('H:i'),
                'tefsil_url'        => $q->tefsil_path ? url("/api/workshop/queue/{$q->id}/tefsil") : null,
            ];
        })->toArray();
    }

    /**
     * Add a new client to the queue.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Handle services being JSON-encoded when using FormData for file uploads
        if (isset($data['services']) && is_string($data['services'])) {
            $data['services'] = json_decode($data['services'], true);
        }

        $validator = Validator::make($data, [
            'client_name'  => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:1000',
            'tefsil_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'services'     => 'required|array|min:1',
            'services.*.label'          => 'required|string|max:100',
            'services.*.material_type'  => 'nullable|string|max:100',
            'services.*.material_color' => 'nullable|string|max:100',
            'services.*.quantity'       => 'required|numeric|min:0.01',
            'services.*.unit'           => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $validData = $validator->validated();
        $tenantId = $request->user()->tenant_id;

        DB::beginTransaction();
        try {
            $tefsilPath = null;
            if ($request->hasFile('tefsil_file')) {
                $tefsilPath = $request->file('tefsil_file')->store('workshop/tefsils', 'public');
            }

            $queue = WorkshopQueue::create([
                'tenant_id'    => $tenantId,
                'queue_number' => WorkshopQueue::generateNumber($tenantId),
                'client_name'  => $validData['client_name'],
                'client_phone' => $validData['client_phone'],
                'notes'        => $validData['notes'],
                'status'       => 'waiting',
                'tefsil_path'  => $tefsilPath,
            ]);

            foreach ($validData['services'] as $s) {
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
        $queue = WorkshopQueue::findOrFail($id);
        $queue->update(['is_hidden_from_workshop' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Mark client's job as delivered (handed back to client).
     */
    public function deliver($id)
    {
        $queue = WorkshopQueue::findOrFail($id);
        $queue->update(['status' => 'delivered', 'delivered_at' => now()]);
        return response()->json(['success' => true, 'message' => "Commande {$queue->queue_number} livrée!"]);
    }

    /**
     * Cancel delivery (return to done/ready state).
     */
    public function undeliver($id)
    {
        $queue = WorkshopQueue::findOrFail($id);
        $queue->update(['status' => 'done', 'delivered_at' => null]);
        return response()->json(['success' => true, 'message' => "Livraison annulée pour {$queue->queue_number}!"]);
    }

    /**
     * Delete / cancel a queue entry.
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);
        WorkshopQueue::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function togglePriority($id)
    {
        $queue = WorkshopQueue::findOrFail($id);
        $queue->update(['is_priority' => !$queue->is_priority]);
        return response()->json([
            'success' => true,
            'is_priority' => (bool)$queue->is_priority,
            'message' => $queue->is_priority ? 'Mode Express activé !' : 'Mode standard rétabli.'
        ]);
    }

    /**
     * Serve SketchCut / tefsil file (avoids broken public/storage symlinks on some servers).
     */
    public function downloadTefsil($id)
    {
        $queue = WorkshopQueue::findOrFail($id);

        if ($queue->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (!$queue->tefsil_path || !Storage::disk('public')->exists($queue->tefsil_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($queue->tefsil_path);
    }
}
