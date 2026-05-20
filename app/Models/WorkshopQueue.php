<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkshopQueue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'started_at'   => 'datetime',
        'done_at'      => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function services()
    {
        return $this->hasMany(WorkshopQueueService::class, 'queue_id')->orderBy('id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isAllDone(): bool
    {
        return $this->services->count() > 0 && $this->services->every(fn($s) => $s->is_done);
    }

    public static function generateNumber(int $tenantId): string
    {
        $todayCount = static::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count();
        return 'Q-' . str_pad($todayCount + 1, 2, '0', STR_PAD_LEFT);
    }
}
