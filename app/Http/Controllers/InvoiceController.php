<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Invoice, InvoiceItem, Client, StockPanel, StockCanto, Service};
use App\Services\{StockService, ClientLedgerService};

class InvoiceController extends Controller
{
    /**
     * List all invoices with client & items.
     */
    public function index(Request $request)
    {
        $query = Invoice::withoutGlobalScopes()->with(['client' => function($q) { $q->withoutGlobalScopes()->withTrashed(); }, 'items', 'user:id,name'])
            ->latest();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Date range
        if ($request->filled('from')) {
            $query->whereDate('issue_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('issue_date', '<=', $request->to);
        }

        $invoices = $query->get()->map(function ($inv) {
            // Auto-detect expired quotes
            $status = $inv->status;
            if ($inv->isExpired() && !in_array($status, ['accepted', 'refused', 'cancelled', 'expired'])) {
                $status = 'expired';
            }

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'type' => $inv->type,
                'status' => $status,
                'issue_date' => $inv->issue_date->format('Y-m-d'),
                'due_date' => $inv->due_date?->format('Y-m-d'),
                'validity_days' => $inv->validity_days,
                'expiry_date' => $inv->expiry_date?->format('Y-m-d'),
                'is_expired' => $inv->isExpired(),
                'validated_at' => $inv->validated_at?->toDateTimeString(),
                'stock_deducted' => (bool) $inv->stock_deducted,
                'client' => $inv->client,
                'user_name' => $inv->user?->name ?? 'Système',
                'subtotal' => (float) $inv->subtotal,
                'tax_rate' => (float) $inv->tax_rate,
                'tax_amount' => (float) $inv->tax_amount,
                'total' => (float) $inv->total,
                'amount_paid' => (float) $inv->amount_paid,
                'remaining' => $inv->remainingBalance(),
                'notes' => $inv->notes,
                'items' => $inv->items,
                'items_count' => $inv->items->count(),
                'created_at' => $inv->created_at->toDateTimeString(),
            ];
        });

        return response()->json($invoices);
    }

    /**
     * Get a single invoice with all details.
     */
    public function show($id)
    {
        $invoice = Invoice::withoutGlobalScopes()->with(['client' => function($q) { $q->withoutGlobalScopes()->withTrashed(); }, 'items', 'user:id,name'])->findOrFail($id);

        return response()->json([
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'type' => $invoice->type,
            'status' => $invoice->status,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date?->format('Y-m-d'),
            'client' => $invoice->client,
            'user_name' => $invoice->user?->name ?? 'Système',
            'subtotal' => (float) $invoice->subtotal,
            'tax_rate' => (float) $invoice->tax_rate,
            'tax_amount' => (float) $invoice->tax_amount,
            'total' => (float) $invoice->total,
            'amount_paid' => (float) $invoice->amount_paid,
            'remaining' => $invoice->remainingBalance(),
            'notes' => $invoice->notes,
            'items' => $invoice->items,
        ]);
    }

    /**
     * Create a new invoice/quote with items (transactional).
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:invoice,quote',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'validity_days' => 'nullable|integer|min:1|max:365',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.category' => 'required|in:mdf,lati,hardware,labor,canto,service,other',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.item_type' => 'nullable|in:stock_panel,stock_canto,service',
            'items.*.item_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $tenantId = auth()->user()->tenant_id;
            $taxRate = (float) ($request->tax_rate ?? 0);
            $validityDays = $request->type === 'quote' ? ($request->validity_days ?? 15) : null;
            $expiryDate = $validityDays ? \Carbon\Carbon::parse($request->issue_date)->addDays($validityDays)->format('Y-m-d') : null;

            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
            }
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            // Generate invoice number
            $invoiceNumber = Invoice::generateNumber($tenantId, $request->type);

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'client_id' => $request->client_id,
                'user_id' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'type' => $request->type,
                'status' => 'draft',
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'validity_days' => $validityDays,
                'expiry_date' => $expiryDate,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'amount_paid' => 0,
                'notes' => $request->notes,
            ]);

            // Create line items
            foreach ($request->items as $index => $item) {
                $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $unitCost = $this->resolveUnitCost($item);

                InvoiceItem::create([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'unité',
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal,
                    'sort_order' => $index,
                    'item_type' => $item['item_type'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ($request->type === 'quote' ? 'Devis' : 'Facture') . " {$invoiceNumber} créé(e) avec succès !",
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice Creation Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update an existing invoice/quote.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:invoice,quote',
            'status' => 'nullable|in:draft,sent,paid,partial,cancelled,accepted,refused,expired',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'validity_days' => 'nullable|integer|min:1|max:365',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.category' => 'required|in:mdf,lati,hardware,labor,canto,service,other',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.item_type' => 'nullable|in:stock_panel,stock_canto,service',
            'items.*.item_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $invoice = Invoice::withoutGlobalScopes()->findOrFail($id);
            $tenantId = auth()->user()->tenant_id;
            $taxRate = (float) ($request->tax_rate ?? 0);
            $validityDays = $request->type === 'quote' ? ($request->validity_days ?? $invoice->validity_days ?? 15) : null;
            $expiryDate = $validityDays ? \Carbon\Carbon::parse($request->issue_date)->addDays($validityDays)->format('Y-m-d') : null;

            // Recalculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
            }
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            $invoice->update([
                'client_id' => $request->client_id,
                'type' => $request->type,
                'status' => $request->status ?? $invoice->status,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'validity_days' => $validityDays,
                'expiry_date' => $expiryDate,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $request->notes,
            ]);

            // Replace all items (delete old, insert new)
            $invoice->items()->delete();

            foreach ($request->items as $index => $item) {
                $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $unitCost = $this->resolveUnitCost($item);

                InvoiceItem::create([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'unité',
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal,
                    'sort_order' => $index,
                    'item_type' => $item['item_type'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Document {$invoice->invoice_number} mis à jour avec succès !",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice Update Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update invoice status only.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,paid,partial,cancelled,accepted,refused,expired',
        ]);

        $invoice = Invoice::findOrFail($id);
        $invoice->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Statut mis à jour : {$request->status}",
        ]);
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertToInvoice($id)
    {
        $quote = Invoice::where('type', 'quote')->findOrFail($id);
        $tenantId = auth()->user()->tenant_id;

        DB::beginTransaction();
        try {
            $invoiceNumber = Invoice::generateNumber($tenantId, 'invoice');

            $quote->update([
                'type' => 'invoice',
                'invoice_number' => $invoiceNumber,
                'status' => 'draft',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Devis converti en facture {$invoiceNumber} !",
                'invoice_number' => $invoiceNumber,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete (soft) an invoice.
     */
    public function destroy($id)
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Impossible de supprimer une facture déjà payée.'], 422);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => "Document {$invoice->invoice_number} supprimé.",
        ]);
    }

    /**
     * Generate next available number preview.
     */
    public function nextNumber(Request $request)
    {
        $type = $request->input('type', 'invoice');
        $tenantId = auth()->user()->tenant_id;
        $number = Invoice::generateNumber($tenantId, $type);

        return response()->json(['next_number' => $number]);
    }

    /**
     * Duplicate an invoice/quote with new number.
     */
    public function duplicate($id)
    {
        $original = Invoice::withoutGlobalScopes()->with('items')->findOrFail($id);
        $tenantId = auth()->user()->tenant_id;

        DB::beginTransaction();
        try {
            $newNumber = Invoice::generateNumber($tenantId, $original->type);
            $validityDays = $original->type === 'quote' ? ($original->validity_days ?? 15) : null;
            $issueDate = now()->format('Y-m-d');
            $expiryDate = $validityDays ? now()->addDays($validityDays)->format('Y-m-d') : null;

            $copy = Invoice::create([
                'tenant_id' => $tenantId,
                'client_id' => $original->client_id,
                'user_id' => auth()->id(),
                'invoice_number' => $newNumber,
                'type' => $original->type,
                'status' => 'draft',
                'issue_date' => $issueDate,
                'due_date' => $original->due_date,
                'validity_days' => $validityDays,
                'expiry_date' => $expiryDate,
                'subtotal' => $original->subtotal,
                'tax_rate' => $original->tax_rate,
                'tax_amount' => $original->tax_amount,
                'total' => $original->total,
                'amount_paid' => 0,
                'notes' => $original->notes,
            ]);

            foreach ($original->items as $item) {
                InvoiceItem::create([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $copy->id,
                    'description' => $item->description,
                    'category' => $item->category,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'unit_cost' => $item->unit_cost ?? 0,
                    'total' => $item->total,
                    'sort_order' => $item->sort_order,
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Document dupliqué : {$newNumber}",
                'invoice_id' => $copy->id,
                'invoice_number' => $newNumber,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get summary counts for dashboard.
     */
    public function summary()
    {
        $pendingQuotes = Invoice::withoutGlobalScopes()
            ->where('type', 'quote')
            ->whereIn('status', ['draft', 'sent'])
            ->count();

        $expiredQuotes = Invoice::withoutGlobalScopes()
            ->where('type', 'quote')
            ->whereIn('status', ['draft', 'sent'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->count();

        $unpaidInvoices = Invoice::withoutGlobalScopes()
            ->where('type', 'invoice')
            ->whereIn('status', ['draft', 'sent', 'partial'])
            ->count();

        return response()->json([
            'pending_quotes' => $pendingQuotes,
            'expired_quotes' => $expiredQuotes,
            'unpaid_invoices' => $unpaidInvoices,
        ]);
    }

    /**
     * Validate an invoice: deduct stock + add debt to client.
     */
    public function validateInvoice($id)
    {
        $invoice = Invoice::withoutGlobalScopes()->with('items')->findOrFail($id);

        if ($invoice->type !== 'invoice') {
            return response()->json(['error' => 'Seules les factures peuvent être validées. Convertissez d\'abord le devis.'], 422);
        }

        if ($invoice->validated_at) {
            return response()->json(['error' => 'Cette facture est déjà validée.'], 422);
        }

        if ($invoice->status === 'cancelled') {
            return response()->json(['error' => 'Impossible de valider une facture annulée.'], 422);
        }

        DB::beginTransaction();
        try {
            $stockService = app(StockService::class);
            $ledgerService = app(ClientLedgerService::class);

            // Deduct stock for linked items
            foreach ($invoice->items as $item) {
                if (!$item->item_type || !$item->item_id) continue;

                if ($item->item_type === 'stock_panel') {
                    $stockService->deductPanel($item->item_id, (float) $item->quantity);
                } elseif ($item->item_type === 'stock_canto') {
                    $stockService->deductCanto($item->item_id, (float) $item->quantity);
                }
            }

            // Add debt to client
            $ledgerService->adjustCreditForOrder($invoice->client_id, (float) $invoice->total);

            // Mark as validated
            $invoice->update([
                'validated_at' => now(),
                'stock_deducted' => true,
                'status' => 'sent',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Facture {$invoice->invoice_number} validée ! Stock déduit et dette ajoutée au client.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice Validation Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Record a payment on an invoice.
     */
    public function payInvoice(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->findOrFail($id);

        if ($invoice->type !== 'invoice') {
            return response()->json(['error' => 'Les paiements ne s\'appliquent qu\'aux factures.'], 422);
        }

        if (!$invoice->validated_at) {
            return response()->json(['error' => 'Veuillez valider la facture avant d\'enregistrer un paiement.'], 422);
        }

        $remaining = (float) $invoice->total - (float) $invoice->amount_paid;
        $amount = (float) $request->amount;

        if ($amount > ($remaining + 0.01)) {
            return response()->json(['error' => "Le montant ({$amount} DH) dépasse le reste à payer ({$remaining} DH)."], 422);
        }

        DB::beginTransaction();
        try {
            $ledgerService = app(ClientLedgerService::class);

            // Record payment (reduces client debt)
            $ledgerService->recordPayment(
                $invoice->client_id,
                $amount,
                'reglement',
                null,
                "Paiement facture {$invoice->invoice_number}",
                $invoice->id
            );

            // Update invoice amount_paid
            $newPaid = (float) $invoice->amount_paid + $amount;
            $newStatus = $newPaid >= (float) $invoice->total ? 'paid' : 'partial';

            $invoice->update([
                'amount_paid' => $newPaid,
                'status' => $newStatus,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Paiement de {$amount} DH enregistré sur {$invoice->invoice_number}.",
                'new_status' => $newStatus,
                'amount_paid' => $newPaid,
                'remaining' => (float) $invoice->total - $newPaid,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get available stock items for the invoice item selector.
     */
    public function stockItems()
    {
        $panels = StockPanel::withoutGlobalScopes()
            ->select('id', 'type', 'finish_type', 'color_code', 'color_name', 'size_x', 'size_y', 'thickness', 'quantity', 'base_price_sell', 'cost_price')
            ->where('quantity', '>', 0)
            ->get()
            ->map(fn($p) => [
                'item_type' => 'stock_panel',
                'item_id' => $p->id,
                'category' => $p->type === 'LATI' ? 'lati' : 'mdf',
                'description' => "{$p->type} {$p->color_name} {$p->size_x}x{$p->size_y} ép.{$p->thickness}mm" . ($p->finish_type ? " [{$p->finish_type}]" : ''),
                'unit' => 'unité',
                'unit_price' => (float) $p->base_price_sell,
                'unit_cost' => (float) $p->cost_price,
                'available' => (float) $p->quantity,
            ]);

        $cantos = StockCanto::withoutGlobalScopes()
            ->select('id', 'name', 'color_code', 'color_name', 'finish_type', 'width_mm', 'thickness_mm', 'total_length_remaining', 'base_price_sell_per_m', 'cost_price_per_m')
            ->where('total_length_remaining', '>', 0)
            ->get()
            ->map(fn($c) => [
                'item_type' => 'stock_canto',
                'item_id' => $c->id,
                'category' => 'canto',
                'description' => "Chant {$c->color_name} {$c->width_mm}mm" . ($c->finish_type ? " [{$c->finish_type}]" : ''),
                'unit' => 'm',
                'unit_price' => (float) $c->base_price_sell_per_m,
                'unit_cost' => (float) $c->cost_price_per_m,
                'available' => (float) $c->total_length_remaining,
            ]);

        $services = Service::withoutGlobalScopes()
            ->select('id', 'name', 'unit_price', 'calculation_type')
            ->get()
            ->map(fn($s) => [
                'item_type' => 'service',
                'item_id' => $s->id,
                'category' => 'service',
                'description' => $s->name,
                'unit' => $s->calculation_type === 'per_meter' ? 'm' : 'unité',
                'unit_price' => (float) $s->unit_price,
                'unit_cost' => 0,
                'available' => null,
            ]);

        return response()->json([
            'panels' => $panels,
            'cantos' => $cantos,
            'services' => $services,
        ]);
    }

    /**
     * Resolve unit cost for an item based on its type.
     */
    private function resolveUnitCost(array $item): float
    {
        $type = $item['item_type'] ?? null;
        $id = $item['item_id'] ?? null;

        if (!$type || !$id) return 0;

        if ($type === 'stock_panel') {
            $panel = StockPanel::find($id);
            return $panel ? (float) $panel->cost_price : 0;
        }

        if ($type === 'stock_canto') {
            $canto = StockCanto::find($id);
            return $canto ? (float) $canto->cost_price_per_m : 0;
        }

        return 0;
    }
}
