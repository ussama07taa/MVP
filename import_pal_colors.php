<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StockPanel;
use App\Models\StockCanto;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

$colors = [
    ['name' => 'White Halifax Oak', 'code' => 'H1176', 'finish' => 'ST37'],
    ['name' => 'Natural Halifax Oak', 'code' => 'H1180', 'finish' => 'ST37'],
    ['name' => 'Halifax Oak Tobacco', 'code' => 'H1181', 'finish' => 'ST37'],
    ['name' => 'Halifax Oak Satin Sand Grey', 'code' => 'H1336', 'finish' => 'ST37'],
    ['name' => 'Halifax Oak Pewter', 'code' => 'H3176', 'finish' => 'ST37'],
    ['name' => 'Halifax Oak Satin Black', 'code' => 'H3178', 'finish' => 'ST37'],
    ['name' => 'Cognac Brown Sherman Oak', 'code' => 'H1344', 'finish' => 'ST32'],
    ['name' => 'Gray Sherman Oak', 'code' => 'H1345', 'finish' => 'ST32'],
    ['name' => 'Oak Sherman Anthracite', 'code' => 'H1346', 'finish' => 'ST32'],
    ['name' => 'White Gladstone Oak', 'code' => 'H3335', 'finish' => 'ST28'],
    ['name' => 'Gladstone Oak Sand', 'code' => 'H3309', 'finish' => 'ST28'],
    ['name' => 'Gladstone Oak Tobacco', 'code' => 'H3325', 'finish' => 'ST28'],
    ['name' => 'Gray Gladstone Oak - Beige', 'code' => 'H3326', 'finish' => 'ST28'],
    ['name' => 'Gladstone Oak Sepia', 'code' => 'H3342', 'finish' => 'ST28'],
    ['name' => 'Orleans Oak Sand', 'code' => 'H1377', 'finish' => 'ST36'],
    ['name' => 'Orleans Brown Oak', 'code' => 'H1379', 'finish' => 'ST36'],
];

$supplier = Supplier::where('name', 'LIKE', '%Egger%')->first() ?? Supplier::first();
$tenantId = 1; // Assuming tenant 1

echo "Importing " . count($colors) . " colors for Supplier: " . ($supplier->name ?? 'None') . "\n";

foreach ($colors as $c) {
    // 1. Create MDF Panel
    StockPanel::updateOrCreate(
        ['color_code' => $c['code'], 'type' => 'MDF EGGER', 'tenant_id' => $tenantId],
        [
            'supplier_id' => $supplier->id ?? null,
            'provider_catalog' => 'PAL EGGER',
            'finish_type' => $c['finish'],
            'thickness' => 18,
            'size_x' => 2800,
            'size_y' => 2070,
            'quantity' => 10,
            'alert_threshold' => 2,
            'cost_price' => 750.00,
            'base_price_sell' => 950.00
        ]
    );

    // 2. Create Matching Canto
    StockCanto::updateOrCreate(
        ['color_code' => $c['code'], 'tenant_id' => $tenantId],
        [
            'supplier_id' => $supplier->id ?? null,
            'name' => 'Canto ' . $c['name'],
            'provider_catalog' => 'PAL EGGER',
            'finish_type' => $c['finish'],
            'width_mm' => 23,
            'thickness_mm' => 1,
            'total_length_remaining' => 100,
            'alert_threshold' => 20,
            'cost_price_per_m' => 4.50,
            'base_price_sell_per_m' => 7.00
        ]
    );
    
    echo "✓ Processed: {$c['name']} ({$c['code']})\n";
}

echo "Done!\n";
