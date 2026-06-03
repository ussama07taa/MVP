<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\Invoice;

$invoices = Invoice::withTrashed()->get();
echo "Total Invoices: " . $invoices->count() . PHP_EOL;
foreach ($invoices as $i) {
    echo "{$i->invoice_number} | Status: {$i->status} | Tenant: {$i->tenant_id}" . PHP_EOL;
}
