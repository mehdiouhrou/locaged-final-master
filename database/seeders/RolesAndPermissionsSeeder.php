<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ----------------------------------------------------------------
        // 1. Liste complète de toutes les permissions
        // ----------------------------------------------------------------
        $permissionNames = [
            // Documents
            'view any document',
            'view department document',
            'view service document',
            'view own document',
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
            'view service user',
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

            // Services
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

            // UI Translations
            'view any ui translation',
            'create ui translation',
            'update ui translation',
            'delete ui translation',
            'restore ui translation',
            'forceDelete ui translation',
        ];

        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        $pick = function (array $names) use ($permissions) {
            return collect($names)
                ->filter(fn($n) => isset($permissions[$n]))
                ->map(fn($n) => $permissions[$n])
                ->all();
        };

        // ----------------------------------------------------------------
        // 2. Assignation des permissions par rôle
        // ----------------------------------------------------------------

        // ── RÔLE 1 : master ─────────────────────────────────────────────
        Role::firstOrCreate(['name' => 'master'])
            ->syncPermissions(array_values($permissions));

        // ── RÔLE 2 : Directrice du SPCR ─────────────────────────────────
        // Portée totale — approbation, audit, destruction, emplacements physiques
        // ❌ Pas de gestion utilisateurs (réservé IT Admin)
        Role::firstOrCreate(['name' => 'Directrice du SPCR'])
            ->syncPermissions($pick([
                // Documents
                'view any document',
                'create document',
                'update document',
                'delete document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture seule
                'view any user',

                // Départements
                'view any department',
                'create department',
                'update department',
                'delete department',

                // Catégories
                'view any category',
                'create category',
                'update category',
                'delete category',

                // Tags
                'view any tag',
                'create tag',
                'update tag',
                'delete tag',

                // Emplacements physiques — accès complet
                'view any physical location',
                'create physical location',
                'update physical location',
                'delete physical location',

                // Services
                'view any service',
                'create service',
                'update service',
                'delete service',

                // Destruction — accès complet (DG + Chef de Pôle uniquement)
                'view any document destruction request',
                'approve document destruction request',
                'decline document destruction request',
                'delete document destruction request',

                // Audit — accès complet (DG + IT Admin uniquement)
                'view any ocr job',
            ]));

        // ── RÔLE 3 : IT Admin ───────────────────────────────────────────
        // ✅ SEUL rôle avec gestion utilisateurs
        // ✅ Accès audit (DG + IT Admin uniquement)
        // ❌ Pas destruction, pas emplacements physiques
        Role::firstOrCreate(['name' => 'IT Admin'])
            ->syncPermissions($pick([
                // Documents — sa cellule
                'view service document',
                'view own document',
                'create document',
                'update document',
                'approve document',
                'decline document',

                // Utilisateurs — SEUL rôle avec gestion complète
                'view any user',
                'create user',
                'update user',
                'delete user',

                // Structure — lecture
                'view any department',
                'view any category',
                'create category',
                'update category',
                'view any tag',
                'create tag',
                'update tag',
                'view any service',

                // Emplacements physiques — lecture seule
                'view any physical location',

                // Audit — accès complet (DG + IT Admin uniquement)
                'view any ocr job',
            ]));

        // ── RÔLE 4 : Assistante de Direction ────────────────────────────
        // Portée totale — lecture/écriture
        // ❌ Ne peut PAS approuver ni refuser
        // ❌ Pas de destruction, pas d'audit
        Role::firstOrCreate(['name' => 'Assistante de Direction'])
            ->syncPermissions($pick([
                // Documents — sans approbation
                'view any document',
                'create document',
                'update document',

                // Utilisateurs — lecture seule
                'view any user',

                // Structure — lecture seule
                'view any department',
                'view any category',
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — lecture seule
                'view any physical location',

                // Services — lecture seule
                'view any service',
            ]));

        // ── RÔLE 5 : Chef de Pôle ───────────────────────────────────────
        // Portée : tout son pôle
        // ✅ Approbation, destruction (DG + Chef de Pôle), emplacements physiques
        // ❌ Pas d'audit, pas de gestion utilisateurs
        Role::firstOrCreate(['name' => 'Chef de Pôle'])
            ->syncPermissions($pick([
                // Documents — tout son pôle
                'view department document',
                'view own document',
                'create document',
                'update document',
                'delete document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture son pôle
                'view department user',
                'view own user',

                // Structure — lecture
                'view any department',
                'view any category',
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — peut créer et modifier
                'view any physical location',
                'create physical location',
                'update physical location',

                // Services — lecture
                'view any service',

                // Destruction — accès complet (DG + Chef de Pôle uniquement)
                'view department document destruction request',
                'approve document destruction request',
                'decline document destruction request',
            ]));

        // ── RÔLE 6 : Chef de Département ────────────────────────────────
        // Portée : son unité
        // ✅ Approbation, emplacements physiques
        // ❌ Pas de destruction, pas d'audit
        Role::firstOrCreate(['name' => 'Chef de Département'])
            ->syncPermissions($pick([
                // Documents — toute son unité
                'view department document',
                'view own document',
                'create document',
                'update document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture son unité
                'view department user',
                'view own user',

                // Structure — lecture
                'view any department',
                'view any category',
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — peut créer et modifier
                'view any physical location',
                'create physical location',
                'update physical location',

                // Services — lecture
                'view any service',

                // Destruction — lecture seule (approbation réservée DG + Chef de Pôle)
                'view department document destruction request',
            ]));

        // ── RÔLE 7 : user ───────────────────────────────────────────────
        // Portée : sa cellule uniquement
        Role::firstOrCreate(['name' => 'user'])
            ->syncPermissions($pick([
                'view service document',
                'view own document',
                'create document',
                'update document',
                'view any category',
                'view any tag',
                'create tag',
            ]));

        // ── RÔLE 8 : Chargée de dépôt (Mme. Aicha EL GHANDOURI) ────────
        // Upload uniquement dans toutes les catégories autorisées
        // ❌ Pas d'approbation, pas de consultation étendue, pas de gestion
        Role::firstOrCreate(['name' => 'Chargée de dépôt'])
            ->syncPermissions($pick([
                'create document',
                'view own document',
                'view any category',
            ]));
    }
}
