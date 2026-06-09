<?php

namespace App\Services;

use App\Models\{Client, Payment, Order};
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
}
