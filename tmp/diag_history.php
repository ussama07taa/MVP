<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::where('role', 'cashier')->first();
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/api/clients/1/history', 'GET')
);

// Manually authenticate for this fake request if needed
auth()->login($user);
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getContent() . "\n";
