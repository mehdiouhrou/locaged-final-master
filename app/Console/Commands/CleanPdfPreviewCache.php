<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanPdfPreviewCache extends Command
{
    protected $signature = 'documents:clean-pdf-preview-cache {--hours=} {--dry-run}';

    protected $description = 'Delete cached Office->PDF upload preview files older than N hours';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?: config('uploads.pdf_preview_cache_hours', 48));
        $hours = max($hours, 1);

        $tmpBase = storage_path('app/tmp/pdf-preview');

        if (! is_dir($tmpBase)) {
            $this->info('Nothing to clean: pdf-preview cache directory does not exist.');

            return self::SUCCESS;
        }

        $cutoff = now()->subHours($hours)->getTimestamp();
        $dryRun = (bool) $this->option('dry-run');

        $deleted = 0;
        $scanned = 0;

        foreach (File::files($tmpBase) as $file) {
            $scanned++;

            if ($file->getMTime() >= $cutoff) {
                continue;
            }

            if ($dryRun) {
                $deleted++;

                continue;
            }

            if (@unlink($file->getPathname())) {
                $deleted++;
            }
        }

        $verb = $dryRun ? 'would be deleted' : 'deleted';
        $this->info("Scanned {$scanned} file(s); {$deleted} {$verb} (older than {$hours}h).");

        return self::SUCCESS;
    }
}
