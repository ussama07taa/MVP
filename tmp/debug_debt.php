<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{Client, Order, Invoice, Payment, OrderReturn};

$sara = Client::withoutGlobalScopes()->where('name', 'like', '%Sara%')->orWhere('phone', 'like', '%Sara%')->orWhere('phone', 'like', '%0655667788%')->first();

if (!$sara) {
    echo "Sara not found.\n";
    return;
}

$id = $sara->id;
echo "Client: " . $sara->name . " (ID: $id)\n";
echo "Current total_credit (stored): " . $sara->total_credit . " DH\n\n";

$orders = Order::withoutGlobalScopes()->where('client_id', $id)->get();
echo "--- ALL ORDERS (POS) ---\n";
foreach ($orders as $o) {
    $reste = $o->total_sell_price - $o->amount_paid;
    echo "ID #{$o->id} | Tenant={$o->tenant_id} | Total={$o->total_sell_price} | Paid={$o->amount_paid} | Reste={$reste} | Date: {$o->created_at}\n";
}

$invoices = Invoice::withoutGlobalScopes()->where('client_id', $id)->where('type', 'invoice')->whereNotNull('validated_at')->get();
echo "\n--- ALL INVOICES (Standard) ---\n";
foreach ($invoices as $i) {
    $reste = $i->total - $i->amount_paid;
    echo "Num #{$i->invoice_number} | Total={$i->total} | Paid={$i->amount_paid} | Reste={$reste} | Date: {$i->issue_date}\n";
}

$payments = Payment::withoutGlobalScopes()->where('client_id', $id)->get();
echo "\n--- ALL PAYMENTS ---\n";
foreach ($payments as $p) {
    echo "PID #{$p->id} | Amount={$p->amount} | Type={$p->type} | Linked Order=#{$p->order_id} | Date: {$p->created_at}\n";
}

$totalOrders = $orders->sum('total_sell_price');
$totalInvoices = $invoices->sum('total');
$totalPayments = $payments->sum('amount');

$returns = OrderReturn::withoutGlobalScopes()
            ->whereHas('order', function($q) use ($id) {
                $q->withoutGlobalScopes()->where('client_id', $id);
            })
            ->get();
$totalReturns = $returns->sum('total_refunded');

$calcDebt = ($totalOrders + $totalInvoices) - $totalPayments - $totalReturns;

echo "\n--- FINAL CALCULATION ---\n";
echo "SUM(Orders)   : $totalOrders DH\n";
echo "SUM(Invoices) : $totalInvoices DH\n";
echo "SUM(Payments) : $totalPayments DH\n";
echo "SUM(Returns)  : $totalReturns DH\n";
echo "Calculated Debt: $calcDebt DH\n";
if ($calcDebt == $sara->total_credit) {
    echo "VERDICT: The stored 'total_credit' IS CORRECT according to transaction logs.\n";
} else {
    echo "VERDICT: The stored 'total_credit' IS DESYNCED from logs.\n";
}
