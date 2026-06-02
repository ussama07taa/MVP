<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\WorkshopQueue;
use App\Models\Payment;
use App\Http\Requests\StoreClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index()
    {
        return Client::withTrashed()
            ->select('id', 'name', 'phone', 'address', 'city', 'notes', 'total_credit', 'created_at')
            ->latest()
            ->get();
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();
        
        $client = new Client();
        $client->name = $validated['name'];
        $client->phone = $validated['phone'] ?? null;
        $client->address = $validated['address'] ?? null;
        $client->city = $validated['city'] ?? null;
        $client->notes = $validated['notes'] ?? null;
        $client->tenant_id = auth()->user()->tenant_id;
        $client->save();

        return response()->json($client, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        $client = Client::withoutGlobalScopes()->findOrFail($id);
        $client->update($validated);

        return response()->json($client);
    }

    public function destroy($id)
    {
        $client = Client::withTrashed()->findOrFail($id);

        if ((float) $client->total_credit > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un client avec un crédit en cours.',
            ], 422);
        }

        // If already soft-deleted, we can force delete or just leave it. 
        // Let's force delete if the user clicks delete again.
        if ($client->trashed()) {
            $client->forceDelete();
        } else {
            $client->delete();
        }

        return response()->json(['message' => 'Client supprimé avec succès.']);
    }

    public function history($id)
    {
        $client = Client::findOrFail($id);
        $orders = Order::with(['lines.item', 'payments'])->where('client_id', $id)->latest()->get();

        // Devis & Factures models for internal processing
        $invoices_models = Invoice::with(['client', 'items'])
            ->where('client_id', $id)
            ->latest()
            ->get();

        $invoices = $invoices_models->map(function ($inv) {
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'type' => $inv->type,
                'status' => $inv->isExpired() ? 'expired' : $inv->status,
                'total' => $inv->total,
                'amount_paid' => $inv->amount_paid,
                'issue_date' => $inv->issue_date,
                'expiry_date' => $inv->expiry_date,
                'validity_days' => $inv->validity_days,
            ];
        });

        // Workshop jobs linked by client name or phone
        $workshopJobs = WorkshopQueue::withoutGlobalScopes()
            ->with('services')
            ->where(function ($q) use ($client) {
                $q->where('client_name', 'LIKE', '%' . $client->name . '%');
                if ($client->phone) {
                    $q->orWhere('client_phone', $client->phone);
                }
            })
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue_number' => $job->queue_number,
                    'status' => $job->status,
                    'created_at' => $job->created_at,
                    'done_at' => $job->done_at,
                    'delivered_at' => $job->delivered_at,
                    'services' => $job->services->map(fn($s) => [
                        'name' => $s->label,
                        'is_done' => $s->is_done,
                    ]),
                ];
            });

        // 5. Unified Timeline (Ledger)
        $timeline = collect();

        // Add Orders (Debt)
        foreach ($orders as $o) {
            $timeline->push([
                'id' => 'ord_' . $o->id,
                'date' => $o->created_at,
                'type' => 'Achat (POS)',
                'reference' => '#FAC-' . $o->id,
                'amount' => (float) $o->total_sell_price,
                'impact' => 'increase',
                'raw_id' => $o->id,
                'raw_type' => 'order'
            ]);
        }

        // Add Validated Invoices (Debt)
        foreach ($invoices_models as $inv) {
            if ($inv->validated_at && $inv->type === 'invoice') {
                $timeline->push([
                    'id' => 'inv_' . $inv->id,
                    'date' => $inv->validated_at,
                    'type' => 'Facture Validée',
                    'reference' => $inv->invoice_number,
                    'amount' => (float) $inv->total,
                    'impact' => 'increase',
                    'raw_id' => $inv->id,
                    'raw_type' => 'invoice'
                ]);
            }
        }

        // Add Payments (Credit)
        $payments = Payment::withoutGlobalScopes()->with('invoice')->where('client_id', $id)->get();
        foreach ($payments as $p) {
            $ref = 'Paiement';
            if ($p->order_id) $ref .= " (Vente #{$p->order_id})";
            if ($p->invoice_id) $ref .= " ({$p->invoice?->invoice_number})";
            if ($p->notes) $ref .= " - {$p->notes}";

            $timeline->push([
                'id' => 'pay_' . $p->id,
                'date' => $p->created_at,
                'type' => 'Paiement',
                'reference' => $p->payment_method ? strtoupper($p->payment_method) : 'CASH',
                'description' => $ref,
                'amount' => (float) $p->amount,
                'impact' => 'decrease',
                'raw_id' => $p->id,
                'raw_type' => 'payment'
            ]);
        }

        // Add Returns (Credit)
        $returns = \App\Models\OrderReturn::withoutGlobalScopes()->whereHas('order', function($q) use ($id) {
            $q->withoutGlobalScopes()->where('client_id', $id);
        })->get();

        foreach ($returns as $r) {
            $timeline->push([
                'id' => 'ret_' . $r->id,
                'date' => $r->created_at,
                'type' => 'Retour Article',
                'reference' => 'Retour #' . $r->id,
                'amount' => (float) $r->total_refunded,
                'impact' => 'decrease',
                'raw_id' => $r->id,
                'raw_type' => 'return'
            ]);
        }

        $sortedTimeline = $timeline->sortByDesc('date')->values();

        // Stats
        $totalRevenue = $orders->sum('total_sell_price') + $invoices_models->whereNotNull('validated_at')->where('type', 'invoice')->sum('total');
        $totalPaid = $orders->sum('amount_paid') + $invoices_models->where('type', 'invoice')->sum('amount_paid');
        $lastOrder = $orders->first();
        $orderCount = $orders->count();
        $invoiceCount = $invoices_models->where('type', 'invoice')->count();
        $devisCount = $invoices_models->where('type', 'quote')->count();

        return response()->json([
            'client' => $client,
            'orders' => $orders,
            'invoices' => $invoices->values(),
            'workshop_jobs' => $workshopJobs,
            'timeline' => $sortedTimeline,
            'stats' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_paid' => round($totalPaid, 2),
                'order_count' => $orderCount,
                'invoice_count' => $invoiceCount,
                'devis_count' => $devisCount,
                'workshop_jobs_count' => $workshopJobs->count(),
                'last_order_date' => $lastOrder?->created_at?->format('Y-m-d'),
                'member_since' => $client->created_at?->format('Y-m-d'),
            ],
        ]);
    }

    public function pay(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.1']);
        
        $client = Client::withoutGlobalScopes()->findOrFail($id);

        if ($request->amount > ($client->total_credit + 0.01)) {
            return response()->json([
                'error' => "Le montant (" . number_format($request->amount, 2) . " DH) dépasse la dette actuelle (" . number_format($client->total_credit, 2) . " DH)."
            ], 422);
        }
        return DB::transaction(function() use ($request, $id, $client) {
            $amountToDistribute = $request->amount;
            
            $unpaidOrders = Order::withoutGlobalScopes()->where('client_id', $id)
                ->whereRaw('amount_paid < total_sell_price')
                ->orderBy('created_at', 'asc')
                ->get();
                
            foreach ($unpaidOrders as $order) {
                if ($amountToDistribute <= 0) break;
                
                $reste = $order->total_sell_price - $order->amount_paid;
                $paymentForThisOrder = min($amountToDistribute, $reste);
                
                Payment::create([
                    'tenant_id' => $client->tenant_id,
                    'order_id' => $order->id,
                    'client_id' => $client->id,
                    'amount' => $paymentForThisOrder,
                    'type' => 'solde',
                    'payment_method' => 'cash'
                ]);
                
                $order->increment('amount_paid', $paymentForThisOrder);
                $amountToDistribute -= $paymentForThisOrder;
            }
            
            $client->decrement('total_credit', $request->amount);
            return response()->json(['message' => 'Paiement effectué']);
        });
    }

    public function recalculateCredit($id)
    {
        return DB::transaction(function() use ($id) {
            $client = Client::findOrFail($id);

            // 1. Fetch all financial records chronologically
            $orders = Order::where('client_id', $id)
                ->orderBy('created_at', 'asc')
                ->get();

            $invoices = Invoice::where('client_id', $id)
                ->where('type', 'invoice')
                ->whereNotNull('validated_at')
                ->orderBy('issue_date', 'asc')
                ->get();

            $totalPayments = Payment::where('client_id', $id)
                ->sum('amount');

            $totalReturns = \App\Models\OrderReturn::whereHas('order', function($q) use ($id) {
                    $q->where('client_id', $id);
                })
                ->sum('total_refunded');

            $paymentPool = (float) $totalPayments;
            $revenueTotal = 0;

            // 2. Reset and Distribute payments to Orders
            foreach ($orders as $order) {
                // Get returns specifically for this order
                $orderRefunds = \App\Models\OrderReturn::where('order_id', $order->id)
                    ->sum('total_refunded');

                $netTotal = (float) $order->total_sell_price - (float) $orderRefunds;
                $revenueTotal += $netTotal;

                $paymentForOrder = min($paymentPool, $netTotal);
                $order->update(['amount_paid' => round($paymentForOrder, 2)]);
                $paymentPool -= $paymentForOrder;
            }

            // 3. Distribute remaining payments to Standard Invoices
            foreach ($invoices as $invoice) {
                $netTotal = (float) $invoice->total;
                $revenueTotal += $netTotal;

                $paymentForInvoice = min($paymentPool, $netTotal);
                $invoice->update(['amount_paid' => round($paymentForInvoice, 2)]);
                $paymentPool -= $paymentForInvoice;
            }

            // 4. Update Client's total_credit (Debt)
            // total_credit = (Total Revenue - Total Payments - Total Returns)
            // But since we already subtracted returns from revenue in our loops:
            // total_credit = revenueTotal - totalPayments
            $newCredit = round($revenueTotal - $totalPayments, 2);
            $client->update(['total_credit' => $newCredit]);

            return response()->json([
                'message' => "Crédit et Historique synchronisés avec succès. Solde: " . number_format($newCredit, 2) . " DH",
                'new_credit' => $newCredit
            ]);
        });
    }
}
