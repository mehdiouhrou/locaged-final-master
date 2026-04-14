<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditEvidenceService;
use Illuminate\Console\Command;

class ExtractAuditEvidence extends Command
{
    protected $signature = 'audit:extract-proof
        {actor : User ID generating the package}
        {--log-type=all : all|documents|authentication}
        {--date-from= : Start date (Y-m-d)}
        {--date-to= : End date (Y-m-d)}
        {--user-id= : Filter on actor user id}
        {--action-type= : Filter by action/type}
        {--search= : Text search}';

    protected $description = 'Generate signed legal evidence package for audit logs';

    public function handle(AuditEvidenceService $service): int
    {
        $actor = User::find((int) $this->argument('actor'));
        if (! $actor) {
            $this->error('Actor user not found.');

            return self::FAILURE;
        }

        $filters = array_filter([
            'log_type' => $this->option('log-type'),
            'date_from' => $this->option('date-from'),
            'date_to' => $this->option('date-to'),
            'user_id' => $this->option('user-id'),
            'action_type' => $this->option('action-type'),
            'search' => $this->option('search'),
        ], fn ($value) => $value !== null && $value !== '');

        $result = $service->buildSignedEvidencePackage($actor, $filters);

        $this->info('Evidence package created: ' . $result['local_absolute_path']);
        $this->line('Archive status: ' . ($result['immutable_archive']['status'] ?? 'unknown'));
        if (! empty($result['immutable_archive']['path'])) {
            $this->line('WORM path: ' . $result['immutable_archive']['path']);
        }

        return self::SUCCESS;
    }
}
