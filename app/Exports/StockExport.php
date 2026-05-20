<?php

namespace App\Exports;

use App\Models\StockPanel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockExport implements FromQuery, WithHeadings, WithMapping
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function query()
    {
        return StockPanel::query()->where('tenant_id', $this->tenantId);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Dimensions',
            'Épaisseur',
            'Couleur',
            'Quantité',
            'Prix de Vente',
        ];
    }

    public function map($panel): array
    {
        return [
            $panel->id,
            $panel->type,
            $panel->size_x . 'x' . $panel->size_y,
            $panel->thickness,
            $panel->color_name ?? $panel->color_code,
            $panel->quantity,
            $panel->base_price_sell,
        ];
    }
}
