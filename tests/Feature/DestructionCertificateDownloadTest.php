<?php

use App\Models\Department;
use App\Models\DestructionCertificate;
use App\Models\Document;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('private');
});

test('authorized user can download destruction certificate pdf', function () {
    $dept = Department::query()->create(['name' => 'PV-'.uniqid()]);
    $user = \App\Models\User::factory()->create();
    $user->givePermissionTo('view any document');

    $doc = Document::withoutGlobalScopes()->create([
        'uid' => (string) Str::uuid(),
        'title' => 'Doc PV',
        'category_id' => null,
        'department_id' => $dept->id,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $relative = 'destruction-certificates/test-'.Str::uuid().'.pdf';
    Storage::disk('private')->put($relative, '%PDF-1.4 minimal');

    $cert = DestructionCertificate::query()->create([
        'public_id' => (string) Str::uuid(),
        'document_id' => $doc->id,
        'document_destruction_request_id' => null,
        'approved_by' => $user->id,
        'manifest' => [],
        'pdf_path' => $relative,
    ]);

    $this->actingAs($user)
        ->get(route('destruction-certificates.download', $cert))
        ->assertOk();
});
