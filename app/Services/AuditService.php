<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function log(string $action, Document $document, ?int $versionId = null, array $metadata = []): void
    {
        if (! $versionId) {
            return;
        }

        DB::transaction(function () use ($action, $document, $versionId, $metadata): void {
            $row = [
                'user_id' => auth()->id(),
                'user_name' => auth()->check() ? auth()->user()->full_name : null,
                'document_id' => $document->id,
                'version_id' => $versionId,
                'action' => $action,
                'ip_address' => request()->ip(),
                'occurred_at' => now(),
            ];

            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $row['user_agent'] = request()->userAgent();
            }
            if ($metadata !== [] && Schema::hasColumn('audit_logs', 'metadata')) {
                $row['metadata'] = $metadata;
            }

            if (
                Schema::hasColumn('audit_logs', 'entry_hash')
                && Schema::hasColumn('audit_logs', 'previous_hash')
                && Schema::hasColumn('audit_logs', 'hash_version')
                && Schema::hasColumn('audit_logs', 'sealed_at')
            ) {
                $previousHash = AuditLog::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->value('entry_hash');

                $row['hash_version'] = 'hmac-sha256-v1';
                $row['previous_hash'] = $previousHash ?: null;
                $row['entry_hash'] = self::computeEntryHash($row, $row['previous_hash']);
                $row['sealed_at'] = now();
            }

            AuditLog::create($row);
        });
    }

    /**
     * Trace après suppression définitive (forceDelete) : sans FK document/version (lignes cibles supprimées).
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function logPermanentDocumentDestruction(
        int $documentId,
        ?string $title,
        ?string $filePath,
        ?int $userId = null
    ): void {
        DB::transaction(function () use ($documentId, $title, $filePath, $userId): void {
            $metadata = [
                'document_id' => $documentId,
                'title' => $title,
                'file_path' => $filePath,
                'destroyed_at' => now()->toIso8601String(),
            ];

            $actor = $userId ? \App\Models\User::query()->find($userId) : null;

            $row = [
                'user_id' => $userId,
                'user_name' => $actor?->full_name,
                'document_id' => null,
                'version_id' => null,
                'action' => 'document_permanently_destroyed',
                'ip_address' => request()->ip(),
                'occurred_at' => now(),
            ];

            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $row['user_agent'] = request()->userAgent();
            }
            if (Schema::hasColumn('audit_logs', 'metadata')) {
                $row['metadata'] = $metadata;
            }

            if (
                Schema::hasColumn('audit_logs', 'entry_hash')
                && Schema::hasColumn('audit_logs', 'previous_hash')
                && Schema::hasColumn('audit_logs', 'hash_version')
                && Schema::hasColumn('audit_logs', 'sealed_at')
            ) {
                $previousHash = AuditLog::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->value('entry_hash');

                $row['hash_version'] = 'hmac-sha256-v1';
                $row['previous_hash'] = $previousHash ?: null;
                $row['entry_hash'] = self::computeEntryHash($row, $row['previous_hash']);
                $row['sealed_at'] = now();
            }

            AuditLog::withoutGlobalScopes()->create($row);
        });
    }

    public static function computeEntryHash(array $row, ?string $previousHash): string
    {
        $payload = [
            'user_id' => $row['user_id'] ?? null,
            'user_name' => $row['user_name'] ?? null,
            'document_id' => $row['document_id'] ?? null,
            'version_id' => $row['version_id'] ?? null,
            'action' => $row['action'] ?? null,
            'ip_address' => $row['ip_address'] ?? null,
            'user_agent' => $row['user_agent'] ?? null,
            'occurred_at' => self::formatTimestamp($row['occurred_at'] ?? null),
            'metadata' => self::normalizeForHash($row['metadata'] ?? null),
            'previous_hash' => $previousHash,
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            $jsonPayload = '{}';
        }

        return hash_hmac('sha256', $jsonPayload, self::resolveHmacSecret());
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function normalizeForHash($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);
        if ($isAssoc) {
            ksort($value);
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = self::normalizeForHash($nestedValue);
        }

        return $value;
    }

    private static function resolveHmacSecret(): string
    {
        $configured = (string) config('audit.hmac_key', '');
        if ($configured !== '') {
            return $configured;
        }

        $appKey = (string) config('app.key', '');
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $appKey;
    }

    private static function formatTimestamp(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.u');
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
