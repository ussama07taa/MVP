<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $req = new \App\Http\Requests\StoreOrderRequest();
    $req->merge([
        'client_id' => 1,
        'amount_paid' => 0,
        'items' => [
            ['type' => 'panel', 'id' => 1, 'quantity' => 1, 'unit_price' => 100]
        ]
    ]);
    
    // Bypass validation for manual test
    $req->setContainer($app);
    $req->setRedirector($app['redirect']);
    $req->validateResolved();

    $c = new \App\Http\Controllers\OrderController();
    $res = $c->store($req);
    echo "SUCCESS: \n";
    echo json_encode($res->getData());
} catch (\Exception $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . " on line " . $e->getLine() . "\n";
}
