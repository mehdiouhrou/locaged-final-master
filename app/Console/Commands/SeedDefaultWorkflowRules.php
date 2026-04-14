<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\WorkFlowRule;
use Illuminate\Console\Command;

class SeedDefaultWorkflowRules extends Command
{
    protected $signature = 'ged:seed-default-workflow-rules {--dry-run : Afficher les créations sans écrire en base}';

    protected $description = 'Crée des règles workflow par défaut (category_id null) par département pour les transitions courantes, si absentes';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $pairs = [
            ['pending', 'approved'],
            ['pending', 'declined'],
            ['declined', 'pending'],
            ['approved', 'archived'],
        ];

        $created = 0;
        foreach (Department::query()->orderBy('id')->cursor() as $department) {
            foreach ($pairs as [$from, $to]) {
                $exists = WorkFlowRule::withoutGlobalScopes()
                    ->where('department_id', $department->id)
                    ->whereNull('category_id')
                    ->where('from_status', $from)
                    ->where('to_status', $to)
                    ->exists();

                if ($exists) {
                    continue;
                }

                if ($dry) {
                    $this->line("[dry-run] dept {$department->id}: {$from} → {$to}");
                } else {
                    WorkFlowRule::withoutGlobalScopes()->create([
                        'department_id' => $department->id,
                        'category_id' => null,
                        'level' => 1,
                        'from_status' => $from,
                        'to_status' => $to,
                    ]);
                }
                $created++;
            }
        }

        $this->info($dry ? "Dry-run: {$created} règles seraient créées." : "{$created} règle(s) créée(s).");

        return self::SUCCESS;
    }
}
