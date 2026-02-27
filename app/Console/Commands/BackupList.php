<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupList extends Command
{
    protected $signature = 'backup:list';
    protected $description = 'List all available backups';

    public function handle()
    {
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            $this->info('No backups found.');
            return 0;
        }

        $backups = glob($backupPath . '/*.zip');
        if (empty($backups)) {
            $this->info('No backups found.');
            return 0;
        }

        $this->info('Available backups:');
        $data = [];
        $totalSize = 0;
        foreach ($backups as $backup) {
            $size = filesize($backup);
            $totalSize += $size;
            $data[] = [
                basename($backup),
                round($size / 1024 / 1024, 2) . ' MB',
                date('Y-m-d H:i:s', filemtime($backup))
            ];
        }
        $this->table(['Name', 'Size', 'Date'], $data);
        $this->info('Total: ' . count($backups) . ' backups, ' . round($totalSize / 1024 / 1024, 2) . ' MB');
        return 0;
    }
}
