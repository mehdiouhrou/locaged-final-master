<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PurgeAuditLogs extends Command
{
    protected $signature = 'audit:purge-old-logs {--months=} {--dry-run}';

    protected $description = 'Purge old audit logs while preserving destruction traces';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: config('audit.log_retention_months', 6));
        $months = max($months, 1);

        $cutoff = now()->subMonths($months);

        $query = AuditLog::withoutGlobalScopes()
            ->where('occurred_at', '<', $cutoff)
            ->whereNotIn('action', ['document.destroy', 'destroy', 'permanently_deleted']);

        $count = (clone $query)->count();

        if ((bool) $this->option('dry-run')) {
            $this->info("Dry-run: {$count} logs would be deleted (cutoff {$cutoff->toDateTimeString()}).");

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->chunkById(1000, function ($rows) use (&$deleted) {
            $ids = $rows->pluck('id')->all();
            if (! empty($ids)) {
                $deleted += AuditLog::withoutGlobalScopes()->whereIn('id', $ids)->delete();
            }
        });

        $this->info("Deleted {$deleted} audit log rows older than {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
