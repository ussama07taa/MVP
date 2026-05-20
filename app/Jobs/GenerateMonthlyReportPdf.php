<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Notifications\ReportGeneratedNotification;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateMonthlyReportPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $tenantId;

    public function __construct(User $user, $tenantId)
    {
        $this->user = $user;
        $this->tenantId = $tenantId;
    }

    public function handle()
    {
        // Simulate heavy data fetching or perform actual logic
        $data = [
            'title' => 'Rapport Mensuel',
            'tenant_id' => $this->tenantId,
            'date' => now()->format('F Y')
        ];
        
        // Ensure the directory exists
        Storage::disk('public')->makeDirectory('reports');

        // Generate PDF
        // Note: You must create the view 'reports.monthly' first
        try {
            $pdf = Pdf::loadView('reports.monthly', $data);
            $fileName = "reports/tenant_{$this->tenantId}_" . now()->timestamp . ".pdf";
            
            Storage::disk('public')->put($fileName, $pdf->output());

            // Notify the user that it's ready
            $this->user->notify(new ReportGeneratedNotification(url("storage/{$fileName}")));
        } catch (\Exception $e) {
            \Log::error("PDF Generation failed: " . $e->getMessage());
        }
    }
}
