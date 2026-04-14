<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Profile;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileCategoryAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ProfileCategoryAccessService caches per user id; RefreshDatabase reuses id 1 between tests.
        Cache::flush();
    }

    public function test_document_scope_restricts_to_profile_categories_when_driver_is_profile(): void
    {
        config(['ged.category_access_driver' => 'profile']);

        $dept = Department::query()->create(['name' => 'Dept Test '.uniqid()]);
        $sub = SubDepartment::query()->create([
            'department_id' => $dept->id,
            'name' => 'Sub Test',
        ]);

        $catAllowed = Category::withoutGlobalScopes()->create([
            'name' => 'Cat Allowed '.uniqid(),
            'expiry_value' => 1,
            'expiry_unit' => 'years',
        ]);

        $catOther = Category::withoutGlobalScopes()->create([
            'name' => 'Cat Other '.uniqid(),
            'expiry_value' => 1,
            'expiry_unit' => 'years',
        ]);

        $profile = Profile::query()->create([
            'name' => 'Profil test',
            'description' => null,
        ]);

        DB::table('profile_category')->insert([
            'profile_id' => $profile->id,
            'category_id' => $catAllowed->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create();
        DB::table('profile_user')->insert([
            'profile_id' => $profile->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docOk = Document::withoutGlobalScopes()->create([
            'uid' => (string) \Illuminate\Support\Str::uuid(),
            'title' => 'Visible',
            'category_id' => $catAllowed->id,
            'department_id' => $dept->id,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $docHidden = Document::withoutGlobalScopes()->create([
            'uid' => (string) \Illuminate\Support\Str::uuid(),
            'title' => 'Hidden',
            'category_id' => $catOther->id,
            'department_id' => $dept->id,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $ids = Document::query()->pluck('id')->all();

        $this->assertContains($docOk->id, $ids);
        $this->assertNotContains($docHidden->id, $ids);
    }

    public function test_profile_model_delegates_accessible_category_ids(): void
    {
        $user = User::factory()->create();
        $res = Profile::getAccessibleCategoryIds($user);
        $this->assertNotNull($res);
        $this->assertTrue($res->isEmpty());
    }
}
