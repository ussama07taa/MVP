<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Notifications\ReportGeneratedNotification;
use Maatwebsite\Excel\Facades\Excel;

class GenerateGenericExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $exportObject;
    protected $fileName;

    public function __construct(User $user, $exportObject, $fileName)
    {
        $this->user = $user;
        $this->exportObject = $exportObject;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        Excel::store($this->exportObject, $this->fileName, 'public');
        
        $this->user->notify(new ReportGeneratedNotification(url("storage/{$this->fileName}")));
    }
}
