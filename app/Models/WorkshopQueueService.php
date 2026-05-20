<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopQueueService extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function queue()
    {
        return $this->belongsTo(WorkshopQueue::class, 'queue_id');
    }

    public function doneByUser()
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
