<?php

namespace App\Services;

use App\Models\{StockPanel, StockCanto, Consumable};
use Illuminate\Support\Facades\DB;

class StockService
{
    public function deductPanel($id, $qty)
    {
        $panel = StockPanel::whereId($id)->lockForUpdate()->firstOrFail();
        if ($panel->quantity < $qty) {
            throw new \Exception("Stock insuffisant pour le panneau ID: {$id}");
        }
        $panel->decrement('quantity', $qty);
        $this->deductFifoFromPurchaseLines('StockPanel', (int) $panel->id, (float) $qty);
        return $panel;
    }

    public function deductCanto($id, $qty)
    {
        $canto = StockCanto::whereId($id)->lockForUpdate()->firstOrFail();
        if ($canto->total_length_remaining < $qty) {
            throw new \Exception("Stock insuffisant pour le chant ID: {$id}");
        }
        $canto->decrement('total_length_remaining', $qty);
        $this->deductFifoFromPurchaseLines('StockCanto', (int) $canto->id, (float) $qty);
        return $canto;
    }

    public function restoreFifoToPurchaseLines(string $stockType, int $stockItemId, float $qty): void
    {
        $remaining = $qty;

        $lines = DB::table('purchase_lines')
            ->join('purchases', 'purchase_lines.purchase_id', '=', 'purchases.id')
            ->where('purchase_lines.stock_item_id', $stockItemId)
            ->where('purchase_lines.stock_item_type', $stockType)
            ->whereNotNull('purchase_lines.quantity_remaining')
            ->orderBy('purchases.created_at', 'desc')
            ->lockForUpdate()
            ->get(['purchase_lines.id', 'purchase_lines.quantity', 'purchase_lines.quantity_remaining']);

        foreach ($lines as $line) {
            if ($remaining <= 0) {
                break;
            }

            $capacity = (float) $line->quantity - (float) $line->quantity_remaining;
            if ($capacity <= 0) {
                continue;
            }

            $restore = min($remaining, $capacity);

            DB::table('purchase_lines')
                ->where('id', $line->id)
                ->increment('quantity_remaining', $restore);

            $remaining -= $restore;
        }
    }

    public function deductFifoFromPurchaseLines(string $stockType, int $stockItemId, float $qty): void
    {
        $remaining = $qty;

        $lines = DB::table('purchase_lines')
            ->join('purchases', 'purchase_lines.purchase_id', '=', 'purchases.id')
            ->where('purchase_lines.stock_item_id', $stockItemId)
            ->where('purchase_lines.stock_item_type', $stockType)
            ->where('purchase_lines.quantity_remaining', '>', 0)
            ->orderBy('purchases.created_at', 'asc')
            ->lockForUpdate()
            ->get(['purchase_lines.id', 'purchase_lines.quantity_remaining']);

        foreach ($lines as $line) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $line->quantity_remaining;
            $take = min($remaining, $available);

            DB::table('purchase_lines')
                ->where('id', $line->id)
                ->decrement('quantity_remaining', $take);

            $remaining -= $take;
        }
    }

    public function deductConsumable($id, $qty)
    {
        $consumable = Consumable::whereId($id)->lockForUpdate()->firstOrFail();
        if ($consumable->quantity_in_stock < $qty) {
            throw new \Exception("Stock insuffisant pour le consommable ID: {$id}");
        }
        $consumable->decrement('quantity_in_stock', $qty);
        return $consumable;
    }
    public function recordPanelPurchase($id, $qtyAdded, $newUnitCost, $newSellPrice, $purchaseId, $supplierId)
    {
        $panel = StockPanel::whereId($id)->lockForUpdate()->firstOrFail();
        
        $oldQty = (float) $panel->quantity;
        $oldCost = (float) $panel->cost_price;
        $newTotalQty = $oldQty + $qtyAdded;

        // CUMP Calculation: ((Old Qty * Old Cost) + (New Qty * New Cost)) / (Old Qty + New Qty)
        $cump = $newTotalQty > 0 
            ? (($oldQty * $oldCost) + ($qtyAdded * $newUnitCost)) / $newTotalQty 
            : $newUnitCost;

        $panel->update([
            'quantity' => $newTotalQty,
            'cost_price' => $cump,
            'base_price_sell' => $newSellPrice > 0 ? $newSellPrice : $panel->base_price_sell,
            'purchase_id' => $purchaseId,
            'supplier_id' => $supplierId
        ]);

        return $panel;
    }

    public function recordCantoPurchase($id, $qtyAdded, $newUnitCost, $newSellPrice, $purchaseId, $supplierId)
    {
        $canto = StockCanto::whereId($id)->lockForUpdate()->firstOrFail();
        
        $oldQty = (float) $canto->total_length_remaining;
        $oldCost = (float) $canto->cost_price_per_m;
        $newTotalQty = $oldQty + $qtyAdded;

        $cump = $newTotalQty > 0 
            ? (($oldQty * $oldCost) + ($qtyAdded * $newUnitCost)) / $newTotalQty 
            : $newUnitCost;

        $canto->update([
            'total_length_remaining' => $newTotalQty,
            'cost_price_per_m' => $cump,
            'base_price_sell_per_m' => $newSellPrice > 0 ? $newSellPrice : $canto->base_price_sell_per_m,
            'purchase_id' => $purchaseId,
            'supplier_id' => $supplierId
        ]);

        return $canto;
    }

    public function recordConsumablePurchase($id, $qtyAdded, $newUnitCost, $newSellPrice)
    {
        $consumable = Consumable::whereId($id)->lockForUpdate()->firstOrFail();

        $currentQty = (float) $consumable->quantity_in_stock;
        $currentAvg = (float) $consumable->average_cost_price;
        $newTotalQty = $currentQty + $qtyAdded;

        $newAvg = $newTotalQty > 0 
            ? (($currentQty * $currentAvg) + ($qtyAdded * $newUnitCost)) / $newTotalQty 
            : $newUnitCost;

        $consumable->update([
            'quantity_in_stock' => $newTotalQty,
            'average_cost_price' => $newAvg,
            'base_price_sell' => $newSellPrice > 0 ? $newSellPrice : $consumable->base_price_sell
        ]);

        return $consumable;
    }
}
