<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockPanel;
use App\Models\StockCanto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Http\Requests\StoreStockPanelRequest;
use App\Http\Requests\StoreStockCantoRequest;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Imports\InitialStockImport;
use App\Imports\InitialStockCantoImport;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller 
{
    public function panels()
    {
        return StockPanel::withoutGlobalScopes()->with('supplier')->latest()->get();
    }

    public function showPanel($id)
    {
        return StockPanel::withoutGlobalScopes()->with('supplier')->findOrFail($id);
    }

    public function posPanels()
    {
        return StockPanel::withoutGlobalScopes()->select('id', 'type', 'finish_type', 'color_code', 'color_name', 'provider_catalog', 'size_x', 'size_y', 'thickness', 'quantity', 'base_price_sell')
                ->where('quantity', '>', 0)
                ->paginate(50);
    }

    public function updatePanel(StoreStockPanelRequest $request, $id)
    {
        $panel = StockPanel::withoutGlobalScopes()->findOrFail($id);
        $panel->update($request->validated());
        
        $tenantId = auth()->user()->tenant_id;
        Cache::forget("tenant.{$tenantId}.panels");
        Cache::forget("tenant.{$tenantId}.pos_panels_paged");
        
        return $panel;
    }

    public function cantos()
    {
        return StockCanto::withoutGlobalScopes()->with('supplier')->latest()->get();
    }

    public function posCantos()
    {
        return StockCanto::withoutGlobalScopes()->select('id', 'name', 'color_code', 'color_name', 'finish_type', 'provider_catalog', 'width_mm', 'thickness_mm', 'total_length_remaining', 'base_price_sell_per_m')
                ->where('total_length_remaining', '>', 0)
                ->paginate(50);
    }

    public function updateCanto(StoreStockCantoRequest $request, $id)
    {
        $canto = StockCanto::withoutGlobalScopes()->findOrFail($id);
        $canto->update($request->validated());

        $tenantId = auth()->user()->tenant_id;
        Cache::forget("tenant.{$tenantId}.cantos");
        Cache::forget("tenant.{$tenantId}.pos_cantos_paged");

        return $canto;
    }

    public function getProductBatches($productId) 
    {
        $panel = StockPanel::withoutGlobalScopes()->findOrFail($productId);
        
        $batches = DB::table('purchase_lines')
            ->join('purchases', 'purchase_lines.purchase_id', '=', 'purchases.id')
            ->where('purchase_lines.product_name_snapshot', $panel->type)
            ->where('purchase_lines.category', 'mdf')
            ->select(
                'purchase_lines.id', 
                'purchase_lines.quantity as available',
                'purchase_lines.unit_sell_price as price',
                'purchases.created_at'
            )
            ->orderBy('purchases.created_at', 'asc')
            ->get();
            
        return response()->json($batches);
    }

    public function adjustStock(StoreInventoryAdjustmentRequest $request)
    {
        $validated = $request->validated();
        $validated['quantity_adjusted'] = -$validated['quantity'];


        $modelClass = "App\\Models\\" . $validated['item_type'];
        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Type d\'article invalide'], 422);
        }

        return DB::transaction(function () use ($validated, $modelClass) {
            $item = $modelClass::withoutGlobalScopes()->lockForUpdate()->findOrFail($validated['item_id']);
            
            \App\Models\InventoryAdjustment::create([
                'tenant_id' => auth()->user()->tenant_id,
                'item_id' => $item->id,
                'item_type' => $modelClass,
                'purchase_line_id' => $validated['purchase_line_id'] ?? null,
                'quantity_adjusted' => -$validated['quantity'],
                'reason' => $validated['reason'],
                'user_id' => auth()->id()
            ]);

            if ($validated['item_type'] === 'StockCanto') {
                $item->total_length_remaining -= $validated['quantity'];
            } elseif ($validated['item_type'] === 'Consumable') {
                $item->quantity_in_stock -= $validated['quantity'];
            } else {
                $item->quantity -= $validated['quantity'];
            }
            $item->save();
            
            $tenantId = auth()->user()->tenant_id;
            Cache::forget("tenant.{$tenantId}.panels");
            Cache::forget("tenant.{$tenantId}.cantos");
            Cache::forget("tenant.{$tenantId}.pos_panels_paged");
            Cache::forget("tenant.{$tenantId}.pos_cantos_paged");

            if (!empty($validated['purchase_line_id'])) {
                $batch = \App\Models\PurchaseLine::withoutGlobalScopes()->lockForUpdate()->find($validated['purchase_line_id']);
                $batch->quantity -= $validated['quantity'];
                $batch->save();
            }

            return response()->json(['success' => true, 'message' => 'Stock ajusté avec succès.']);
        });
    }

    public function importInitialStock(Request $request, $type = 'panel')
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $importClass = match($type) {
                'canto' => new InitialStockCantoImport(auth()->user()->tenant_id),
                default => new InitialStockImport(auth()->user()->tenant_id),
            };

            Excel::import($importClass, $request->file('file'));
            
            $tenantId = auth()->user()->tenant_id;
            Cache::forget("tenant.{$tenantId}.pos_panels_paged");
            Cache::forget("tenant.{$tenantId}.pos_cantos_paged");

            return response()->json(['success' => true, 'message' => 'Stock initial importé avec succès !']);
        } catch (\Exception $e) {
            \Log::error("Import Error ({$type}): " . $e->getMessage(), [
                'exception' => $e,
                'file' => $request->file('file')->getClientOriginalName()
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'importation : ' . $e->getMessage()], 500);
        }
    }
}
