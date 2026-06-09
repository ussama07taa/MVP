<?php

namespace App\Services;

use App\Models\{StockPanel, StockCanto, Consumable, Service};

class PricingService
{
    public function resolveCheckoutUnitSell(array $item): float
    {
        return match ($item['type']) {
            'panel' => round((float) StockPanel::findOrFail($item['id'])->base_price_sell, 2),
            'canto' => round((float) StockCanto::findOrFail($item['id'])->base_price_sell_per_m, 2),
            'consumable' => round((float) Consumable::findOrFail($item['id'])->base_price_sell, 2),
            'service' => round((float) Service::findOrFail($item['id'])->unit_price, 2),
            'custom_labor' => round(max(0, (float) ($item['unit_price'] ?? 0)), 2),
            default => throw new \InvalidArgumentException("Type d'article inconnu: {$item['type']}"),
        };
    }

    public function resolveCantoCollagePrice(): float
    {
        $service = Service::where('name', 'like', '%collage%')
            ->orWhere('name', 'like', '%chant%')
            ->orWhere('name', 'like', '%coupe%')
            ->first();

        return $service ? round((float) $service->unit_price, 2) : 2.00;
    }

    public function resolveInvoiceUnitSell(array $item): float
    {
        $type = $item['item_type'] ?? null;
        $id = $item['item_id'] ?? null;

        if ($type === 'stock_panel' && $id) {
            return round((float) StockPanel::findOrFail($id)->base_price_sell, 2);
        }

        if ($type === 'stock_canto' && $id) {
            return round((float) StockCanto::findOrFail($id)->base_price_sell_per_m, 2);
        }

        if ($type === 'service' && $id) {
            return round((float) Service::findOrFail($id)->unit_price, 2);
        }

        return round(max(0, (float) ($item['unit_price'] ?? 0)), 2);
    }
}
