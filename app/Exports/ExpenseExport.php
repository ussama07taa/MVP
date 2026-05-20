<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpenseExport implements FromQuery, WithHeadings, WithMapping
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function query()
    {
        return Expense::query()->where('tenant_id', $this->tenantId);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Libellé',
            'Catégorie',
            'Montant',
            'Date',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->id,
            $expense->title,
            $expense->category,
            $expense->amount,
            $expense->date,
        ];
    }
}
