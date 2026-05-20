<?php
 
namespace App\Imports;
 
use App\Models\StockPanel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
 
class InitialStockImport implements ToModel, WithHeadingRow, SkipsEmptyRows
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

        // mapping 'code' to 'color_code' and 'nom' to 'type'
        $panel = StockPanel::firstOrNew([
            'tenant_id' => $this->tenantId,
            'color_code' => trim($row['code']),
            'type' => $row['nom'] ?? $row['type'] ?? 'MDF',
        ]);
 
        $panel->provider_catalog = $row['marque'] ?? $row['catalogue'] ?? $panel->provider_catalog ?? 'STOCK INITIAL';
        
        // Setting required fields if new
        if (!$panel->exists) {
            $panel->size_x = $row['longueur'] ?? $row['format_x'] ?? 2800;
            $panel->size_y = $row['largeur'] ?? $row['format_y'] ?? 2070;
            $panel->thickness = $row['epaisseur'] ?? 18;
            $panel->finish_type = $row['finition'] ?? 'Standard';
        }
        
        $panel->quantity = ($panel->quantity ?? 0) + (int) ($row['quantite'] ?? $row['qty'] ?? 0);
        $panel->cost_price = (float) ($row['prix_achat'] ?? $row['prix'] ?? 0);
        $panel->base_price_sell = (float) ($row['prix_vente'] ?? 0);
        
        // Custom activity log description for Opening Stock
        activity()->withoutLogs(function () use ($panel) {
            $panel->save();
        });
        
        activity()
            ->performedOn($panel)
            ->withProperties([
                'type' => 'STOCK_INITIAL', 
                'added_quantity' => $row['quantite'] ?? $row['qty'] ?? 0,
                'source' => 'Excel Import'
            ])
            ->log('Stock initial importé via Excel');
 
        return $panel;
    }
}
