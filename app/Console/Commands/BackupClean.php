<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupClean extends Command
{
    protected $signature = 'backup:clean {--dry-run} {--keep-days=7}';
    protected $description = 'Remove old backups (default: keeps 7 days)';

    public function handle()
    {
        $backupPath = storage_path('app/backups');
        $keepDays = (int) $this->option('keep-days');
        
        if (!file_exists($backupPath)) {
            $this->info('No backups to clean.');
            return 0;
        }

        $backups = glob($backupPath . '/*.zip');
        $cutoffTime = time() - ($keepDays * 24 * 60 * 60);
        $deleted = 0;

        foreach ($backups as $backup) {
            if (filemtime($backup) < $cutoffTime) {
                if ($this->option('dry-run')) {
                    $this->info('Would delete: ' . basename($backup));
                } else {
                    unlink($backup);
                    $this->info('Deleted: ' . basename($backup));
                }
                $deleted++;
            }
        }

        $this->info($deleted === 0 ? 'No old backups to clean.' : "✅ Cleaned {$deleted} backup(s)");
        return 0;
    }
}
