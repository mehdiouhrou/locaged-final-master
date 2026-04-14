<?php

namespace App\Console\Commands;

use App\Models\DocumentVersion;
use Illuminate\Console\Command;

class ImportToElasticsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-to-typesense {--include-empty-ocr : Index latest approved versions even without OCR text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import approved document versions into Typesense via Laravel Scout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $includeEmptyOcr = (bool) $this->option('include-empty-ocr');

        $query = DocumentVersion::query()
            ->whereHas('document', fn ($q) => $q->where('status', 'approved'));

        if (! $includeEmptyOcr) {
            $query->whereNotNull('ocr_text')
                ->where('ocr_text', '!=', '');
        }

        $count = 0;
        $query->chunkById(200, function ($versions) use (&$count) {
            $versions->searchable();
            $count += $versions->count();
            $this->info("Indexed {$count} versions...");
        });

        $this->info("Typesense import complete. Indexed {$count} versions.");

        return self::SUCCESS;
    }
}
