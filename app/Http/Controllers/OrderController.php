<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreOrderRequest;
use App\Models\{Order, OrderLine, StockPanel, StockCanto, Service, Client, Payment, User, Tenant, OrderReturn, OrderReturnLine, Consumable};
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\OrderResource;

class OrderController extends Controller {
    protected $checkout;
    protected $ledger;

    public function __construct(\App\Services\CheckoutService $checkout, \App\Services\ClientLedgerService $ledger)
    {
        $this->checkout = $checkout;
        $this->ledger = $ledger;
    }

    public function index() {
        $tenantId = auth()->user()->tenant_id;

        // 1. Fetch POS Orders (with full relations)
        $orders = Order::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['client', 'lines.item', 'payments'])
            ->latest()
            ->get();

        // 2. Fetch Validated Invoices (NO items.item — morph aliases differ)
        $invoices = collect();
        try {
            $invoices = \App\Models\Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('type', 'invoice')
                ->whereNotNull('validated_at')
                ->with(['client', 'items', 'payments'])
                ->latest()
                ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Could not load invoices: " . $e->getMessage());
        }

        // 3. Build unified collection
        $result = collect();

        foreach ($orders as $order) {
            $result->push([
                'id' => $order->id,
                'client' => $order->client ? ['id' => $order->client->id, 'name' => $order->client->name, 'phone' => $order->client->phone] : null,
                'total_sell_price' => (float) $order->total_sell_price,
                'amount_paid' => (float) $order->amount_paid,
                'status' => $order->status,
                'display_reference' => "#FAC-" . $order->id,
                'source' => 'pos',
                'created_at' => $order->created_at?->toIso8601String(),
                'lines' => $order->lines->map(fn($l) => [
                    'id' => $l->id,
                    'label' => $l->label,
                    'quantity' => (float) $l->quantity,
                    'unit_sell_price' => (float) $l->unit_sell_price,
                    'total_line_sell' => (float) $l->total_line_sell,
                    'item_type' => $l->item_type,
                    'item' => $l->relationLoaded('item') ? $l->item : null,
                    'quantity_returned' => (float) $l->returns()->sum('quantity_returned'),
                ]),
                'payments' => $order->payments,
                'total_refunded' => (float) $order->returns()->sum('total_refunded'),
                'return_history' => [],
            ]);
        }

        foreach ($invoices as $inv) {
            $result->push([
                'id' => $inv->id,
                'client' => $inv->client ? ['id' => $inv->client->id, 'name' => $inv->client->name, 'phone' => $inv->client->phone] : null,
                'total_sell_price' => (float) $inv->total,
                'amount_paid' => (float) $inv->amount_paid,
                'status' => $inv->status,
                'display_reference' => $inv->invoice_number,
                'source' => 'invoice',
                'created_at' => $inv->validated_at?->toIso8601String(),
                'lines' => $inv->items->map(fn($l) => [
                    'id' => $l->id,
                    'label' => $l->description,
                    'quantity' => (float) $l->quantity,
                    'unit_sell_price' => (float) $l->unit_price,
                    'total_line_sell' => (float) $l->total,
                    'item_type' => $l->item_type,
                    'item' => null,
                    'quantity_returned' => 0,
                ]),
                'payments' => $inv->payments,
                'total_refunded' => 0,
                'return_history' => [],
            ]);
        }

        // Sort by date descending
        $sorted = $result->sortByDesc('created_at')->values();

        return response()->json(['data' => $sorted]);
    }

    public function show($id) {
        $order = Order::withoutGlobalScopes()->with(['client', 'lines.item', 'payments'])->findOrFail($id);
        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request) {
        try {
            $order = $this->checkout->execute($request->validated());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Commande validée avec succès!', 
                    'order_id' => $order->id, 
                    'profit' => $order->total_sell_price - $order->total_cost_price
                ], 201);
            }

            return redirect()->back()->with('message', 'Commande validée avec succès!')->with('order_id', $order->id);
        } catch (\Exception $e) {
            \Log::error('Checkout Error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function pay(\Illuminate\Http\Request $request, $id) {
        $request->validate([
            'amount' => 'required|numeric|min:0.1',
            'source' => 'sometimes|string'
        ]);
        
        try {
            return DB::transaction(function() use ($request, $id) {
                if ($request->source === 'invoice') {
                    $invoice = \App\Models\Invoice::withoutGlobalScopes()->findOrFail($id);
                    
                    $this->ledger->recordPayment(
                        $invoice->client_id, 
                        $request->amount, 
                        'reglement', 
                        null,
                        "Paiement facture {$invoice->invoice_number}",
                        $invoice->id
                    );

                    $invoice->increment('amount_paid', $request->amount);
                    
                    // Update status for Invoice
                    $newPaid = (float) $invoice->amount_paid;
                    $status = $newPaid >= (float) $invoice->total ? 'paid' : 'partial';
                    $invoice->update(['status' => $status]);

                    $invoice->load(['client', 'items', 'payments']);
                    
                    // Map items to lines for resource consistency
                    $invoice->total_sell_price = (float) $invoice->total;
                    $invoice->display_reference = $invoice->invoice_number;
                    $invoice->source = 'invoice';
                    $invoice->setRelation('lines', $invoice->items);

                    return response()->json([
                        'message' => 'Paiement facture ajouté', 
                        'order' => new OrderResource($invoice)
                    ]);
                } else {
                    $order = Order::withoutGlobalScopes()->findOrFail($id);
                    
                    $this->ledger->recordPayment(
                        $order->client_id, 
                        $request->amount, 
                        'avance', 
                        $order->id
                    );

                    $order->increment('amount_paid', $request->amount);
                    $order->load(['client', 'lines.item', 'payments']);

                    return response()->json([
                        'message' => 'Paiement ajouté', 
                        'order' => new OrderResource($order)
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storeReturn(\Illuminate\Http\Request $request, $orderId)
    {
        $order = Order::withoutGlobalScopes()->with('lines.item')->findOrFail($orderId);

        // Idempotency Check
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            $existingReturn = OrderReturn::where('idempotency_key', $idempotencyKey)->first();
            if ($existingReturn) {
                return response()->json([
                    'success' => true,
                    'message' => 'Retour déjà traité (Idempotency)',
                    'refunded' => $existingReturn->total_refunded,
                    'is_duplicate' => true
                ]);
            }
        }
        
        return DB::transaction(function() use ($request, $order, $idempotencyKey) {
            $totalRefund = 0;
            $userId = auth()->id();

            $orderReturn = OrderReturn::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'user_id' => $userId,
                'idempotency_key' => $idempotencyKey,
                'total_refunded' => 0, // Will update later
                'reason' => $request->reason
            ]);

            foreach ($request->return_lines as $lineData) {
                $orderLine = $order->lines()
                    ->where('id', $lineData['order_line_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // Ensure we don't return more than ordered (locked row prevents race-condition double-returns)
                $alreadyReturned = OrderReturnLine::where('order_line_id', $orderLine->id)
                    ->lockForUpdate()
                    ->sum('quantity_returned');
                $maxReturnable = $orderLine->quantity - $alreadyReturned;

                $qtyToReturn = min($lineData['quantity'], $maxReturnable);

                if ($qtyToReturn <= 0) continue;

                $refundAmount = $qtyToReturn * $orderLine->unit_sell_price;
                $totalRefund += $refundAmount;

                OrderReturnLine::create([
                    'order_return_id' => $orderReturn->id,
                    'order_line_id' => $orderLine->id,
                    'quantity_returned' => $qtyToReturn,
                    'amount_refunded' => $refundAmount
                ]);

                // Restock inventory polymorphically with row-level lock
                if ($orderLine->item_type === StockPanel::class) {
                    StockPanel::whereId($orderLine->item_id)
                        ->lockForUpdate()
                        ->increment('quantity', $qtyToReturn);
                } elseif ($orderLine->item_type === StockCanto::class) {
                    StockCanto::whereId($orderLine->item_id)
                        ->lockForUpdate()
                        ->increment('total_length_remaining', $qtyToReturn);
                } elseif ($orderLine->item_type === Consumable::class) {
                    Consumable::whereId($orderLine->item_id)
                        ->lockForUpdate()
                        ->increment('quantity_in_stock', $qtyToReturn);
                }
            }

            $orderReturn->update(['total_refunded' => $totalRefund]);

            // Adjust client credit/debt & Record refund payment via Ledger
            $this->ledger->recordPayment(
                $order->client_id,
                -$totalRefund, // Negative amount for refund
                'retour',
                $order->id,
                'Remboursement Retour #' . $orderReturn->id . ($request->reason ? ' : ' . $request->reason : '')
            );

            // Clear Stock Cache
            Cache::forget("tenant.{$order->tenant_id}.panels");
            Cache::forget("tenant.{$order->tenant_id}.cantos");

            return response()->json([
                'success' => true, 
                'message' => 'Retour traité avec succès. Inventaire mis à jour.',
                'refunded' => $totalRefund
            ]);
        });
    }
}
