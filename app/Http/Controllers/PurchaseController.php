<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\{Purchase, Supplier, SupplierPayment, StockPanel, StockCanto, Consumable};

class PurchaseController extends Controller
{
    protected $stockService;

    public function __construct(\App\Services\StockService $stockService) {
        $this->stockService = $stockService;
    }

    public function store(\App\Http\Requests\StorePurchaseRequest $request) {
        try {
            DB::beginTransaction();
            

            $documentPath = null;
            if ($request->hasFile('invoice_document')) {
                $file = $request->file('invoice_document');
                $documentPath = $file->store('invoices', 'public');
            }

            $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;

            $computedTotal = 0;
            foreach ($items as $item) {
                $qtyAdded = (float)($item['data']['quantity'] ?? ($item['data']['total_length_remaining'] ?? 1));
                $newUnitCost = (float)($item['data']['cost_price'] ?? ($item['data']['cost_price_per_m'] ?? ($item['data']['unit_cost'] ?? 0)));
                $lineTotal = round($qtyAdded * $newUnitCost, 2);
                $computedTotal += $lineTotal;
            }
            $amountPaid = round(min((float) $request->amount_paid, $computedTotal), 2);

            $purchase = Purchase::create([
                'tenant_id' => 1,
                'supplier_id' => $request->supplier_id,
                'reference_invoice' => $request->reference_invoice,
                'total_amount' => $computedTotal,
                'amount_paid' => $amountPaid,
                'document_path' => $documentPath
            ]);

            if ($amountPaid < $computedTotal) {
                $debt = $computedTotal - $amountPaid;
                Supplier::withoutGlobalScopes()->whereId($request->supplier_id)->lockForUpdate()->increment('total_debt', $debt);
            }
            if ($amountPaid > 0) {
                 SupplierPayment::create([
                    'supplier_id' => $request->supplier_id, 
                    'purchase_id' => $purchase->id, 
                    'amount' => $amountPaid, 
                    'payment_method' => $request->payment_method ?? 'cash',
                    'cash_date' => ($request->payment_method === 'check') ? $request->cash_date : null,
                    'status' => ($request->payment_method === 'check' && $request->cash_date) ? 'pending' : 'cashed'
                 ]);
            }

            foreach($items as $item) {
                $qtyAdded = (float)($item['data']['quantity'] ?? ($item['data']['total_length_remaining'] ?? 1));
                $newUnitCost = (float)($item['data']['cost_price'] ?? ($item['data']['cost_price_per_m'] ?? ($item['data']['unit_cost'] ?? 0)));
                $totalLineCost = round($qtyAdded * $newUnitCost, 2);
                $newSellPrice = (float)($item['data']['base_price_sell'] ?? ($item['data']['base_price_sell_per_m'] ?? 0));
                
                $productName = "Article {$item['category']}";
                if ($item['category'] === 'mdf' || $item['category'] === 'panel') {
                    $productName = "MDF " . ($item['data']['type'] ?? '') . " " . ($item['data']['color_name'] ?? '') . " " . ($item['data']['size_x'] ?? '') . "x" . ($item['data']['size_y'] ?? '');
                } elseif ($item['category'] === 'canto') {
                    $productName = "Canto " . ($item['data']['color_name'] ?? '') . " " . ($item['data']['width_mm'] ?? '') . "mm";
                } elseif ($item['category'] === 'consumable') {
                    $productName = $item['data']['name'] ?? 'Consommable';
                }

                $stockItemId = null;
                $stockItemType = null;

                if ($item['category'] === 'mdf' || $item['category'] === 'panel') {
                    if (!empty($item['data']['existing_id'])) {
                        $panel = $this->stockService->recordPanelPurchase(
                            $item['data']['existing_id'],
                            $qtyAdded,
                            $newUnitCost,
                            $newSellPrice,
                            $purchase->id,
                            $request->supplier_id
                        );
                        $stockItemId = $panel->id;
                        $stockItemType = 'StockPanel';
                    } else {
                        $panelData = collect($item['data'])->only([
                            'type', 'size_x', 'size_y', 'thickness', 'color_code', 'color_name',
                            'finish_type', 'provider_catalog', 'quantity', 'cost_price', 'base_price_sell'
                        ])->toArray();

                        $panel = StockPanel::create(array_merge($panelData, [
                            'supplier_id' => $request->supplier_id,
                            'purchase_id' => $purchase->id
                        ]));
                        $stockItemId = $panel->id;
                        $stockItemType = 'StockPanel';
                    }
                } elseif ($item['category'] === 'canto') {
                    if (!empty($item['data']['existing_id'])) {
                        $canto = $this->stockService->recordCantoPurchase(
                            $item['data']['existing_id'],
                            $qtyAdded,
                            $newUnitCost,
                            $newSellPrice,
                            $purchase->id,
                            $request->supplier_id
                        );
                        $stockItemId = $canto->id;
                        $stockItemType = 'StockCanto';
                    } else {
                        $cantoData = collect($item['data'])->only([
                            'name', 'color_code', 'color_name', 'finish_type', 'provider_catalog', 'width_mm',
                            'thickness_mm', 'total_length_remaining', 'cost_price_per_m', 'base_price_sell_per_m'
                        ])->toArray();

                        $canto = StockCanto::create(array_merge($cantoData, [
                            'supplier_id' => $request->supplier_id,
                            'purchase_id' => $purchase->id
                        ]));
                        $stockItemId = $canto->id;
                        $stockItemType = 'StockCanto';
                    }
                } elseif ($item['category'] === 'consumable') {
                    $consumable = Consumable::withoutGlobalScopes()->firstOrCreate(
                        ['name' => $item['data']['name'], 'unit' => $item['data']['unit']],
                        ['quantity_in_stock' => 0, 'average_cost_price' => 0]
                    );
                    
                    $this->stockService->recordConsumablePurchase(
                        $consumable->id,
                        $qtyAdded,
                        $newUnitCost,
                        $newSellPrice
                    );
                }

                \App\Models\PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'stock_item_id' => $stockItemId,
                    'stock_item_type' => $stockItemType,
                    'category' => $item['category'],
                    'product_name_snapshot' => $productName,
                    'quantity' => $qtyAdded,
                    'quantity_remaining' => $stockItemId ? $qtyAdded : null,
                    'unit_cost' => $newUnitCost,
                    'unit_sell_price' => $newSellPrice,
                    'total_line_cost' => $totalLineCost,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Facture Fournisseur traitée avec succès. Stocks mis à jour!', 'purchase_id' => $purchase->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function index() {
        return Purchase::withoutGlobalScopes()->with([
            'supplier' => function ($query) {
                $query->withoutGlobalScopes()->withTrashed()->select('id', 'name', 'deleted_at');
            }, 
            'returns', 
            'lines'
        ])
        ->latest()
        ->get()
        ->map(function ($purchase) {
            $totalReturnedQty = $purchase->returns->sum('returned_quantity');
            $originalQty = $purchase->lines->sum('quantity');
            $availableQty = max(0, $originalQty - $totalReturnedQty);
            
            $status = 'COMPLET';
            $statusColor = 'bg-emerald-50 text-emerald-600 border-emerald-100';
            
            if ($totalReturnedQty > 0) {
                if ($totalReturnedQty >= $originalQty) {
                    $status = 'RETOUR TOTAL';
                    $statusColor = 'bg-rose-50 text-rose-600 border-rose-100';
                } else {
                    $status = 'RETOUR PARTIEL';
                    $statusColor = 'bg-amber-50 text-amber-600 border-amber-100';
                }
            }

            $supplierName = $purchase->supplier ? $purchase->supplier->name : 'Inconnu';
            if ($purchase->supplier && $purchase->supplier->trashed()) {
               $supplierName .= ' (Supprimé)';
            }

            $totalRefundAmount = $purchase->returns->sum('refund_amount');
            $originalTotal = (float)$purchase->total_amount;
            $netAmount = $originalTotal - (float)$totalRefundAmount;

            // Separate available quantities
            $availableMdf = 0;
            $availableCanto = 0;
            $availableConsumable = 0;

            foreach ($purchase->lines as $line) {
                $retQty = $purchase->returns->where('purchase_line_id', $line->id)->sum('returned_quantity');
                $netQty = max(0, $line->quantity - $retQty);
                if (in_array($line->category, ['mdf', 'panel'])) {
                    $availableMdf += $netQty;
                } elseif ($line->category === 'canto') {
                    $availableCanto += $netQty;
                } elseif ($line->category === 'consumable') {
                    $availableConsumable += $netQty;
                }
            }

            return [
                'id' => $purchase->id,
                'ref' => '#ACH-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT),
                'reference_invoice' => $purchase->reference_invoice,
                'supplier_name' => $supplierName,
                'total_amount' => $originalTotal,
                'net_amount' => $netAmount,
                'total_refund' => (float)$totalRefundAmount,
                'amount_paid' => (float)$purchase->amount_paid,
                'created_at' => $purchase->created_at->toDateTimeString(),
                'raw_date' => $purchase->created_at->format('Y-m-d'),
                'document_path' => $purchase->document_path,
                'returned_qty' => $totalReturnedQty,
                'original_qty' => $originalQty,
                'available_qty' => $availableQty,
                'available_mdf' => $availableMdf,
                'available_canto' => $availableCanto,
                'available_consumable' => $availableConsumable,
                'status' => $status,
                'status_color' => $statusColor,
                'returns' => $purchase->returns,
                'lines' => $purchase->lines,
                'item_name' => $purchase->lines->count() > 1 ? $purchase->lines->count() . ' Articles Différents' : ($purchase->lines->first()->product_name_snapshot ?? 'Articles'),
                'item_price' => $purchase->lines->count() > 1 ? null : ($purchase->lines->first()->unit_cost ?? 0)
            ];
        });
    }

    public function suppliers() {
        return Supplier::withoutGlobalScopes()->latest()->get();
    }

    public function storeSupplier(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255', 
            'phone' => 'nullable|string|max:50'
        ]);
        return Supplier::create([
            'name' => $validated['name'], 
            'phone' => $validated['phone'] ?? null, 
            'total_debt' => 0
        ]);
    }
    public function processReturn(Request $request, $id)
    {
        $validated = $request->validate([
            'purchase_line_id' => 'required|exists:purchase_lines,id',
            'returned_quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $purchase = Purchase::withoutGlobalScopes()->lockForUpdate()->findOrFail($id);
            $line = \App\Models\PurchaseLine::where('purchase_id', $purchase->id)->where('id', $validated['purchase_line_id'])->firstOrFail();
            
            $qtyToReturn = (float)$validated['returned_quantity'];
            
            $stockItem = null;
            $availableQty = 0;

            if (in_array($line->category, ['mdf', 'panel'])) {
                if ($line->stock_item_id) {
                    $stockItem = StockPanel::withoutGlobalScopes()->where('id', $line->stock_item_id)->lockForUpdate()->first();
                } else {
                    $stockItem = StockPanel::withoutGlobalScopes()->where('purchase_id', $purchase->id)->lockForUpdate()->first();
                }
                $availableQty = $stockItem ? (float)$stockItem->quantity : 0;
            } elseif ($line->category === 'canto') {
                if ($line->stock_item_id) {
                    $stockItem = StockCanto::withoutGlobalScopes()->where('id', $line->stock_item_id)->lockForUpdate()->first();
                } else {
                    $stockItem = StockCanto::withoutGlobalScopes()->where('purchase_id', $purchase->id)->lockForUpdate()->first();
                }
                $availableQty = $stockItem ? (float)$stockItem->total_length_remaining : 0;
            } elseif ($line->category === 'consumable') {
                $stockItem = Consumable::withoutGlobalScopes()->where('name', $line->product_name_snapshot)->lockForUpdate()->first();
                $availableQty = $stockItem ? (float)$stockItem->quantity_in_stock : 0;
            }

            if ($availableQty <= 0) {
                return response()->json([
                    'error' => "Ce lot est entièrement épuisé (Stock = 0). Impossible d'effectuer un retour pour des articles déjà vendus ou consommés."
                ], 422);
            }

            if ($qtyToReturn > $availableQty) {
                return response()->json([
                    'error' => "Quantité demandée ({$qtyToReturn}) supérieure au stock disponible ({$availableQty})."
                ], 422);
            }

            $refundAmount = $qtyToReturn * (float)$line->unit_cost;

            \App\Models\PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'purchase_line_id' => $line->id,
                'returned_quantity' => $qtyToReturn,
                'refund_amount' => $refundAmount,
                'reason' => $validated['reason']
            ]);

            if ($stockItem) {
                if (in_array($line->category, ['mdf', 'panel'])) {
                    $stockItem->decrement('quantity', $qtyToReturn);
                } elseif ($line->category === 'canto') {
                    $stockItem->decrement('total_length_remaining', $qtyToReturn);
                } else {
                    $stockItem->decrement('quantity_in_stock', $qtyToReturn);
                }
            }

            if ($line->quantity_remaining !== null) {
                $line->decrement('quantity_remaining', min($qtyToReturn, (float) $line->quantity_remaining));
            }

            $existingReturns = (float) $purchase->returns()->sum('refund_amount');
            $netBeforeReturn = max(0, (float) $purchase->total_amount - $existingReturns);
            $unpaidOnPurchase = max(0, $netBeforeReturn - (float) $purchase->amount_paid);
            $debtReduction = min($refundAmount, $unpaidOnPurchase);

            $supplier = Supplier::withoutGlobalScopes()->lockForUpdate()->findOrFail($purchase->supplier_id);
            if ($debtReduction > 0.01) {
                $supplier->decrement('total_debt', $debtReduction);
            }

            $message = $debtReduction > 0.01
                ? "Retour traité avec succès. Stock réduit et dette fournisseur diminuée de {$debtReduction} DH."
                : "Retour traité avec succès. Stock réduit (achat déjà réglé, dette inchangée).";

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        });
    }

    public function supplierHistory($id) {
        $supplier = Supplier::withoutGlobalScopes()->findOrFail($id);
        
        $purchases = Purchase::withoutGlobalScopes()->where('supplier_id', $id)
            ->with(['lines', 'payments', 'returns'])
            ->latest()
            ->get()
            ->map(function ($purch) {
                $totalRefund = (float)$purch->returns->sum('refund_amount');
                $netAmount = (float)$purch->total_amount - $totalRefund;

                return [
                    'id' => $purch->id,
                    'ref' => '#ACH-' . str_pad($purch->id, 4, '0', STR_PAD_LEFT),
                    'reference_invoice' => $purch->reference_invoice,
                    'created_at' => $purch->created_at,
                    'total_amount' => (float)$purch->total_amount,
                    'net_amount' => $netAmount,
                    'total_refund' => $totalRefund,
                    'amount_paid' => (float)$purch->amount_paid,
                    'items' => $purch->lines->map(function ($line) use ($purch) {
                        $returnedLineQty = $purch->returns->where('purchase_line_id', $line->id)->sum('returned_quantity');
                        $netLineQty = (float)$line->quantity - $returnedLineQty;
                        $netLineTotal = $netLineQty * (float)$line->unit_cost;

                        return [
                            'id' => $line->id,
                            'category' => $line->category,
                            'item_name' => $line->product_name_snapshot,
                            'quantity' => (float)$line->quantity,
                            'net_quantity' => $netLineQty,
                            'unit_price' => (float)$line->unit_cost,
                            'total_price' => (float)$line->total_line_cost,
                            'net_total_price' => $netLineTotal,
                            'returned_qty' => $returnedLineQty
                        ];
                    }),
                    'payments' => $purch->payments
                ];
            });

        $all_payments = SupplierPayment::withoutGlobalScopes()->with('purchase:id,reference_invoice')
            ->where('supplier_id', $id)
            ->latest()
            ->get();

        $totalReturnsQuery = \App\Models\PurchaseReturn::withoutGlobalScopes()->whereHas('purchase', function($q) use($id) { 
            $q->where('supplier_id', $id); 
        });

        $summary = [
            'total_panels' => \App\Models\PurchaseLine::whereHas('purchase', function($q) use($id) { $q->withoutGlobalScopes()->where('supplier_id', $id); })->whereIn('category', ['mdf', 'panel'])->sum('quantity') - (clone $totalReturnsQuery)->whereHas('line', function($q){ $q->whereIn('category', ['mdf', 'panel']); })->sum('returned_quantity'),
            'total_cantos' => \App\Models\PurchaseLine::whereHas('purchase', function($q) use($id) { $q->withoutGlobalScopes()->where('supplier_id', $id); })->where('category', 'canto')->sum('quantity') - (clone $totalReturnsQuery)->whereHas('line', function($q){ $q->where('category', 'canto'); })->sum('returned_quantity'),
            'total_paid_global' => $all_payments->sum('amount')
        ];

        return response()->json([
            'purchases' => $purchases,
            'all_payments' => $all_payments,
            'summary' => $summary
        ]);
    }

    public function paySupplier(Request $request, $id) {
        $request->validate([
            'amount' => 'required|numeric|min:0.1',
            'payment_method' => 'required|string',
            'cash_date' => 'nullable|date',
            'purchase_id' => 'nullable|exists:purchases,id'
        ]);

        return DB::transaction(function() use ($request, $id) {
            $supplier = Supplier::withoutGlobalScopes()->lockForUpdate()->findOrFail($id);
            $amountToDistribute = $request->amount;

            if ($request->filled('purchase_id')) {
                $purch = Purchase::withoutGlobalScopes()->with('returns')->where('supplier_id', $id)->findOrFail($request->purchase_id);
                $netAmount = (float)$purch->total_amount - (float)$purch->returns->sum('refund_amount');
                $reste = round($netAmount - (float)$purch->amount_paid, 2);
                
                if (bccomp((string)$amountToDistribute, (string)$reste, 2) === 1) {
                    return response()->json(['error' => 'Le montant dépasse le reste à payer de cette facture.'], 400);
                }

                SupplierPayment::create([
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purch->id,
                    'amount' => $amountToDistribute,
                    'payment_method' => $request->payment_method,
                    'cash_date' => ($request->payment_method === 'check') ? $request->cash_date : null,
                    'status' => ($request->payment_method === 'check' && $request->cash_date) ? 'pending' : 'cashed'
                ]);

                $purch->increment('amount_paid', $amountToDistribute);
                $supplier->decrement('total_debt', $amountToDistribute);

                return response()->json(['success' => true]);
            }

            if (bccomp((string)$amountToDistribute, (string)$supplier->total_debt, 2) === 1) {
                return response()->json(['error' => 'Le montant dépasse la dette globale du fournisseur.'], 400);
            }

            $unpaidPurchases = Purchase::withoutGlobalScopes()->where('supplier_id', $id)
                ->with('returns')
                ->get()
                ->filter(function($p) {
                    $net = (float)$p->total_amount - (float)$p->returns->sum('refund_amount');
                    return bccomp((string)$net, (string)$p->amount_paid, 2) === 1;
                })
                ->sortBy('created_at');

            foreach ($unpaidPurchases as $purch) {
                if ($amountToDistribute <= 0.01) break;
                
                $netAmount = (float)$purch->total_amount - (float)$purch->returns->sum('refund_amount');
                $reste = $netAmount - (float)$purch->amount_paid;
                $payForThis = min($amountToDistribute, $reste);

                if ($payForThis > 0.01) {
                    SupplierPayment::create([
                        'supplier_id' => $supplier->id,
                        'purchase_id' => $purch->id,
                        'amount' => $payForThis,
                        'payment_method' => $request->payment_method,
                        'cash_date' => ($request->payment_method === 'check') ? $request->cash_date : null,
                        'status' => ($request->payment_method === 'check' && $request->cash_date) ? 'pending' : 'cashed'
                    ]);

                    $purch->increment('amount_paid', $payForThis);
                    $amountToDistribute -= $payForThis;
                }
            }

            if ($amountToDistribute > 0) {
                 SupplierPayment::create([
                    'supplier_id' => $supplier->id,
                    'purchase_id' => null,
                    'amount' => $amountToDistribute,
                    'payment_method' => $request->payment_method,
                    'cash_date' => ($request->payment_method === 'check') ? $request->cash_date : null,
                    'status' => ($request->payment_method === 'check' && $request->cash_date) ? 'pending' : 'cashed'
                ]);
            }

            $purchases = Purchase::withoutGlobalScopes()
                ->where('supplier_id', $id)
                ->with('returns')
                ->get();
            $totalNet = $purchases->sum(fn ($p) => (float) $p->total_amount - (float) $p->returns->sum('refund_amount'));
            $totalPaid = SupplierPayment::withoutGlobalScopes()->where('supplier_id', $id)->sum('amount');
            $supplier->update(['total_debt' => max(0, round($totalNet - $totalPaid, 2))]);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Recalculate supplier debt from actual purchase data.
     * Fixes any desync between total_debt and real unpaid amounts.
     */
    public function recalculateDebt($id) {
        return DB::transaction(function() use ($id) {
            $supplier = Supplier::withoutGlobalScopes()->lockForUpdate()->findOrFail($id);
            
            // 1. Get all purchases and their net amounts (accounting for returns)
            $purchases = Purchase::withoutGlobalScopes()->where('supplier_id', $id)
                ->with('returns')
                ->orderBy('created_at', 'asc')
                ->get();

            // 2. Get total amount actually paid by the supplier
            $totalPaid = \App\Models\SupplierPayment::withoutGlobalScopes()->where('supplier_id', $id)->sum('amount');
            
            $tempPaid = $totalPaid;
            $totalNetToPay = 0;

            // 3. Reset amount_paid on all purchases and re-distribute the total paid amount
            foreach ($purchases as $purch) {
                $netForThis = (float)$purch->total_amount - (float)$purch->returns->sum('refund_amount');
                $totalNetToPay += $netForThis;

                $payForThis = min($tempPaid, $netForThis);
                $purch->update(['amount_paid' => round($payForThis, 2)]);
                $tempPaid -= $payForThis;
            }

            $correctedDebt = max(0, round($totalNetToPay - $totalPaid, 2));
            $oldDebt = $supplier->total_debt;
            $supplier->update(['total_debt' => $correctedDebt]);

            return response()->json([
                'success' => true,
                'old_debt' => $oldDebt,
                'new_debt' => $correctedDebt,
                'message' => "Dette recalculée en profondeur et synchronisée avec vos règlements."
            ]);
        });
    }
}
