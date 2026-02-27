<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class BackupRun extends Command
{
    protected $signature = 'backup:run {--only-db} {--only-files}';
    protected $description = 'Create backup of database and files';

    public function handle()
    {
        $timestamp = date('Y-m-d-H-i-s');
        $backupName = "backup-{$timestamp}";
        $backupPath = storage_path("app/backup-temp/{$backupName}");
        
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $this->info("Starting backup: {$backupName}");

        if (!$this->option('only-files')) {
            $this->info('Backing up database...');
            $this->backupDatabase($backupPath);
        }

        if (!$this->option('only-db')) {
            $this->info('Backing up files and documents...');
            $this->backupFiles($backupPath);
        }

        $this->info('Creating zip archive...');
        $zipPath = storage_path("app/backups/{$backupName}.zip");
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }
        
        $this->createZip($backupPath, $zipPath);
        $this->deleteDirectory($backupPath);

        $sizeInMB = round(filesize($zipPath) / 1024 / 1024, 2);
        $this->info("✅ Backup completed: {$backupName}.zip ({$sizeInMB} MB)");
        $this->info("   Location: storage/app/backups/{$backupName}.zip");
        return 0;
    }

    protected function backupDatabase($path)
    {
        $filename = $path . '/database.sql';
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $command = sprintf(
            'mysqldump -h%s -u%s -p"%s" %s > %s 2>&1',
            $host,
            $username,
            $password,
            $database,
            $filename
        );

        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            $this->error('Database dump failed: ' . implode("\n", $output));
            throw new \Exception('Database backup failed');
        }
        
        $this->info('  Database backed up: ' . round(filesize($filename) / 1024 / 1024, 2) . ' MB');
    }

    protected function backupFiles($path)
    {
        $filesPath = $path . '/files';
        mkdir($filesPath, 0755, true);
        
        // Backup ALL storage/app content (includes all uploaded documents)
        $storageAppPath = storage_path('app');
        $excludeDirs = ['backup-temp', 'backups']; // Don't backup the backups folder itself
        
        $this->info('  Scanning storage directory...');
        
        $dir = opendir($storageAppPath);
        while (($item = readdir($dir)) !== false) {
            if ($item != '.' && $item != '..' && !in_array($item, $excludeDirs)) {
                $sourcePath = $storageAppPath . '/' . $item;
                $destPath = $filesPath . '/storage/' . $item;
                
                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $destPath);
                    $this->info("  Copied: storage/app/{$item}");
                } else {
                    if (!file_exists(dirname($destPath))) {
                        mkdir(dirname($destPath), 0755, true);
                    }
                    copy($sourcePath, $destPath);
                }
            }
        }
        closedir($dir);
        
        // Backup .env file
        if (file_exists(base_path('.env'))) {
            copy(base_path('.env'), $filesPath . '/.env');
            $this->info('  Copied: .env file');
        }
    }

    protected function createZip($source, $destination)
    {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create zip file');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
    }

    protected function copyDirectory($source, $destination)
    {
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($source . '/' . $file)) {
                    $this->copyDirectory($source . '/' . $file, $destination . '/' . $file);
                } else {
                    copy($source . '/' . $file, $destination . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
        }
        rmdir($dir);
    }
}
