<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the backup daily at 00:00
        $schedule->command('backup:clean')->daily()->at('01:00'); // Deletes old backups
        $schedule->command('backup:run')->daily()->at('01:30');   // Runs the new backup

        // Process recurring expenses every 1st of the month at 00:05 AM
        $schedule->command('expenses:process-recurring')->monthlyOn(1, '00:05');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
