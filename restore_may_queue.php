<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\WorkshopQueue;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$affected = WorkshopQueue::where('status', '!=', 'delivered')
    ->where('is_hidden_from_workshop', true)
    ->whereMonth('created_at', 5)
    ->update(['is_hidden_from_workshop' => false]);

echo "Successfully unhidden {$affected} jobs from May.\n";

$pending = WorkshopQueue::where('status', '!=', 'delivered')
    ->where('is_hidden_from_workshop', false)
    ->orderBy('created_at', 'asc')
    ->get(['id', 'client_name', 'status', 'created_at']);

echo "\nUpdated Queue State:\n";
foreach ($pending as $p) {
    echo "{$p->id} | {$p->client_name} | {$p->status} | {$p->created_at}\n";
}
