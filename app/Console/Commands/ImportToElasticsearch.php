<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Console\Command;

class ImportToElasticsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-to-typesense {--include-empty-ocr : Index approved documents even when latest version has no OCR text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import approved documents into Typesense via Laravel Scout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $includeEmptyOcr = (bool) $this->option('include-empty-ocr');

        $query = Document::withoutGlobalScopes()
            ->where('status', DocumentStatus::Approved->value);

        if (! $includeEmptyOcr) {
            $query->whereHas('latestVersion', function ($q) {
                $q->whereNotNull('ocr_text')
                    ->where('ocr_text', '!=', '');
            });
        }

        $count = 0;
        $query->chunkById(200, function ($documents) use (&$count) {
            $documents->searchable();
            $count += $documents->count();
            $this->info("Indexed {$count} documents...");
        });

        $this->info("Typesense import complete. Indexed {$count} documents.");

        return self::SUCCESS;
    }
}
