<?php

// app/Jobs/ProcessOcrJob.php

namespace App\Jobs;

use App\Models\DocumentVersion;
use App\Models\OcrJob;
use App\Services\OcrService;
use Exception;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected OcrJob $ocrJob;
    
    /**
     * The number of seconds the job can run before timing out.
     * Increased to 1 hour to handle large PDFs (100+ pages)
     */
    public int $timeout = 3600; // 1 hour (was 30 minutes)
    
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(OcrJob $ocrJob)
    {
        $this->onQueue(config('ged.ocr_queue', 'default'));
        $this->ocrJob = $ocrJob;
    }

    public function handle(OcrService $ocrService): void
    {

        Log::info("OCR Job started", ['ocr_job_id' => $this->ocrJob->id]);
        $this->ocrJob->update(['status' => OcrJob::STATUS_PROCESSING, 'processed_at' => now()]);

        try {
            $docVersion = $this->ocrJob->documentVersion;

            if (! $docVersion) {
                throw new Exception('Document version not found.');
            }

            $path = $this->absolutePathForVersionFile($docVersion);
            Log::info('Processing file at path', ['path' => $path]);

            $ocrText = $ocrService->extractText($path);

            $docVersion->update(['ocr_text' => $ocrText]);

            try {
                $docVersion->document?->searchable();
            } catch (Throwable $e) {
                Log::warning('OCR finished but search index sync failed (document text was saved)', [
                    'ocr_job_id' => $this->ocrJob->id,
                    'document_version_id' => $docVersion->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->ocrJob->update([
                'status' => OcrJob::STATUS_COMPLETED,
                'completed_at' => now(),
                'error_message' => null,
            ]);
            Log::info("OCR Job completed successfully", ['ocr_job_id' => $this->ocrJob->id]);

        } catch (Exception $e) {
            Log::error('OCR Job Failed', [
                'ocr_job_id' => $this->ocrJob->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Only mark as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $this->ocrJob->update([
                    'status' => OcrJob::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            } else {
                // Don't change status on retry - let Laravel handle retries
                $this->ocrJob->update([
                    'error_message' => $e->getMessage(),
                ]);
            }
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Resolve the absolute filesystem path for a version file (uploads may live on local or public disk).
     */
    private function absolutePathForVersionFile(DocumentVersion $documentVersion): string
    {
        $relative = $documentVersion->file_path;
        if ($relative === null || $relative === '') {
            throw new Exception('Document version has no file_path.');
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($relative)) {
                return $disk->path($relative);
            }
        }

        if (Storage::exists($relative)) {
            return Storage::path($relative);
        }

        throw new Exception(
            'Document file not found in storage (checked disks: local, public, default). Path: '.$relative
        );
    }

}
