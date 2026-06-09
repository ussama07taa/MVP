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
        $panels = StockPanel::withoutGlobalScopes()->with('supplier')->latest()->get();
        if (auth()->user()->role !== 'admin') {
            $panels->transform(function($p) {
                $p->cost_price = null;
                return $p;
            });
        }
        return $panels;
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
        
        Cache::forget("global.panels");
        Cache::forget("global.pos_panels_paged");
        
        return $panel;
    }

    public function cantos()
    {
        $cantos = StockCanto::withoutGlobalScopes()->with('supplier')->latest()->get();
        if (auth()->user()->role !== 'admin') {
            $cantos->transform(function($c) {
                $c->cost_price_per_m = null;
                return $c;
            });
        }
        return $cantos;
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

        Cache::forget("global.cantos");
        Cache::forget("global.pos_cantos_paged");

        return $canto;
    }

    public function getProductBatches($productId) 
    {
        $panel = StockPanel::withoutGlobalScopes()->findOrFail($productId);

        if ($panel->quantity <= 0) {
            return response()->json([]);
        }

        $batches = DB::table('purchase_lines')
            ->join('purchases', 'purchase_lines.purchase_id', '=', 'purchases.id')
            ->where('purchase_lines.stock_item_id', $panel->id)
            ->where('purchase_lines.stock_item_type', 'StockPanel')
            ->whereIn('purchase_lines.category', ['mdf', 'panel'])
            ->where('purchase_lines.quantity_remaining', '>', 0)
            ->select(
                'purchase_lines.id',
                'purchase_lines.quantity_remaining as available',
                DB::raw('COALESCE(purchase_lines.unit_sell_price, ' . (float) $panel->base_price_sell . ') as price'),
                'purchases.created_at'
            )
            ->orderBy('purchases.created_at', 'asc')
            ->get();

        if ($batches->isEmpty()) {
            return response()->json([[
                'id' => 'stock_' . $panel->id,
                'available' => (float) $panel->quantity,
                'price' => (float) $panel->base_price_sell,
                'created_at' => $panel->created_at,
            ]]);
        }

        return response()->json($batches);
    }

    public function adjustStock(StoreInventoryAdjustmentRequest $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);
        $validated = $request->validated();
        $validated['quantity_adjusted'] = -$validated['quantity'];


        $modelClass = "App\\Models\\" . $validated['item_type'];
        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Type d\'article invalide'], 422);
        }

        return DB::transaction(function () use ($validated, $modelClass) {
            $item = $modelClass::withoutGlobalScopes()->lockForUpdate()->findOrFail($validated['item_id']);
            
            \App\Models\InventoryAdjustment::create([
                'tenant_id' => 1,
                'item_id' => $item->id,
                'item_type' => $modelClass,
                'purchase_line_id' => $validated['purchase_line_id'] ?? null,
                'quantity_adjusted' => -$validated['quantity'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id()
            ]);

            $qty = (float) $validated['quantity'];

            if ($validated['item_type'] === 'StockCanto') {
                if ($item->total_length_remaining < $qty) {
                    return response()->json(['error' => 'Stock insuffisant pour cet ajustement.'], 422);
                }
                $item->total_length_remaining -= $qty;
            } elseif ($validated['item_type'] === 'Consumable') {
                if ($item->quantity_in_stock < $qty) {
                    return response()->json(['error' => 'Stock insuffisant pour cet ajustement.'], 422);
                }
                $item->quantity_in_stock -= $qty;
            } else {
                if ($item->quantity < $qty) {
                    return response()->json(['error' => 'Stock insuffisant pour cet ajustement.'], 422);
                }
                $item->quantity -= $qty;
            }
            $item->save();
            
            Cache::forget("global.panels");
            Cache::forget("global.cantos");
            Cache::forget("global.pos_panels_paged");
            Cache::forget("global.pos_cantos_paged");

            if (!empty($validated['purchase_line_id'])) {
                $batch = \App\Models\PurchaseLine::withoutGlobalScopes()->lockForUpdate()->find($validated['purchase_line_id']);
                $batch->quantity -= $validated['quantity'];
                $batch->save();
            }

            return response()->json(['success' => true, 'message' => 'Stock ajusté avec succès.']);
        });
    }

    public function adjustmentHistory()
    {
        if (auth()->user()->role !== 'admin') abort(403);
        return \App\Models\InventoryAdjustment::withoutGlobalScopes()
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($adj) {
                // Manually load item without global scopes to ensure it's found
                $itemModel = $adj->item_type;
                $item = $itemModel::withoutGlobalScopes()->find($adj->item_id);
                $adj->item_details = $item;
                return $adj;
            });
    }

    public function importInitialStock(Request $request, $type = 'panel')
    {
        if (auth()->user()->role !== 'admin') abort(403);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $importClass = match($type) {
                'canto' => new InitialStockCantoImport(1),
                default => new InitialStockImport(1),
            };

            Excel::import($importClass, $request->file('file'));
            
            Cache::forget("global.pos_panels_paged");
            Cache::forget("global.pos_cantos_paged");

            return response()->json(['success' => true, 'message' => 'Stock initial importé avec succès !']);
        } catch (\Exception $e) {
            \Log::error("Import Error ({$type}): " . $e->getMessage(), [
                'exception' => $e,
                'file' => $request->file('file')->getClientOriginalName()
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'importation : ' . $e->getMessage()], 500);
        }
    }

    public function destroyPanel($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);
        StockPanel::withoutGlobalScopes()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function destroyCanto($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);
        StockCanto::withoutGlobalScopes()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
