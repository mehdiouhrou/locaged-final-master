<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\Service;
use App\Models\Subcategory;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Données minimales + comptes de test après migrate:fresh (rôles clients).
 *
 * @see RolesAndPermissionsSeeder
 */
class QaEnvironmentSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'Password123!';

    public function run(): void
    {
        $dept = Department::query()->create([
            'name' => 'Pôle démo QA',
            'description' => 'Structure de test (scope Chef de pôle / Agent / Approbateur)',
        ]);

        $sub = SubDepartment::query()->create([
            'department_id' => $dept->id,
            'name' => 'Sous-pôle démo',
        ]);

        $service = Service::query()->create([
            'sub_department_id' => $sub->id,
            'name' => 'Service démo',
        ]);

        $category = Category::withoutGlobalScopes()->create([
            'name' => 'Catégorie démo',
            'description' => 'QA',
            'expiry_value' => 5,
            'expiry_unit' => 'years',
        ]);

        $category->services()->sync([$service->id]);

        Subcategory::query()->create([
            'category_id' => $category->id,
            'name' => 'Général',
        ]);

        $users = [
            [
                'email' => 'master@qa.locaged.test',
                'full_name' => 'Master (LocaGed)',
                'role' => 'master',
                'departments' => [],
                'services' => [],
            ],
            [
                'email' => 'direction@qa.locaged.test',
                'full_name' => 'Direction',
                'role' => 'Direction',
                'departments' => [],
                'services' => [],
            ],
            [
                'email' => 'it@qa.locaged.test',
                'full_name' => 'IT Admin',
                'role' => 'IT Admin',
                'departments' => [],
                'services' => [],
            ],
            [
                'email' => 'chef.pole@qa.locaged.test',
                'full_name' => 'Chef de pôle',
                'role' => 'Chef de Pôle',
                'departments' => [$dept->id],
                'services' => [$service->id],
            ],
            [
                'email' => 'approbateur@qa.locaged.test',
                'full_name' => 'Approbateur',
                'role' => 'Approbateur',
                'departments' => [$dept->id],
                'services' => [$service->id],
            ],
            [
                'email' => 'agent@qa.locaged.test',
                'full_name' => 'Agent',
                'role' => 'Agent',
                'departments' => [$dept->id],
                'services' => [$service->id],
            ],
        ];

        foreach ($users as $row) {
            $user = User::query()->create([
                'username' => $row['email'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'locale' => 'fr',
            ]);

            $role = Role::query()->where('name', $row['role'])->first();
            if ($role) {
                $user->assignRole($role);
            }

            if (! empty($row['departments'])) {
                $user->departments()->sync($row['departments']);
            }
            if (! empty($row['services'])) {
                $user->services()->sync($row['services']);
            }
        }

        $this->command?->info('QA: structure +6 utilisateurs créés. Mot de passe: '.self::DEFAULT_PASSWORD);
    }
}
