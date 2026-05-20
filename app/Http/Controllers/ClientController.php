<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Http\Requests\StoreClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class ClientController extends Controller
{
    public function index()
    {
        return Client::withoutGlobalScopes()->select('id', 'name', 'phone', 'total_credit')->latest()->get();
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();
        
        $client = new Client();
        $client->name = $validated['name'];
        $client->phone = $validated['phone'] ?? null;
        $client->tenant_id = auth()->user()->tenant_id;
        $client->save();

        return response()->json($client, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $client = Client::findOrFail($id);
        $client->update($validated);

        return response()->json($client);
    }

    public function destroy($id)
    {
        $client = Client::withoutGlobalScopes()->withTrashed()->findOrFail($id);

        if ((float) $client->total_credit > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un client avec un crédit en cours.',
            ], 422);
        }

        // If already soft-deleted, we can force delete or just leave it. 
        // Let's force delete if the user clicks delete again.
        if ($client->trashed()) {
            $client->forceDelete();
        } else {
            $client->delete();
        }

        return response()->json(['message' => 'Client supprimé avec succès.']);
    }

    public function history($id)
    {
        $client = Client::findOrFail($id);
        $orders = Order::with(['lines.item', 'payments'])->where('client_id', $id)->latest()->get();
        return response()->json(['client' => $client, 'orders' => $orders]);
    }

    public function pay(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.1']);
        
        return DB::transaction(function() use ($request, $id) {
            $client = Client::findOrFail($id);
            $amountToDistribute = $request->amount;
            
            $unpaidOrders = Order::where('client_id', $id)
                ->whereRaw('amount_paid < total_sell_price')
                ->orderBy('created_at', 'asc')
                ->get();
                
            foreach ($unpaidOrders as $order) {
                if ($amountToDistribute <= 0) break;
                
                $reste = $order->total_sell_price - $order->amount_paid;
                $paymentForThisOrder = min($amountToDistribute, $reste);
                
                Payment::create([
                    'tenant_id' => $client->tenant_id,
                    'order_id' => $order->id,
                    'client_id' => $client->id,
                    'amount' => $paymentForThisOrder,
                    'type' => 'règlement',
                    'payment_method' => 'cash'
                ]);
                
                $order->increment('amount_paid', $paymentForThisOrder);
                $amountToDistribute -= $paymentForThisOrder;
            }
            
            $client->decrement('total_credit', $request->amount);
            return response()->json(['message' => 'Paiement effectué']);
        });
    }
}
