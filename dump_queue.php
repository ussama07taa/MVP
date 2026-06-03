<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\WorkshopQueue;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$queue = WorkshopQueue::where('status', '!=', 'delivered')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'client_name', 'status', 'created_at', 'is_hidden_from_workshop']);

echo "ID | Client Name | Status | Created At | Hidden\n";
echo "---|-------------|--------|------------|-------\n";
foreach ($queue as $q) {
    echo "{$q->id} | {$q->client_name} | {$q->status} | {$q->created_at} | " . ($q->is_hidden_from_workshop ? 'YES' : 'NO') . "\n";
}
