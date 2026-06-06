<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturnLine extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLine::class);
    }
}
