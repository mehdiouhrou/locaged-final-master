<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditService
{
    /**
     * Point d\'entree central. Toutes les autres methodes du service sont des
     * wrappers fins autour de record(). Ne retourne jamais silencieusement :
     * toute action journalisee doit produire une ligne, avec ou sans document
     * associe, via document_id/version_id OU subject_type/subject_id.
     *
     * @param  array<string, mixed>  $options
     */
    public static function record(string $action, array $options = []): void
    {
        DB::transaction(function () use ($action, $options): void {
            $actor = $options['actor'] ?? auth()->user();

            $row = [
                'user_id' => array_key_exists('user_id', $options) ? $options['user_id'] : $actor?->id,
                'user_name' => array_key_exists('user_name', $options) ? $options['user_name'] : $actor?->full_name,
                'document_id' => $options['document_id'] ?? null,
                'version_id' => $options['version_id'] ?? null,
                'action' => $action,
                'ip_address' => $options['ip_address'] ?? request()->ip(),
                'occurred_at' => now(),
            ];

            if (Schema::hasColumn('audit_logs', 'subject_type')) {
                $row['subject_type'] = $options['subject_type'] ?? null;
            }
            if (Schema::hasColumn('audit_logs', 'subject_id')) {
                $row['subject_id'] = $options['subject_id'] ?? null;
            }
            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $row['user_agent'] = $options['user_agent'] ?? request()->userAgent();
            }

            $metadata = $options['metadata'] ?? [];
            if ($metadata !== [] && Schema::hasColumn('audit_logs', 'metadata')) {
                $row['metadata'] = $metadata;
            }

            if (
                Schema::hasColumn('audit_logs', 'entry_hash')
                && Schema::hasColumn('audit_logs', 'previous_hash')
                && Schema::hasColumn('audit_logs', 'hash_version')
                && Schema::hasColumn('audit_logs', 'sealed_at')
                && Schema::hasTable('audit_chain_state')
            ) {
                $chainState = DB::table('audit_chain_state')
                    ->lockForUpdate()
                    ->find(1);

                $previousHash = $chainState->last_hash ?? null;

                $row['hash_version'] = 'hmac-sha256-v2';
                $row['previous_hash'] = $previousHash ?: null;
                $row['entry_hash'] = self::computeEntryHash($row, $row['previous_hash']);
                $row['sealed_at'] = now();

                DB::table('audit_chain_state')
                    ->where('id', 1)
                    ->update([
                        'last_hash' => $row['entry_hash'],
                        'updated_at' => now(),
                    ]);
            }

            AuditLog::withoutGlobalScopes()->create($row);
        });
    }

    /**
     * Journalise une action rattachee a un "sujet" generique (authentification,
     * partage de categorie, workflow collaboratif, export, emplacement physique...)
     * plutot qu\'a un document. subject_type identifie le domaine (ex:
     * \'authentication\', \'category_share\', \'workflow\', \'export\',
     * \'physical_location\'), subject_id l\'identifiant de l\'entite concernee
     * (peut etre null, ex: tentative de login echouee sans user connu).
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function logSubject(
        string $action,
        string $subjectType,
        ?int $subjectId = null,
        array $metadata = [],
        ?User $actor = null,
        ?int $userId = null,
        ?string $userName = null
    ): void {
        $actor = $actor ?? auth()->user();

        self::record($action, [
            'user_id' => $userId ?? $actor?->id,
            'user_name' => $userName ?? $actor?->full_name,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Compare deux jeux d\'attributs (avant/apres) et retourne uniquement les champs
     * qui ont reellement change, sous une forme exploitable directement dans metadata.
     * Usage : capturer $before = $model->only($fields) AVANT le save(), puis appeler
     * diff($before, $model->only($fields), $fields) APRES le save().
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<int, string>  $fields
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(array $before, array $after, array $fields): array
    {
        $changes = [];
        foreach ($fields as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ($old != $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function log(string $action, Document $document, ?int $versionId = null, array $metadata = []): void
    {
        self::record($action, [
            'document_id' => $document->id,
            'version_id' => $versionId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Trace apres suppression definitive (forceDelete) : sans FK document/version (lignes cibles supprimees).
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function logPermanentDocumentDestruction(
        int $documentId,
        ?string $title,
        ?string $filePath,
        ?int $userId = null
    ): void {
        $actor = $userId ? User::query()->find($userId) : null;

        self::record('document_permanently_destroyed', [
            'user_id' => $userId,
            'user_name' => $actor?->full_name,
            'metadata' => [
                'document_id' => $documentId,
                'title' => $title,
                'file_path' => $filePath,
                'destroyed_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Journalise une action effectuee SUR un utilisateur cible (ex: reinitialisation
     * de mot de passe par un admin). user_id = utilisateur CIBLE (pour apparaitre sur
     * sa page Activite), l\'auteur reel de l\'action est dans metadata.performed_by_*.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function logUserAction(string $action, User $targetUser, array $metadata = []): void
    {
        $actor = auth()->user();

        $metadata['performed_by_id'] = $actor?->id;
        $metadata['performed_by_name'] = $actor?->full_name;

        self::record($action, [
            'user_id' => $targetUser->id,
            'user_name' => $targetUser->full_name,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function logCategoryAudit(string $action, array $metadata = []): void
    {
        self::record($action, [
            'metadata' => $metadata,
        ]);
    }

    public static function computeEntryHash(array $row, ?string $previousHash): string
    {
        $payload = [
            'user_id' => $row['user_id'] ?? null,
            'user_name' => $row['user_name'] ?? null,
            'document_id' => $row['document_id'] ?? null,
            'version_id' => $row['version_id'] ?? null,
            'subject_type' => $row['subject_type'] ?? null,
            'subject_id' => $row['subject_id'] ?? null,
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
