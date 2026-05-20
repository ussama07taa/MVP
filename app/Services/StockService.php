<?php

namespace App\Services;

use App\Models\{StockPanel, StockCanto, Consumable};

class StockService
{
    public function deductPanel($id, $qty)
    {
        $panel = StockPanel::whereId($id)->lockForUpdate()->firstOrFail();
        if ($panel->quantity < $qty) {
            throw new \Exception("Stock insuffisant pour le panneau ID: {$id}");
        }
        $panel->decrement('quantity', $qty);
        return $panel;
    }

    public function deductCanto($id, $qty)
    {
        $canto = StockCanto::whereId($id)->lockForUpdate()->firstOrFail();
        if ($canto->total_length_remaining < $qty) {
            throw new \Exception("Stock insuffisant pour le chant ID: {$id}");
        }
        $canto->decrement('total_length_remaining', $qty);
        return $canto;
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
