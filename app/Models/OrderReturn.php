<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    use HasFactory, BelongsToTenant;
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function lines()
    {
        return $this->hasMany(OrderReturnLine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
