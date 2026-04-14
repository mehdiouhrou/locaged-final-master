<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuthenticationLog;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class AuditEvidenceService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function buildSignedEvidencePackage(User $actor, array $filters = []): array
    {
        $logType = (string) ($filters['log_type'] ?? 'all');

        [$documentLogs, $authenticationLogs] = $this->loadLogs($actor, $filters, $logType);

        $normalizedDocumentRows = $this->normalizeDocumentLogs($documentLogs);
        $normalizedAuthenticationRows = $this->normalizeAuthenticationLogs($authenticationLogs);

        $recordsHash = hash('sha256', json_encode([
            'document_logs' => $normalizedDocumentRows,
            'authentication_logs' => $normalizedAuthenticationRows,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $manifest = [
            'spec_version' => 'audit-legal-v2',
            'generated_at' => now()->toIso8601String(),
            'generated_by' => [
                'id' => $actor->id,
                'name' => $actor->full_name,
                'email' => $actor->email,
            ],
            'filters' => $filters,
            'counts' => [
                'document_logs' => count($normalizedDocumentRows),
                'authentication_logs' => count($normalizedAuthenticationRows),
                'total' => count($normalizedDocumentRows) + count($normalizedAuthenticationRows),
            ],
            'audit_chain' => [
                'sealed_rows' => AuditLog::withoutGlobalScopes()->whereNotNull('entry_hash')->count(),
                'last_entry_hash' => AuditLog::withoutGlobalScopes()->whereNotNull('entry_hash')->orderByDesc('id')->value('entry_hash'),
            ],
            'records_hash_sha256' => $recordsHash,
            'app' => [
                'name' => (string) config('app.name'),
                'env' => (string) config('app.env'),
                'url' => (string) config('app.url'),
            ],
        ];

        $signature = $this->signManifest($manifest);

        $archiveName = 'audit-evidence-' . now()->format('Ymd_His') . '-' . Str::random(6) . '.zip';
        $localRelativePath = 'legal-evidence/' . $archiveName;
        $localAbsolutePath = storage_path('app/private/' . $localRelativePath);
        $this->ensureDirectory(dirname($localAbsolutePath));

        $this->createZipPackage(
            $localAbsolutePath,
            $manifest,
            $signature,
            $normalizedDocumentRows,
            $normalizedAuthenticationRows
        );

        $archiveResult = $this->archiveImmutably($localRelativePath, $archiveName, $manifest, $signature);

        return [
            'download_name' => $archiveName,
            'local_relative_path' => $localRelativePath,
            'local_absolute_path' => $localAbsolutePath,
            'manifest' => $manifest,
            'signature' => $signature,
            'immutable_archive' => $archiveResult,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:\Illuminate\Support\Collection<int,AuditLog>,1:\Illuminate\Support\Collection<int,AuthenticationLog>}
     */
    private function loadLogs(User $actor, array $filters, string $logType): array
    {
        $documentQuery = AuditLog::query()
            ->with(['document' => function ($q) {
                $q->withoutGlobalScopes()->withTrashed();
            }]);

        if (! empty($filters['date_from'])) {
            $documentQuery->whereDate('occurred_at', '>=', (string) $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $documentQuery->whereDate('occurred_at', '<=', (string) $filters['date_to']);
        }
        if (! empty($filters['user_id'])) {
            $documentQuery->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['action_type'])) {
            $documentQuery->where('action', (string) $filters['action_type']);
        }
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $documentQuery->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhereHas('document', function ($dq) use ($search) {
                        $dq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        $authQuery = AuthenticationLog::query()->with('user');
        if (! $actor->can('view any role') && ! $actor->can('view organization wide reports')) {
            $authQuery->where('user_id', $actor->id);
        }
        if (! empty($filters['date_from'])) {
            $authQuery->whereDate('occurred_at', '>=', (string) $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $authQuery->whereDate('occurred_at', '<=', (string) $filters['date_to']);
        }
        if (! empty($filters['user_id'])) {
            $authQuery->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['action_type'])) {
            $authQuery->where('type', (string) $filters['action_type']);
        }
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $authQuery->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        $documentLogs = collect();
        $authenticationLogs = collect();

        if (in_array($logType, ['all', 'documents'], true)) {
            $documentLogs = $documentQuery->orderBy('id')->get();
        }
        if (in_array($logType, ['all', 'authentication'], true)) {
            $authenticationLogs = $authQuery->orderBy('id')->get();
        }

        return [$documentLogs, $authenticationLogs];
    }

    /**
     * @param \Illuminate\Support\Collection<int,AuditLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDocumentLogs($logs): array
    {
        return $logs->map(function (AuditLog $log): array {
            return [
                'id' => $log->id,
                'occurred_at' => $this->formatDate($log->occurred_at),
                'user_id' => $log->user_id,
                'user_name' => $log->user_name,
                'document_id' => $log->document_id,
                'document_title' => $log->document?->title,
                'version_id' => $log->version_id,
                'action' => $log->action,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'metadata' => $log->metadata,
                'hash_version' => $log->hash_version,
                'previous_hash' => $log->previous_hash,
                'entry_hash' => $log->entry_hash,
                'sealed_at' => $this->formatDate($log->sealed_at),
            ];
        })->values()->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int,AuthenticationLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAuthenticationLogs($logs): array
    {
        return $logs->map(function (AuthenticationLog $log): array {
            return [
                'id' => $log->id,
                'occurred_at' => $this->formatDate($log->occurred_at),
                'user_id' => $log->user_id,
                'user_name' => $log->user_name,
                'email' => $log->email,
                'type' => $log->type,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
            ];
        })->values()->all();
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, string|null>
     */
    private function signManifest(array $manifest): array
    {
        $payload = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $privateKey = (string) config('audit.evidence.private_key', '');
        if ($privateKey !== '') {
            $signature = null;
            $ok = openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            if ($ok && is_string($signature)) {
                return [
                    'algorithm' => 'rsa-sha256',
                    'encoding' => 'base64',
                    'value' => base64_encode($signature),
                    'public_key_fingerprint' => hash('sha256', (string) config('audit.evidence.public_key', '')),
                ];
            }
        }

        $secret = (string) config('audit.hmac_key', '');
        if ($secret === '') {
            $secret = (string) config('app.key', '');
        }

        return [
            'algorithm' => 'hmac-sha256',
            'encoding' => 'hex',
            'value' => hash_hmac('sha256', $payload, $secret),
            'public_key_fingerprint' => null,
        ];
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, string|null> $signature
     * @param array<int, array<string, mixed>> $documentRows
     * @param array<int, array<string, mixed>> $authenticationRows
     */
    private function createZipPackage(
        string $zipPath,
        array $manifest,
        array $signature,
        array $documentRows,
        array $authenticationRows
    ): void {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString(
            'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
        $zip->addFromString(
            'signature.json',
            json_encode($signature, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
        $zip->addFromString(
            'document_logs.ndjson',
            $this->toNdjson($documentRows)
        );
        $zip->addFromString(
            'authentication_logs.ndjson',
            $this->toNdjson($authenticationRows)
        );
        $zip->addFromString('README.txt', $this->buildReadme());

        $zip->close();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function toNdjson(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, string|null> $signature
     * @return array<string, mixed>
     */
    private function archiveImmutably(
        string $localRelativePath,
        string $archiveName,
        array $manifest,
        array $signature
    ): array {
        $enabled = (bool) config('audit.evidence.worm_enabled', false);
        if (! $enabled) {
            return [
                'enabled' => false,
                'disk' => null,
                'path' => null,
                'status' => 'skipped',
                'message' => 'WORM archive disabled by configuration.',
            ];
        }

        $disk = (string) config('audit.evidence.worm_disk', 's3');
        $years = max((int) config('audit.evidence.worm_retention_years', 10), 1);
        $mode = strtoupper((string) config('audit.evidence.worm_mode', 'COMPLIANCE'));
        $remotePath = trim((string) config('audit.evidence.worm_prefix', 'audit-evidence/'), '/') . '/' . $archiveName;

        $content = Storage::disk('private')->get($localRelativePath);
        $retainUntil = now()->addYears($years)->toIso8601String();

        try {
            Storage::disk($disk)->put($remotePath, $content, [
                'visibility' => 'private',
                'Metadata' => [
                    'evidence-spec' => 'audit-legal-v2',
                    'records-hash' => (string) ($manifest['records_hash_sha256'] ?? ''),
                    'signature-algo' => (string) ($signature['algorithm'] ?? ''),
                ],
                'ObjectLockMode' => $mode,
                'ObjectLockRetainUntilDate' => $retainUntil,
            ]);

            return [
                'enabled' => true,
                'disk' => $disk,
                'path' => $remotePath,
                'status' => 'archived',
                'mode' => $mode,
                'retain_until' => $retainUntil,
            ];
        } catch (\Throwable $e) {
            return [
                'enabled' => true,
                'disk' => $disk,
                'path' => $remotePath,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d\TH:i:s.uP');
        }

        if ($date === null) {
            return null;
        }

        return (string) $date;
    }

    private function buildReadme(): string
    {
        return <<<TXT
Audit Legal Evidence Package (v2)

Files:
- manifest.json: extraction metadata, filters, counts, records hash
- signature.json: signature details for the manifest
- document_logs.ndjson: one JSON row per document audit record
- authentication_logs.ndjson: one JSON row per authentication record

Verification:
1) Verify manifest signature using configured algorithm.
2) Recompute records hash from NDJSON files and compare with manifest.records_hash_sha256.
3) Run `php artisan audit:verify-integrity` on source system for chain integrity.

TXT;
    }
}
