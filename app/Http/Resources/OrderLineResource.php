<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOrderLine = $this->resource instanceof \App\Models\OrderLine;

        return [
            'id' => $this->id,
            'label' => $this->label ?? $this->description,
            'quantity' => (float) $this->quantity,
            'unit_sell_price' => (float) ($this->unit_sell_price ?? $this->unit_price ?? 0),
            'total_line_sell' => (float) ($this->total_line_sell ?? $this->total ?? 0),
            'item_type' => $this->item_type ?? 'App\Models\Service',
            'item' => $this->resource->relationLoaded('item') ? $this->item : null,
            'quantity_returned' => $isOrderLine ? (float) $this->returns()->sum('quantity_returned') : 0,
        ];
    }
}
