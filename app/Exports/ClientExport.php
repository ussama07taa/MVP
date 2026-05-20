<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientExport implements FromQuery, WithHeadings, WithMapping
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function query()
    {
        return Client::query()->where('tenant_id', $this->tenantId);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nom',
            'Téléphone',
            'Crédit Total',
            'Créé le',
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->name,
            $client->phone,
            $client->total_credit,
            $client->created_at->format('d/m/Y'),
        ];
    }
}
