<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Invoice, InvoiceItem, Client};

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
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'type' => $inv->type,
                'status' => $inv->status,
                'issue_date' => $inv->issue_date->format('Y-m-d'),
                'due_date' => $inv->due_date?->format('Y-m-d'),
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
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.category' => 'required|in:mdf,lati,hardware,labor,canto,service,other',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $tenantId = auth()->user()->tenant_id;
            $taxRate = (float) ($request->tax_rate ?? 0);

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

                InvoiceItem::create([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'unité',
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                    'sort_order' => $index,
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
            'status' => 'nullable|in:draft,sent,paid,partial,cancelled',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.category' => 'required|in:mdf,lati,hardware,labor,canto,service,other',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $invoice = Invoice::withoutGlobalScopes()->findOrFail($id);
            $tenantId = auth()->user()->tenant_id;
            $taxRate = (float) ($request->tax_rate ?? 0);

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

                InvoiceItem::create([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'unité',
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                    'sort_order' => $index,
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
            'status' => 'required|in:draft,sent,paid,partial,cancelled',
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
}
