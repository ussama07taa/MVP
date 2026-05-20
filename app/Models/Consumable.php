<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Consumable extends Model
{
    use BelongsToTenant, SoftDeletes;
    protected $guarded = [];
}
