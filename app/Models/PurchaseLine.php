<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class PurchaseLine extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'purchase_id', 'stock_item_id', 'stock_item_type',
        'category', 'product_name_snapshot', 'quantity', 'quantity_remaining',
        'unit_cost', 'unit_sell_price', 'total_line_cost',
    ];

    public function purchase() { 
        return $this->belongsTo(Purchase::class); 
    }
}
