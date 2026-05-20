<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfitExport implements FromArray, WithHeadings, WithTitle
{
    protected $data;
    protected $monthName;

    public function __construct(array $data, $monthName)
    {
        $this->data = $data;
        $this->monthName = $monthName;
    }

    public function array(): array
    {
        return [
            ['Chiffre d\'Affaires (Revenue)', $this->data['revenue'] . ' DH'],
            ['Coût des Marchandises (COGS)', $this->data['cogs'] . ' DH'],
            ['Marge Brute', $this->data['gross_margin'] . ' DH'],
            ['Charges Opérationnelles (OPEX)', $this->data['opex'] . ' DH'],
            ['Bénéfice Net', $this->data['net_profit'] . ' DH'],
            ['Pourcentage de Marge', ($this->data['margin_percentage'] ?? 0) . '%'],
            ['Encaissé (Cash)', $this->data['cash_collected'] . ' DH'],
            ['Crédit Client', $this->data['unpaid_revenue'] . ' DH'],
        ];
    }

    public function headings(): array
    {
        return [
            'Indicateur',
            'Valeur',
        ];
    }

    public function title(): string
    {
        return 'Rapport ' . $this->monthName;
    }
}
