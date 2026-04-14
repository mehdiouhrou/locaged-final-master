<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Console\Command;

class VerifyAuditIntegrity extends Command
{
    protected $signature = 'audit:verify-integrity {--from-id=} {--to-id=}';

    protected $description = 'Verify audit log cryptographic chain integrity';

    public function handle(): int
    {
        $fromId = $this->option('from-id') ? (int) $this->option('from-id') : null;
        $toId = $this->option('to-id') ? (int) $this->option('to-id') : null;

        $query = AuditLog::withoutGlobalScopes()->orderBy('id');
        if ($fromId) {
            $query->where('id', '>=', $fromId);
        }
        if ($toId) {
            $query->where('id', '<=', $toId);
        }

        $count = 0;
        $verifiedCount = 0;
        $expectedPreviousHash = null;
        $chainStarted = false;
        $firstLogId = null;
        $lastLogId = null;

        foreach ($query->cursor() as $log) {
            $count++;
            $firstLogId ??= $log->id;
            $lastLogId = $log->id;

            if (! $log->entry_hash || ! $log->hash_version) {
                if ($chainStarted) {
                    $this->error("Audit #{$log->id}: unsigned row found after sealed rows.");

                    return self::FAILURE;
                }

                continue;
            }

            if (! $chainStarted) {
                $chainStarted = true;
                $expectedPreviousHash = AuditLog::withoutGlobalScopes()
                    ->where('id', '<', $log->id)
                    ->whereNotNull('entry_hash')
                    ->orderByDesc('id')
                    ->value('entry_hash');
            }

            $actualPreviousHash = $log->previous_hash ?: null;
            if (($expectedPreviousHash ?: null) !== $actualPreviousHash) {
                $this->error("Audit #{$log->id}: previous_hash mismatch.");

                return self::FAILURE;
            }

            $expectedEntryHash = AuditService::computeEntryHash([
                'user_id' => $log->user_id,
                'user_name' => $log->user_name,
                'document_id' => $log->document_id,
                'version_id' => $log->version_id,
                'action' => $log->action,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'occurred_at' => $log->getRawOriginal('occurred_at'),
                'metadata' => $log->metadata,
            ], $actualPreviousHash);

            if (! hash_equals((string) $log->entry_hash, $expectedEntryHash)) {
                $this->error("Audit #{$log->id}: entry_hash mismatch.");

                return self::FAILURE;
            }

            $verifiedCount++;
            $expectedPreviousHash = $log->entry_hash;
        }

        if ($count === 0) {
            $this->warn('No audit rows found for the selected range.');

            return self::SUCCESS;
        }

        if ($verifiedCount === 0) {
            $this->warn("No sealed audit rows found (rows scanned: {$count}).");

            return self::SUCCESS;
        }

        $this->info("Integrity OK on {$verifiedCount} sealed rows (scanned {$count}, from #{$firstLogId} to #{$lastLogId}).");

        return self::SUCCESS;
    }
}
