<?php

namespace App\Services;

use App\Models\{Client, Payment, Order, Invoice, OrderReturn};
use Illuminate\Support\Facades\DB;

class ClientLedgerService
{
    /**
     * Record a payment and adjust client credit.
     */
    public function recordPayment($clientId, $amount, $type, $orderId = null, $notes = null, $invoiceId = null)
    {
        return DB::transaction(function() use ($clientId, $amount, $type, $orderId, $notes, $invoiceId) {
            $client = Client::withTrashed()->whereId($clientId)->lockForUpdate()->firstOrFail();
            
            $payment = Payment::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'type' => $type, // 'avance', 'reglement', 'retour'
                'payment_method' => 'cash',
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
    public function distributeGlobalPayment(int $clientId, float $amount, string $type = 'solde', ?string $notes = null): void
    {
        DB::transaction(function () use ($clientId, $amount, $type, $notes) {
            $client = Client::withTrashed()->whereId($clientId)->lockForUpdate()->firstOrFail();
            $remaining = $amount;

            $orders = Order::withoutGlobalScopes()
                ->where('client_id', $clientId)
                ->orderBy('created_at', 'asc')
                ->get();

            $refundsByOrder = OrderReturn::whereIn('order_id', $orders->pluck('id'))
                ->groupBy('order_id')
                ->selectRaw('order_id, SUM(total_refunded) as total')
                ->pluck('total', 'order_id');

            foreach ($orders as $order) {
                if ($remaining <= 0.01) {
                    break;
                }

                $netTotal = max(0, (float) $order->total_sell_price - (float) ($refundsByOrder[$order->id] ?? 0));
                $reste = max(0, $netTotal - (float) $order->amount_paid);
                $payForOrder = min($remaining, $reste);

                if ($payForOrder <= 0.01) {
                    continue;
                }

                Payment::create([
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'order_id' => $order->id,
                    'amount' => $payForOrder,
                    'type' => $type,
                    'payment_method' => 'cash',
                    'notes' => $notes,
                ]);

                $order->increment('amount_paid', $payForOrder);
                $remaining -= $payForOrder;
            }

            $invoices = Invoice::withoutGlobalScopes()
                ->where('client_id', $clientId)
                ->where('type', 'invoice')
                ->whereNotNull('validated_at')
                ->orderBy('issue_date', 'asc')
                ->get();

            foreach ($invoices as $invoice) {
                if ($remaining <= 0.01) {
                    break;
                }

                $reste = max(0, (float) $invoice->total - (float) $invoice->amount_paid);
                $payForInvoice = min($remaining, $reste);

                if ($payForInvoice <= 0.01) {
                    continue;
                }

                Payment::create([
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payForInvoice,
                    'type' => 'reglement',
                    'payment_method' => 'cash',
                    'notes' => $notes ?? "Paiement facture {$invoice->invoice_number}",
                ]);

                $newPaid = (float) $invoice->amount_paid + $payForInvoice;
                $invoice->update([
                    'amount_paid' => $newPaid,
                    'status' => $newPaid >= (float) $invoice->total ? 'paid' : 'partial',
                ]);

                $remaining -= $payForInvoice;
            }

            if ($remaining > 0.01) {
                Payment::create([
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'amount' => $remaining,
                    'type' => $type,
                    'payment_method' => 'cash',
                    'notes' => $notes ?? 'Excédent de paiement',
                ]);
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
