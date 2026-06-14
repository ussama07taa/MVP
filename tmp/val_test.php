<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $data = [
        'client_id' => 1,
        'type' => 'invoice',
        'issue_date' => '2026-06-14',
        'due_date' => '',
        'tax_rate' => 0,
        'validity_days' => 15,
        'notes' => '',
        'invoice_number' => '',
        'items' => [
            [
                'description' => 'Chant Vicenza Oak 22mm [brio]',
                'category' => 'canto',
                'quantity' => '23',
                'unit' => 'm',
                'unit_price' => 6,
                'item_type' => 'stock_canto',
                'item_id' => 28
            ],
            [
                'description' => 'Collage Chant: Chant Vicenza Oak 22mm [brio]',
                'category' => 'service',
                'quantity' => '23',
                'unit' => 'm',
                'unit_price' => 10,
                'item_type' => null,
                'item_id' => null
            ]
        ]
    ];

    $validator = \Illuminate\Support\Facades\Validator::make($data, [
        'client_id' => 'required|exists:clients,id',
        'type' => 'required|in:invoice,quote',
        'status' => 'nullable|in:draft,sent,paid,partial,cancelled,accepted,refused,expired',
        'issue_date' => 'required|date',
        'due_date' => 'nullable|date|after_or_equal:issue_date',
        'validity_days' => 'nullable|integer|min:1|max:365',
        'tax_rate' => 'nullable|numeric|min:0|max:100',
        'notes' => 'nullable|string|max:2000',
        'items' => 'required|array|min:1',
        'items.*.description' => 'required|string|max:500',
        'items.*.category' => 'required|in:mdf,lati,hardware,labor,canto,service,other',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit' => 'nullable|string|max:20',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.item_type' => 'nullable|in:stock_panel,stock_canto,service',
        'items.*.item_id' => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        echo "Validation Failed!\n";
        print_r($validator->errors()->toArray());
    } else {
        echo "Validation Passed!\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
