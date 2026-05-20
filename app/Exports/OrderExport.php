<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderExport implements FromQuery, WithHeadings, WithMapping
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function query()
    {
        return Order::query()->where('tenant_id', $this->tenantId)->with('client');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Client',
            'Total Vente',
            'Montant Payé',
            'Statut',
            'Date',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->client->name ?? 'Passager',
            $order->total_sell_price,
            $order->amount_paid,
            $order->status,
            $order->created_at->format('d/m/Y H:i'),
        ];
    }
}
