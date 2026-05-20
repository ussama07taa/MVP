<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class PurchaseReturn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function line() { return $this->belongsTo(PurchaseLine::class, 'purchase_line_id'); }
}
