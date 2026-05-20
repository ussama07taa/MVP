<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class InventoryAdjustment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    public function item()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseLine()
    {
        return $this->belongsTo(PurchaseLine::class);
    }
}
