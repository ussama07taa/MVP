<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Payment extends Model {
    use BelongsToTenant;
    protected $guarded = [];

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
