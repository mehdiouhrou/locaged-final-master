<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ---------------------------------------------------------------------
        // 1. Define all permission names used in the application (plus new ones)
        // ---------------------------------------------------------------------
        $permissionNames = [
            // Documents
            'view any document',
            'view department document',
            'view service document', // NEW: service-level scope
            'view own document',
            'upload document',
            'download document',
            'create document',
            'update document',
            'delete document',
            'restore document',
            'forceDelete document',
            'approve document',
            'decline document',

            // Users
            'view any user',
            'view department user',
            'view service user', // NEW: service-level user visibility
            'view own user',
            'create user',
            'update user',
            'delete user',
            'restore user',
            'forceDelete user',

            // Departments
            'view any department',
            'create department',
            'update department',
            'delete department',
            'restore department',
            'forceDelete department',

            // Roles
            'view any role',
            'create role',
            'update role',
            'delete role',
            'restore role',
            'forceDelete role',

            // Categories
            'view any category',
            'create category',
            'update category',
            'delete category',
            'restore category',
            'forceDelete category',

            // Tags
            'view any tag',
            'create tag',
            'update tag',
            'delete tag',
            'restore tag',
            'forceDelete tag',

            // Physical locations
            'view any physical location',
            'create physical location',
            'update physical location',
            'delete physical location',
            'restore physical location',
            'forceDelete physical location',

            // Services (logical services under sub-departments)
            'view any service',
            'create service',
            'update service',
            'delete service',
            'restore service',
            'forceDelete service',

            // Workflow rules
            'view any workflow rule',
            'view department workflow rule',
            'create workflow rule',
            'update workflow rule',
            'delete workflow rule',
            'restore workflow rule',
            'forceDelete workflow rule',

            // Document destruction requests
            'view any document destruction request',
            'view department document destruction request',
            'view own document destruction request',
            'create document destruction request',
            'update document destruction request',
            'delete document destruction request',
            'restore document destruction request',
            'forceDelete document destruction request',
            'approve document destruction request',
            'decline document destruction request',

            // OCR jobs
            'view any ocr job',
            'view department ocr job',
            'view own ocr job',
            'create ocr job',
            'update ocr job',
            'delete ocr job',
            'restore ocr job',
            'forceDelete ocr job',

            // UI Translations / Localization
            'view any ui translation',
            'create ui translation',
            'update ui translation',
            'delete ui translation',
            'restore ui translation',
            'forceDelete ui translation',

            // Access profiles (V2 GED)
            'manage profiles',
            'view any profile',
            'create profile',
            'update profile',
            'delete profile',
            'restore profile',
            'forceDelete profile',

            // Organizational structure management
            'manage structures',

            // Horizon & journaux d'audit centralisés (spec alignement)
            'access horizon',
            'view system activity log',
            'view audit log',

            // Expirations / destructions (pages documents expirés, journaux de suppression)
            'access document expiration management',
            'postpone document expiration',

            // Rapports / listes transverses (remplace les checks « super admin » par rôle)
            'view organization wide reports',
            'manage document global expiry',

            // Filtres journaux d’activité & suppressions (remplace hasRole dans Livewire)
            'filter audit logs by assigned departments',
            'filter audit logs by assigned subdepartments',
            'filter audit logs by assigned services',

            // Scope documents legacy « Division Chief » (sous-départements assignés)
            'view subdepartment scoped documents',

            // Navigation back-office (exclut le rôle utilisateur service)
            'access management sidebar',
        ];

        // Create (or find) all permissions
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Helper to fetch Permission models by name
        $pick = function (array $names) use ($permissions) {
            return collect($names)
                ->filter(fn ($name) => isset($permissions[$name]))
                ->map(fn ($name) => $permissions[$name])
                ->all();
        };

        // ------------------------------------------------------------------
        // 2. Define per-role permission sets based on required responsibilities
        // ------------------------------------------------------------------

        // Master: full access to everything
        $masterRole = Role::firstOrCreate(['name' => 'master']);
        $masterRole->syncPermissions(array_values($permissions));

        // Super Administrator (General Direction)
        $superAdminPermissions = $pick([
            // Documents
            'view any document',
            'create document',
            'update document',
            'delete document',
            'approve document',
            'decline document',

            // Users
            'view any user',
            'create user',
            'update user',
            'delete user',

            // Departments
            'view any department',
            'create department',
            'update department',
            'delete department',

            // Categories / Tags / Locations / Services
            'view any category', 'create category', 'update category', 'delete category',
            'view any tag', 'create tag', 'update tag', 'delete tag',
            'view any physical location', 'create physical location', 'update physical location', 'delete physical location',
            'view any service', 'create service', 'update service', 'delete service',

            // Workflow rules
            'view any workflow rule', 'create workflow rule', 'update workflow rule', 'delete workflow rule',

            // Destruction requests
            'view any document destruction request',
            'view department document destruction request',
            'approve document destruction request',
            'decline document destruction request',
            'delete document destruction request',

            // OCR jobs (operational access; Master controls configuration)
            'view any ocr job',

            // Profils d'accès catégories
            'view any profile', 'create profile', 'update profile', 'delete profile',

            'access horizon',
            'view system activity log',

            'access document expiration management',
            'postpone document expiration',

            'view organization wide reports',
            'manage document global expiry',
        ]);
        Role::firstOrCreate(['name' => 'Super Administrator'])->syncPermissions($superAdminPermissions);

        // Department admin ("Admin de pole")
        $departmentAdminPermissions = $pick([
            // Documents within own poles/structures
            'view department document',
            'view own document',
            'create document',
            'update document',
            'delete document',
            'approve document',
            'decline document',

            // Users within own departments
            'view department user',
            'view own user',
            'create user',
            'update user',
            'delete user',

            // Read departments to manage their own scope
            'view any department',

            // Tags: View, Create, Update (NO DELETE)
            'view any tag', 'create tag', 'update tag',

            // Categories: View, Create, Update (NO DELETE)
            'view any category', 'create category', 'update category',

            // Physical locations: View, Create, Update (NO DELETE)
            'view any physical location', 'create physical location', 'update physical location',

            // Services / workflow rules for their departments
            'view any service', 'create service', 'update service', 'delete service',
            'view any workflow rule', 'view department workflow rule', 'create workflow rule', 'update workflow rule', 'delete workflow rule',

            // Profils d'accès (pôle)
            'view any profile', 'create profile', 'update profile', 'delete profile',

            'view system activity log',

            // Destruction requests in their departments
            'view department document destruction request',
            'approve document destruction request',
            'decline document destruction request',

            'access document expiration management',
            'postpone document expiration',

            'filter audit logs by assigned departments',
            'access management sidebar',
        ]);
        $departmentAdminRole = Role::firstOrCreate(['name' => 'Admin de pole']);
        $departmentAdminRole->syncPermissions($departmentAdminPermissions);

        // Sub-department admin ("Admin de departments")
        $divisionChiefPermissions = $pick([
            // Documents within own departments (scoped via poles/sub-departments)
            'view department document',
            'view own document',
            'create document',
            'update document',
            'approve document',
            'decline document',

            // Users within own departments
            'view department user',
            'view own user',
            'create user',
            'update user',

            // Categories: View ONLY (no create/update/delete)
            'view any category',

            // Tags: View, Create, Update (NO DELETE)
            'view any tag', 'create tag', 'update tag',

            // Physical locations: View, Create, Update (NO DELETE)
            'view any physical location', 'create physical location', 'update physical location',

            'view department document destruction request',
            'approve document destruction request',
            'decline document destruction request',
            'access document expiration management',
            'postpone document expiration',

            'filter audit logs by assigned subdepartments',
            'view subdepartment scoped documents',
            'access management sidebar',
        ]);
        $divisionChiefRole = Role::firstOrCreate(['name' => 'Admin de departments']);
        $divisionChiefRole->syncPermissions($divisionChiefPermissions);

        // Service Manager ("Admin de cellule")
        $serviceManagerPermissions = $pick([
            // Documents in assigned service/cellule
            'view service document',
            'view own document',
            'create document',
            'update document',
            'approve document',
            'decline document',

            // Categories (service-level classification management - NO DELETE)
            'view any category',
            'create category',
            'update category',

            // Tags: View, Create, Update (NO DELETE)
            'view any tag', 'create tag', 'update tag',

            // Users: can manage (create/update) users in their services
            'view service user',
            'create user',
            'update user',

            // Physical locations: View, Create, Update (NO DELETE)
            'view any physical location', 'create physical location', 'update physical location',

            'access document expiration management',

            'filter audit logs by assigned services',
            'access management sidebar',
        ]);
        $serviceManagerRole = Role::firstOrCreate(['name' => 'Admin de cellule']);
        $serviceManagerRole->syncPermissions($serviceManagerPermissions);

        // Service User → mapped to generic "user" role
        $serviceUserPermissions = $pick([
            // Documents in assigned cellule/service
            'view service document',
            'view own document',
            'create document',
            'update document',

            // Categories: READ-ONLY access (can see but not modify)
            'view any category',

            // Tags: can view + create personal/common tags (no update/delete)
            'view any tag',
            'create tag',
        ]);
        $serviceUserRole = Role::firstOrCreate(['name' => 'user']);
        $serviceUserRole->syncPermissions($serviceUserPermissions);

        // Legacy admin role kept for compatibility
        // Admin: close to Super Admin but without system-level pages
        $adminPermissions = $pick([
            'view any document', 'create document', 'update document', 'delete document', 'approve document', 'decline document',
            'view any user', 'create user', 'update user', 'delete user',
            'view any department', 'create department', 'update department', 'delete department',
            'view any category', 'create category', 'update category', 'delete category',
            'view any tag', 'create tag', 'update tag', 'delete tag',
            'view any physical location', 'create physical location', 'update physical location', 'delete physical location',
            'view any service', 'create service', 'update service', 'delete service',
            'view any profile', 'create profile', 'update profile', 'delete profile',

            'access horizon',
            'view system activity log',

            'access document expiration management',
            'postpone document expiration',

            'access management sidebar',
        ]);
        Role::firstOrCreate(['name' => 'admin'])->syncPermissions($adminPermissions);

        // Note: no separate basic "user" role anymore; "user" is the service-level user.

        // Rôles libellés en anglais ou hérités : accès file expiration / destructions
        $expirationPerm = $permissions['access document expiration management'] ?? null;
        $deptDestructionPerm = $permissions['view department document destruction request'] ?? null;
        if ($expirationPerm) {
            $legacyDeptScoped = [
                'Department Administrator',
                'Division Chief',
                'Pole Admin',
                'admin de pôle',
            ];
            $legacyServiceScoped = [
                'Service Manager',
                'super administrator',
                'super_admin',
            ];
            foreach ($legacyDeptScoped as $legacyRoleName) {
                $r = Role::where('name', $legacyRoleName)->first();
                if ($r) {
                    $r->givePermissionTo(array_values(array_filter([
                        $expirationPerm,
                        $deptDestructionPerm,
                    ])));
                }
            }
            foreach ($legacyServiceScoped as $legacyRoleName) {
                $r = Role::where('name', $legacyRoleName)->first();
                if ($r) {
                    $r->givePermissionTo($expirationPerm);
                }
            }
        }

        $orgWide = $permissions['view organization wide reports'] ?? null;
        if ($orgWide) {
            foreach (['super administrator', 'super_admin'] as $rn) {
                if ($r = Role::where('name', $rn)->first()) {
                    $r->givePermissionTo($orgWide);
                }
            }
        }

        $filterDept = $permissions['filter audit logs by assigned departments'] ?? null;
        if ($filterDept && ($r = Role::where('name', 'Department Administrator')->first())) {
            $r->givePermissionTo($filterDept);
        }

        $filterSub = $permissions['filter audit logs by assigned subdepartments'] ?? null;
        $subDoc = $permissions['view subdepartment scoped documents'] ?? null;
        if ($r = Role::where('name', 'Division Chief')->first()) {
            foreach (array_filter([$filterSub, $subDoc]) as $p) {
                $r->givePermissionTo($p);
            }
        }

        $filterSvc = $permissions['filter audit logs by assigned services'] ?? null;
        if ($filterSvc && ($r = Role::where('name', 'Service Manager')->first())) {
            $r->givePermissionTo($filterSvc);
        }

        $sidebar = $permissions['access management sidebar'] ?? null;
        if ($sidebar) {
            foreach (['Department Administrator', 'Pole Admin', 'admin de pôle'] as $rn) {
                if ($r = Role::where('name', $rn)->first()) {
                    $r->givePermissionTo($sidebar);
                }
            }
        }

        $manageExpiry = $permissions['manage document global expiry'] ?? null;
        if ($manageExpiry && ($r = Role::where('name', 'super administrator')->first())) {
            $r->givePermissionTo($manageExpiry);
        }

        // ------------------------------------------------------------------
        // 3. Spec V2 role matrix aliases (kept alongside legacy role names)
        // ------------------------------------------------------------------
        $matrixRolePermissions = [
            'Directrice du SPCR' => [
                'view any document',
                'create document',
                'download document',
                'approve document',
                'decline document',
                'delete document',
                'forceDelete document',
                'view audit log',
                'view system activity log',
                'view organization wide reports',
                'access document expiration management',
            ],
            'IT Admin' => [
                'view service document',
                'view own document',
                'upload document',
                'create document',
                'update document',
                'download document',
                'create user',
                'update user',
                'delete user',
                'manage profiles',
                'manage structures',
                'create category',
                'update category',
                'delete category',
                'view audit log',
                'view any profile',
                'create profile',
                'update profile',
                'delete profile',
            ],
            'Assistante de Direction' => [
                'view any document',
                'create document',
                'download document',
                'view audit log',
            ],
            'Chef de Pôle' => [
                'view department document',
                'view own document',
                'upload document',
                'create document',
                'update document',
                'download document',
                'approve document',
                'decline document',
                'delete document',
                'view any physical location',
                'create physical location',
                'update physical location',
                'access document expiration management',
                'postpone document expiration',
            ],
            'Chef de Département' => [
                'view department document',
                'view subdepartment scoped documents',
                'view own document',
                'upload document',
                'create document',
                'update document',
                'download document',
                'approve document',
                'decline document',
                'view any physical location',
                'create physical location',
                'update physical location',
                'access document expiration management',
                'postpone document expiration',
            ],
            'Chargée de dépôt' => [
                'view own document',
                'upload document',
                'create document',
                'view any category',
            ],
        ];

        foreach ($matrixRolePermissions as $roleName => $permissionNamesForRole) {
            Role::firstOrCreate(['name' => $roleName])
                ->syncPermissions($pick($permissionNamesForRole));
        }

        // Keep legacy "user" and service roles aligned with the new document actions.
        foreach (['user', 'Admin de cellule', 'Admin de departments', 'Admin de pole', 'Super Administrator', 'admin'] as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $grant = [];
                if ($permissions['create document'] ?? null) {
                    $grant[] = $permissions['create document'];
                }
                if ($permissions['upload document'] ?? null) {
                    $grant[] = $permissions['upload document'];
                }
                if ($permissions['download document'] ?? null) {
                    $grant[] = $permissions['download document'];
                }
                if ($permissions['view audit log'] ?? null) {
                    $grant[] = $permissions['view audit log'];
                }
                if ($permissions['manage profiles'] ?? null && in_array($roleName, ['admin', 'Super Administrator'], true)) {
                    $grant[] = $permissions['manage profiles'];
                }
                if ($permissions['manage structures'] ?? null && in_array($roleName, ['admin', 'Super Administrator', 'Admin de pole'], true)) {
                    $grant[] = $permissions['manage structures'];
                }

                if (! empty($grant)) {
                    $r->givePermissionTo($grant);
                }
            }
        }
    }
}
