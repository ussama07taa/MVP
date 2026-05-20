<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model {
    use SoftDeletes, BelongsToTenant, LogsActivity;
    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "L'employé a été {$eventName}");
    }

    public function attendances() {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function advances() {
        return $this->hasMany(EmployeeAdvance::class);
    }
}
