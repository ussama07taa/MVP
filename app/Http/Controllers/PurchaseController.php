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
            
            $tenantId = auth()->user()->tenant_id;

            $documentPath = null;
            if ($request->hasFile('invoice_document')) {
                $file = $request->file('invoice_document');
                $documentPath = $file->store('invoices', 'public');
            }

            $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;

            $purchase = Purchase::create([
                'tenant_id' => $tenantId,
                'supplier_id' => $request->supplier_id,
                'reference_invoice' => $request->reference_invoice,
                'total_amount' => $request->total_amount,
                'amount_paid' => $request->amount_paid,
                'document_path' => $documentPath
            ]);

            if ($request->amount_paid < $request->total_amount) {
                $debt = $request->total_amount - $request->amount_paid;
                Supplier::withoutGlobalScopes()->whereId($request->supplier_id)->lockForUpdate()->increment('total_debt', $debt);
            }
            if ($request->amount_paid > 0) {
                 SupplierPayment::create([
                    'tenant_id' => $tenantId,
                    'supplier_id' => $request->supplier_id, 
                    'purchase_id' => $purchase->id, 
                    'amount' => $request->amount_paid, 
                    'payment_method' => $request->payment_method ?? 'cash'
                 ]);
            }

            foreach($items as $item) {
                $qtyAdded = (float)($item['data']['quantity'] ?? ($item['data']['total_length_remaining'] ?? 1));
                $newUnitCost = (float)($item['data']['cost_price'] ?? ($item['data']['cost_price_per_m'] ?? ($item['data']['unit_cost'] ?? 0)));
                $totalLineCost = $qtyAdded * $newUnitCost;
                $newSellPrice = (float)($item['data']['base_price_sell'] ?? ($item['data']['base_price_sell_per_m'] ?? 0));
                
                $productName = "Article {$item['category']}";
                if ($item['category'] === 'mdf' || $item['category'] === 'panel') {
                    $productName = "MDF " . ($item['data']['type'] ?? '') . " " . ($item['data']['color_name'] ?? '') . " " . ($item['data']['size_x'] ?? '') . "x" . ($item['data']['size_y'] ?? '');
                } elseif ($item['category'] === 'canto') {
                    $productName = "Canto " . ($item['data']['color_name'] ?? '') . " " . ($item['data']['width_mm'] ?? '') . "mm";
                } elseif ($item['category'] === 'consumable') {
                    $productName = $item['data']['name'] ?? 'Consommable';
                }

                \App\Models\PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'category' => $item['category'],
                    'product_name_snapshot' => $productName,
                    'quantity' => $qtyAdded,
                    'unit_cost' => $newUnitCost,
                    'unit_sell_price' => $newSellPrice, 
                    'total_line_cost' => $totalLineCost
                ]);

                if ($item['category'] === 'mdf' || $item['category'] === 'panel') {
                    if (!empty($item['data']['existing_id'])) {
                        $this->stockService->recordPanelPurchase(
                            $item['data']['existing_id'], 
                            $qtyAdded, 
                            $newUnitCost, 
                            $newSellPrice, 
                            $purchase->id, 
                            $request->supplier_id
                        );
                    } else {
                        $panelData = collect($item['data'])->only([
                            'type', 'size_x', 'size_y', 'thickness', 'color_code', 'color_name',
                            'finish_type', 'provider_catalog', 'quantity', 'cost_price', 'base_price_sell'
                        ])->toArray();

                        StockPanel::create(array_merge($panelData, [
                            'tenant_id' => $tenantId,
                            'supplier_id' => $request->supplier_id,
                            'purchase_id' => $purchase->id
                        ]));
                    }
                } elseif ($item['category'] === 'canto') {
                    if (!empty($item['data']['existing_id'])) {
                        $this->stockService->recordCantoPurchase(
                            $item['data']['existing_id'], 
                            $qtyAdded, 
                            $newUnitCost, 
                            $newSellPrice, 
                            $purchase->id, 
                            $request->supplier_id
                        );
                    } else {
                        $cantoData = collect($item['data'])->only([
                            'name', 'color_code', 'color_name', 'finish_type', 'provider_catalog', 'width_mm', 
                            'thickness_mm', 'total_length_remaining', 'cost_price_per_m', 'base_price_sell_per_m'
                        ])->toArray();

                        StockCanto::create(array_merge($cantoData, [
                            'tenant_id' => $tenantId,
                            'supplier_id' => $request->supplier_id,
                            'purchase_id' => $purchase->id
                        ]));
                    }
                } elseif ($item['category'] === 'consumable') {
                    $consumable = Consumable::withoutGlobalScopes()->firstOrCreate(
                        ['name' => $item['data']['name'], 'unit' => $item['data']['unit']],
                        ['tenant_id' => $tenantId, 'quantity_in_stock' => 0, 'average_cost_price' => 0]
                    );
                    
                    $this->stockService->recordConsumablePurchase(
                        $consumable->id,
                        $qtyAdded,
                        $newUnitCost,
                        $newSellPrice
                    );
                }
            }

            DB::commit();

            Cache::forget("tenant.{$tenantId}.panels");
            Cache::forget("tenant.{$tenantId}.cantos");

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
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'company_name' => 'nullable|string',
            'total_debt' => 'nullable|numeric'
        ]);
        
        $tenantId = auth()->user()->tenant_id;
        
        return Supplier::create(array_merge($data, ['tenant_id' => $tenantId]));
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

            if ($line->category === 'mdf') {
                $stockItem = StockPanel::withoutGlobalScopes()->where('purchase_id', $purchase->id)->lockForUpdate()->first();
                $availableQty = $stockItem ? (float)$stockItem->quantity : 0;
            } elseif ($line->category === 'canto') {
                $stockItem = StockCanto::withoutGlobalScopes()->where('purchase_id', $purchase->id)->lockForUpdate()->first();
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
                if ($line->category === 'mdf') {
                    $stockItem->decrement('quantity', $qtyToReturn);
                } elseif ($line->category === 'canto') {
                    $stockItem->decrement('total_length_remaining', $qtyToReturn);
                } else {
                    $stockItem->decrement('quantity_in_stock', $qtyToReturn);
                }
            }

            $supplier = Supplier::withoutGlobalScopes()->lockForUpdate()->findOrFail($purchase->supplier_id);
            $supplier->decrement('total_debt', $refundAmount);

            $tenantId = auth()->user()->tenant_id;
            Cache::forget("tenant.{$tenantId}.panels");
            Cache::forget("tenant.{$tenantId}.cantos");

            return response()->json([
                'success' => true,
                'message' => "Retour traité avec succès. Stock réduit et dette fournisseur diminuée de {$refundAmount} DH."
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
            'purchase_id' => 'nullable|exists:purchases,id'
        ]);

        return DB::transaction(function() use ($request, $id) {
            $supplier = Supplier::withoutGlobalScopes()->lockForUpdate()->findOrFail($id);
            $amountToDistribute = $request->amount;

            if ($request->filled('purchase_id')) {
                $purch = Purchase::withoutGlobalScopes()->where('supplier_id', $id)->findOrFail($request->purchase_id);
                $reste = $purch->total_amount - $purch->amount_paid;
                
                if ($amountToDistribute > $reste + 0.01) {
                    return response()->json(['error' => 'Le montant dépasse le reste à payer de cette facture.'], 400);
                }

                SupplierPayment::create([
                    'tenant_id' => $supplier->tenant_id,
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purch->id,
                    'amount' => $amountToDistribute,
                    'payment_method' => $request->payment_method
                ]);

                $purch->increment('amount_paid', $amountToDistribute);
                $supplier->decrement('total_debt', $amountToDistribute);

                return response()->json(['success' => true]);
            }

            if ($amountToDistribute > $supplier->total_debt + 0.01) {
                return response()->json(['error' => 'Le montant dépasse la dette du fournisseur.'], 400);
            }

            $unpaidPurchases = Purchase::withoutGlobalScopes()->where('supplier_id', $id)
                ->whereRaw('amount_paid < total_amount')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($unpaidPurchases as $purch) {
                if ($amountToDistribute <= 0) break;
                
                $reste = $purch->total_amount - $purch->amount_paid;
                $payForThis = min($amountToDistribute, $reste);

                SupplierPayment::create([
                    'tenant_id' => $supplier->tenant_id,
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purch->id,
                    'amount' => $payForThis,
                    'payment_method' => $request->payment_method
                ]);

                $purch->increment('amount_paid', $payForThis);
                $amountToDistribute -= $payForThis;
            }

            if ($amountToDistribute > 0) {
                 SupplierPayment::create([
                    'tenant_id' => $supplier->tenant_id,
                    'supplier_id' => $supplier->id,
                    'purchase_id' => null,
                    'amount' => $amountToDistribute,
                    'payment_method' => $request->payment_method
                ]);
            }

            $supplier->decrement('total_debt', $request->amount);

            return response()->json(['success' => true]);
        });
    }
}
