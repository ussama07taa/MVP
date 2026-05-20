<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class PaySlip extends Model
{
    use HasFactory, BelongsToTenant;
    protected $guarded = [];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }
}
