<?php
$request = \Illuminate\Http\Request::create('/api/orders/checkout', 'POST', [
    'client_id' => 1,
    'amount_paid' => 1000,
    'items' => [
        [
            'type' => 'custom_labor',
            'id' => 'custom_labor_12345',
            'quantity' => 10,
            'unit_price' => 200,
            'name' => 'Pose Canto'
        ]
    ]
]);
$formRequest = \App\Http\Requests\StoreOrderRequest::createFrom($request);
$validator = validator($formRequest->all(), $formRequest->rules());
if ($validator->fails()) {
    echo json_encode($validator->errors()->toArray());
} else {
    echo 'Validation Passed';
}
