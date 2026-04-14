<?php

use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use App\Models\WorkFlowRule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['ged.enforce_workflow_rules' => true]);
});

test('blocked status transition when department has workflow rules', function () {
    $dept = Department::query()->create(['name' => 'WF-'.uniqid()]);

    WorkFlowRule::withoutGlobalScopes()->create([
        'department_id' => $dept->id,
        'category_id' => null,
        'level' => 1,
        'from_status' => 'pending',
        'to_status' => 'approved',
    ]);

    $doc = Document::withoutGlobalScopes()->create([
        'uid' => (string) Str::uuid(),
        'title' => 'WF doc',
        'category_id' => null,
        'department_id' => $dept->id,
        'status' => 'pending',
        'created_by' => null,
    ]);

    expect(fn () => $doc->update(['status' => 'archived']))
        ->toThrow(ValidationException::class);
});

test('allowed transition when rule exists', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $dept = Department::query()->create(['name' => 'WF2-'.uniqid()]);

    WorkFlowRule::withoutGlobalScopes()->create([
        'department_id' => $dept->id,
        'category_id' => null,
        'level' => 1,
        'from_status' => 'pending',
        'to_status' => 'approved',
    ]);

    $doc = Document::withoutGlobalScopes()->create([
        'uid' => (string) Str::uuid(),
        'title' => 'WF doc 2',
        'category_id' => null,
        'department_id' => $dept->id,
        'status' => 'pending',
        'created_by' => null,
    ]);

    $doc->update(['status' => 'approved']);

    expect($doc->fresh()->status)->toBe('approved');
});
