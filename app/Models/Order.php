<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;


use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model {
    use SoftDeletes, BelongsToTenant, LogsActivity;
    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Order has been {$eventName}");
    }
    
    // Relationships
    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function lines() {
        return $this->hasMany(OrderLine::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function cashier() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function returns() {
        return $this->hasMany(OrderReturn::class);
    }
}
