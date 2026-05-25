<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOrder = $this->resource instanceof \App\Models\Order;

        return [
            'id' => $this->id,
            'client' => $this->whenLoaded('client', function() {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'phone' => $this->client->phone,
                ];
            }),
            'total_sell_price' => (float) ($this->total_sell_price ?? 0),
            'amount_paid' => (float) ($this->amount_paid ?? 0),
            'status' => $this->status,
            'display_reference' => $this->display_reference ?? ("#FAC-" . $this->id),
            'source' => $this->source ?? 'pos',
            'created_at' => optional($this->created_at)->toIso8601String(),
            'lines' => OrderLineResource::collection($this->lines ?? $this->items ?? []),
            'payments' => $this->payments ?? [],
            'total_refunded' => $isOrder ? (float) $this->returns()->sum('total_refunded') : 0,
            'return_history' => ($isOrder && $this->relationLoaded('returns')) ? $this->returns : [],
        ];
    }
}
