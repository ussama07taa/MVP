<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'quantity' => (float) $this->quantity,
            'unit_sell_price' => (float) $this->unit_sell_price,
            'total_line_sell' => (float) $this->total_line_sell,
            'item_type' => $this->item_type,
            'item' => $this->whenLoaded('item'),
            'quantity_returned' => (float) $this->returns()->sum('quantity_returned'),
        ];
    }
}
