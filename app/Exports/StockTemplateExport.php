<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'type',
            'code',
            'nom',
            'marque',
            'finition',
            'quantite',
            'prix_achat',
            'prix_vente',
            'longueur',
            'largeur',
            'epaisseur'
        ];
    }

    public function array(): array
    {
        return [
            [
                'panel',
                'K689 RW',
                'MDF',
                'Kronospan',
                'Capella',
                '50',
                '350',
                '450',
                '2800',
                '2070',
                '18'
            ],
            [
                'canto',
                'B-K689',
                'BANDCHANT',
                'Kronospan',
                'Standard',
                '250',
                '1.5',
                '3.5',
                '',
                '22',
                '0.8'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1e293b']]],
        ];
    }
}
