<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\DocumentVersion;
use App\Models\OcrJob;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentObserver
{
    /**
     * @var array<int, array{user_id: int|null, document_id: int, title: ?string, file_path: ?string}>
     */
    private static array $permanentDeletionSnapshots = [];

    public function forceDeleting(Document $document): void
    {
        $versions = DocumentVersion::withoutGlobalScopes()
            ->where('document_id', $document->id)
            ->orderByDesc('version_number')
            ->get();

        $primaryFilePath = $versions->first()?->file_path;

        foreach ($versions as $version) {
            try {
                $version->unsearchable();
            } catch (\Throwable $e) {
                Log::warning('DocumentObserver: unsearchable version failed', [
                    'version_id' => $version->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                OcrJob::query()->where('document_version_id', $version->id)->delete();
            } catch (\Throwable $e) {
                Log::warning('DocumentObserver: OCR job delete failed', [
                    'version_id' => $version->id,
                    'error' => $e->getMessage(),
                ]);
            }

            self::deleteStoredFile($version->file_path);
        }

        if (method_exists($document, 'unsearchable')) {
            try {
                $document->unsearchable();
            } catch (\Throwable $e) {
                Log::warning('DocumentObserver: document unsearchable failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            DocumentMovement::query()->where('document_id', $document->id)->delete();
        } catch (\Throwable $e) {
            Log::warning('DocumentObserver: document_movements delete failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }

        self::$permanentDeletionSnapshots[$document->id] = [
            'user_id' => auth()->id(),
            'document_id' => $document->id,
            'title' => $document->title,
            'file_path' => $primaryFilePath,
        ];
    }

    public function forceDeleted(Document $document): void
    {
        $snapshot = self::$permanentDeletionSnapshots[$document->id] ?? null;
        unset(self::$permanentDeletionSnapshots[$document->id]);

        if ($snapshot === null) {
            return;
        }

        AuditService::logPermanentDocumentDestruction(
            $snapshot['document_id'],
            $snapshot['title'],
            $snapshot['file_path'],
            $snapshot['user_id']
        );
    }

    private static function deleteStoredFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        foreach (['local', 'private'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($relativePath)) {
                    Storage::disk($disk)->delete($relativePath);
                }
            } catch (\Throwable) {
                // try next disk
            }
        }

        $pathInfo = pathinfo($relativePath);
        if (! empty($pathInfo['dirname']) && ! empty($pathInfo['filename'])) {
            $pdfSibling = $pathInfo['dirname'].'/'.$pathInfo['filename'].'.pdf';
            foreach (['local', 'private'] as $disk) {
                try {
                    if (Storage::disk($disk)->exists($pdfSibling)) {
                        Storage::disk($disk)->delete($pdfSibling);
                    }
                } catch (\Throwable) {
                    // continue
                }
            }
        }
    }
}
