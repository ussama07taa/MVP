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
        if (empty($row['code'])) {
            return null;
        }

        $type = strtolower(trim((string) ($row['type'] ?? 'canto')));
        if (in_array($type, ['panel', 'mdf', 'panneau'], true)) {
            return null;
        }

        $canto = StockCanto::firstOrNew([
            'tenant_id' => $this->tenantId,
            'color_code' => trim($row['code']),
            'name' => $row['nom'] ?? $row['name'] ?? 'BANDCHANT',
        ]);

        $canto->provider_catalog = $row['marque'] ?? $row['catalogue'] ?? $canto->provider_catalog ?? 'STOCK INITIAL';

        if (!$canto->exists) {
            $canto->width_mm = $row['largeur'] ?? $row['width'] ?? 22;
            $canto->thickness_mm = $row['epaisseur'] ?? $row['thickness'] ?? 0.8;
        }

        $addedLength = $this->resolveAddedLength($row);
        if ($addedLength <= 0) {
            return null;
        }

        $canto->total_length_remaining = ($canto->total_length_remaining ?? 0) + $addedLength;
        $canto->cost_price_per_m = (float) ($row['prix_achat'] ?? $row['prix'] ?? 0);
        $canto->base_price_sell_per_m = (float) ($row['prix_vente'] ?? 0);

        activity()->withoutLogs(function () use ($canto) {
            $canto->save();
        });

        activity()
            ->performedOn($canto)
            ->withProperties([
                'type' => 'STOCK_INITIAL',
                'added_length' => $addedLength,
                'rolls' => $this->resolveRollCount($row),
                'meters_per_roll' => $this->resolveMetersPerRoll($row),
                'source' => 'Excel Import',
            ])
            ->log('Stock initial (Canto) importé via Excel');

        return $canto;
    }

    protected function resolveRollCount(array $row): int
    {
        return (int) ($row['rouleaux'] ?? $row['nombre_rouleaux'] ?? $row['rolls'] ?? $row['rouleau'] ?? 0);
    }

    protected function resolveMetersPerRoll(array $row): float
    {
        $explicit = (float) ($row['metrage_par_rouleau'] ?? $row['meters_per_roll'] ?? $row['metrage_rouleau'] ?? 0);
        if ($explicit > 0) {
            return $explicit;
        }

        $rolls = $this->resolveRollCount($row);
        $quantity = (float) ($row['metrage'] ?? $row['quantite'] ?? $row['qty'] ?? 0);

        if ($rolls > 0 && $quantity > 0) {
            return $quantity;
        }

        return 150;
    }

    protected function resolveAddedLength(array $row): float
    {
        $rolls = $this->resolveRollCount($row);
        $metersPerRoll = $this->resolveMetersPerRoll($row);
        $quantity = (float) ($row['metrage'] ?? $row['quantite'] ?? $row['qty'] ?? 0);

        if ($rolls > 0) {
            return $rolls * $metersPerRoll;
        }

        return $quantity;
    }
}
