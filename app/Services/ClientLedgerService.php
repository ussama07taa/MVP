<?php

namespace App\Services;

use App\Models\{Client, Payment, Order, Invoice, OrderReturn};
use Illuminate\Support\Facades\DB;

class ClientLedgerService
{
    /**
     * Record a payment and adjust client credit.
     */
    public function recordPayment($clientId, $amount, $type, $orderId = null, $notes = null, $invoiceId = null, $paymentMethod = 'cash')
    {
        return DB::transaction(function() use ($clientId, $amount, $type, $orderId, $notes, $invoiceId, $paymentMethod) {
            $client = Client::withTrashed()->whereId($clientId)->lockForUpdate()->firstOrFail();
            
            $payment = Payment::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'type' => $type, // 'avance', 'reglement', 'retour'
                'payment_method' => $paymentMethod,
                'notes' => $notes
            ]);

            // Adjust credit: payments reduce debt; returns also reduce debt (amount stored negative)
            $creditAdjustment = $type === 'retour' ? abs($amount) : $amount;
            $client->decrement('total_credit', $creditAdjustment);

            return $payment;
        });
    }

    /**
     * Increase client credit (debt) when a new order is placed.
     */
    public function adjustCreditForOrder($clientId, $amount)
    {
        $client = Client::whereId($clientId)->lockForUpdate()->firstOrFail();
        $client->increment('total_credit', $amount);
        return $client;
    }

    /**
     * Distribute a global client payment across unpaid orders (net of returns) then invoices.
     */
    public function distributeGlobalPayment(int $clientId, float $amount, string $type = 'solde', ?string $notes = null, string $paymentMethod = 'cash'): void
    {
        DB::transaction(function () use ($clientId, $amount, $type, $notes, $paymentMethod) {
            $client = Client::withTrashed()->whereId($clientId)->lockForUpdate()->firstOrFail();
            $remaining = $amount;

            $orders = Order::withoutGlobalScopes()
                ->where('client_id', $clientId)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $refundsByOrder = OrderReturn::whereIn('order_id', $orders->pluck('id'))
                ->groupBy('order_id')
                ->selectRaw('order_id, SUM(total_refunded) as total')
                ->pluck('total', 'order_id');

            $paymentsToInsert = [];
            
            foreach ($orders as $order) {
                if (bccomp((string)$remaining, '0', 2) <= 0) {
                    break;
                }

                $netTotal = max(0, (float) $order->total_sell_price - (float) ($refundsByOrder[$order->id] ?? 0));
                $reste = round(max(0, $netTotal - (float) $order->amount_paid), 2);
                $payForOrder = min($remaining, $reste);

                if (bccomp((string)$payForOrder, '0', 2) <= 0) {
                    continue;
                }

                $paymentsToInsert[] = [
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'order_id' => $order->id,
                    'amount' => $payForOrder,
                    'type' => $type,
                    'payment_method' => $paymentMethod,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $order->amount_paid = round((float) $order->amount_paid + $payForOrder, 2);
                $order->save();
                $remaining -= $payForOrder;
            }

            $invoices = Invoice::withoutGlobalScopes()
                ->where('client_id', $clientId)
                ->where('type', 'invoice')
                ->whereNotNull('validated_at')
                ->orderBy('issue_date', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($invoices as $invoice) {
                if (bccomp((string)$remaining, '0', 2) <= 0) {
                    break;
                }

                $reste = round(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2);
                $payForInvoice = min($remaining, $reste);

                if (bccomp((string)$payForInvoice, '0', 2) <= 0) {
                    continue;
                }

                $paymentsToInsert[] = [
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payForInvoice,
                    'type' => 'reglement',
                    'payment_method' => $paymentMethod,
                    'notes' => $notes ?? "Paiement facture {$invoice->invoice_number}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $newPaid = round((float) $invoice->amount_paid + $payForInvoice, 2);
                $invoice->update([
                    'amount_paid' => $newPaid,
                    'status' => $newPaid >= (float) $invoice->total ? 'paid' : 'partial',
                ]);

                $remaining -= $payForInvoice;
            }

            if (bccomp((string)$remaining, '0', 2) === 1) {
                $paymentsToInsert[] = [
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'amount' => $remaining,
                    'type' => $type,
                    'payment_method' => $paymentMethod,
                    'notes' => $notes ?? 'Excédent de paiement',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($paymentsToInsert) > 0) {
                Payment::insert($paymentsToInsert);
            }

            $client->decrement('total_credit', $amount);
        });
    }

    public function orderNetRemaining(Order $order): float
    {
        $refunded = (float) $order->returns()->sum('total_refunded');
        $netTotal = max(0, (float) $order->total_sell_price - $refunded);

        return max(0, $netTotal - (float) $order->amount_paid);
    }
}
