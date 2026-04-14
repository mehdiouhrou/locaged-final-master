<?php

use App\Models\Department;
use App\Models\WorkFlowRule;

test('ged seed default workflow rules creates rules per department', function () {
    $dept = Department::query()->create(['name' => 'Dept-'.uniqid()]);

    expect(WorkFlowRule::withoutGlobalScopes()->where('department_id', $dept->id)->count())->toBe(0);

    $this->artisan('ged:seed-default-workflow-rules')->assertSuccessful();

    expect(WorkFlowRule::withoutGlobalScopes()->where('department_id', $dept->id)->count())->toBe(4);
});

test('ged seed default workflow rules dry run does not persist', function () {
    Department::query()->create(['name' => 'Dept-'.uniqid()]);

    $this->artisan('ged:seed-default-workflow-rules', ['--dry-run' => true])->assertSuccessful();

    expect(WorkFlowRule::withoutGlobalScopes()->count())->toBe(0);
});
