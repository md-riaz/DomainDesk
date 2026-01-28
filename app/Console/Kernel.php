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
        // Scan for expiring domains daily at 8:00 AM
        $schedule->command('domains:scan-expiring')
            ->dailyAt('08:00')
            ->timezone('UTC')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Process auto-renewals daily at 2:00 AM
        $schedule->command('domains:process-auto-renewals')
            ->dailyAt('02:00')
            ->timezone('UTC')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Send renewal reminders daily at 8:15 AM (staggered from scan-expiring)
        $schedule->command('domains:send-renewal-reminders')
            ->dailyAt('08:15')
            ->timezone('UTC')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Send low balance alerts daily at 9:00 AM
        $schedule->command('partners:send-low-balance-alerts')
            ->dailyAt('09:00')
            ->timezone('UTC')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Prune old notifications (keep last 30 days)
        $schedule->command('notifications:prune', ['--days' => 30])
            ->daily()
            ->timezone('UTC');

        // Clean up old telescope entries (if Telescope is installed)
        $schedule->command('telescope:prune')
            ->daily()
            ->timezone('UTC');

        // Clean up failed jobs older than 7 days
        $schedule->command('queue:prune-failed', ['--hours' => 168])
            ->daily()
            ->timezone('UTC');
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
