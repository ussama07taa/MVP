<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockPanel extends Model {
    use SoftDeletes, BelongsToTenant, LogsActivity, HasFactory;
    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Stock Panel has been {$eventName}");
    }
    
    // FIFO Batch tracking relationship
    public function orderLines() {
        return $this->morphMany(OrderLine::class, 'item');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
}
