<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\BackfillDocumentCategories::class,
        \App\Console\Commands\ConvertDocumentsToPdf::class,
        \App\Console\Commands\ForceExpireDocument::class,
        \App\Console\Commands\CheckExpiredDocuments::class,
        \App\Console\Commands\ExtractAuditEvidence::class,
        \App\Console\Commands\VerifyAuditIntegrity::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('audit:purge-old-logs')->dailyAt('02:30');
        $schedule->command('audit:verify-integrity')->dailyAt('02:40');

        require base_path('routes/console.php');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
