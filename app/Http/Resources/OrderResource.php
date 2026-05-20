<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->whenLoaded('client', function() {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'phone' => $this->client->phone,
                ];
            }),
            'total_sell_price' => (float) $this->total_sell_price,
            'amount_paid' => (float) $this->amount_paid,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
            'payments' => $this->whenLoaded('payments'),
            'total_refunded' => (float) $this->returns()->sum('total_refunded'),
            'return_history' => $this->whenLoaded('returns'),
        ];
    }
}
