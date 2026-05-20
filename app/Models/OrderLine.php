<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Traits\BelongsToTenant;

class OrderLine extends Model
{
    use HasFactory, LogsActivity, BelongsToTenant;
    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "L'article de commande a été {$eventName}");
    }

    public function item() {
        return $this->morphTo();
    }

    public function returns() {
        return $this->hasMany(OrderReturnLine::class);
    }
}
