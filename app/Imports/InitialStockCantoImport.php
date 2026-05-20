<?php
 
namespace App\Imports;
 
use App\Models\StockCanto;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
 
class InitialStockCantoImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    protected $tenantId;
 
    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }
 
    public function model(array $row)
    {
        // Validation: skip if code is missing
        if (empty($row['code'])) {
            return null;
        }

        // mapping 'code' to 'color_code' and 'nom' to 'name'
        $canto = StockCanto::firstOrNew([
            'tenant_id' => $this->tenantId,
            'color_code' => trim($row['code']),
            'name' => $row['nom'] ?? $row['name'] ?? 'BANDCHANT',
        ]);
 
        $canto->provider_catalog = $row['marque'] ?? $row['catalogue'] ?? $canto->provider_catalog ?? 'STOCK INITIAL';
        
        // Setting required fields if new
        if (!$canto->exists) {
            $canto->width_mm = $row['largeur'] ?? $row['width'] ?? 22;
            $canto->thickness_mm = $row['epaisseur'] ?? $row['thickness'] ?? 0.8;
        }
        
        $canto->total_length_remaining = ($canto->total_length_remaining ?? 0) + (float) ($row['metrage'] ?? $row['quantite'] ?? $row['qty'] ?? 0);
        $canto->cost_price_per_m = (float) ($row['prix_achat'] ?? $row['prix'] ?? 0);
        $canto->base_price_sell_per_m = (float) ($row['prix_vente'] ?? 0);
        
        // Custom activity log description for Opening Stock
        activity()->withoutLogs(function () use ($canto) {
            $canto->save();
        });
        
        activity()
            ->performedOn($canto)
            ->withProperties([
                'type' => 'STOCK_INITIAL', 
                'added_length' => $row['metrage'] ?? $row['quantite'] ?? $row['qty'] ?? 0,
                'source' => 'Excel Import'
            ])
            ->log('Stock initial (Canto) importé via Excel');
 
        return $canto;
    }
}
