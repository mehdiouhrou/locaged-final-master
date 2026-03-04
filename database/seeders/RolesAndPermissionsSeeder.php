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
        // Vider le cache Spatie avant tout
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ----------------------------------------------------------------
        // 1. Liste complète de toutes les permissions de l'application
        // ----------------------------------------------------------------
        // Hiérarchie SPC Rabat :
        // Department   = Pôle         (ex: Pôle Technique)
        // SubDepartment = Unité        (ex: Unité Eau Potable)
        // Service       = Cellule      (ex: Cellule Investissements EP)
        // ----------------------------------------------------------------
        $permissionNames = [
            // Documents
            'view any document',              // Tout voir (DG, Assistante, master)
            'view department document',       // Pôle OU Unité (GlobalScope décide selon le rôle)
            'view service document',          // Cellule uniquement
            'view own document',              // Ses propres documents
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

        // Créer ou retrouver chaque permission
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        // Helper : récupérer plusieurs permissions par nom
        $pick = function (array $names) use ($permissions) {
            return collect($names)
                ->filter(fn($n) => isset($permissions[$n]))
                ->map(fn($n) => $permissions[$n])
                ->all();
        };

        // ----------------------------------------------------------------
        // 2. Assignation des permissions par rôle
        // ----------------------------------------------------------------

        // ── RÔLE 1 : master (équipe LocaGed — accès total) ──────────────
        // Accès absolu à tout, y compris pages techniques
        Role::firstOrCreate(['name' => 'master'])
            ->syncPermissions(array_values($permissions));

        // ── RÔLE 2 : Directrice du SPCR ─────────────────────────────────
        // Portée : TOUTE la structure
        // ✅ Accès métier complet sur tous les documents
        // ✅ Gestion structure (catégories, départements, tags, lieux, services)
        // ❌ Pas de gestion utilisateurs (réservé IT Admin uniquement)
        // ❌ Pas de : Workflow, OCR, Traduction, Rôles
        Role::firstOrCreate(['name' => 'Directrice du SPCR'])
            ->syncPermissions($pick([
                // Documents — accès total
                'view any document',
                'create document',
                'update document',
                'delete document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture seule (création réservée IT Admin)
                'view any user',

                // Départements / Pôles
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

                // Emplacements physiques
                'view any physical location',
                'create physical location',
                'update physical location',
                'delete physical location',

                // Services / Cellules
                'view any service',
                'create service',
                'update service',
                'delete service',

                // Demandes de destruction
                'view any document destruction request',
                'approve document destruction request',
                'decline document destruction request',
                'delete document destruction request',
            ]));

        // ── RÔLE 3 : IT Admin (Mme. MERZGIOUI) ──────────────────────────
        // Portée documents : sa Cellule (Service) uniquement
        // ✅ SEUL rôle autorisé à créer/modifier/supprimer des utilisateurs
        // ✅ Peut créer des catégories
        // ❌ Pas de : Rôles, Traduction, OCR, Workflow
        // ❌ Ne voit pas les documents des autres services/pôles
        Role::firstOrCreate(['name' => 'IT Admin'])
            ->syncPermissions($pick([
                // Documents — sa cellule seulement
                'view service document',
                'view own document',
                'create document',
                'update document',

                // Utilisateurs — SEUL rôle avec gestion complète
                'view any user',
                'create user',
                'update user',
                'delete user',

                // Départements / Pôles — lecture seule (navigation)
                'view any department',

                // Catégories — peut créer et modifier
                'view any category',
                'create category',
                'update category',

                // Tags
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — lecture seule
                'view any physical location',

                // Services / Cellules — lecture seule
                'view any service',
            ]));

        // ── RÔLE 4 : Assistante de Direction (Mme. Bouchra OUBARI) ──────
        // Portée : TOUTE la structure
        // ✅ Voir, ajouter, modifier, approuver, refuser les documents
        // ✅ Peut créer des emplacements physiques
        // ❌ Pas de suppression de documents
        // ❌ Pas de création d'utilisateurs ni de catégories
        Role::firstOrCreate(['name' => 'Assistante de Direction'])
            ->syncPermissions($pick([
                // Documents — accès large sans suppression
                'view any document',
                'create document',
                'update document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture seule
                'view any user',

                // Départements / Pôles — lecture seule
                'view any department',

                // Catégories — lecture seule
                'view any category',

                // Tags
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — peut créer et modifier
                'view any physical location',
                'create physical location',
                'update physical location',

                // Services / Cellules — lecture seule
                'view any service',
            ]));

        // ── RÔLE 5 : Chef de Pôle ────────────────────────────────────────
        // Portée : tout son Pôle (Department)
        // Le GlobalScope filtre par department_id automatiquement
        // ✅ Voir/ajouter/modifier/approuver/refuser les docs de tout son pôle
        // ✅ Toutes les Unités et Cellules de son pôle sont visibles
        // ❌ Pas de création d'emplacements physiques
        // ❌ Pas de gestion utilisateurs, Workflow, OCR, Traduction
        Role::firstOrCreate(['name' => 'Chef de Pôle'])
            ->syncPermissions($pick([
                // Documents — tout son pôle (GlobalScope → filtre par department_id)
                'view department document',
                'view own document',
                'create document',
                'update document',
                'delete document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture pour son pôle
                'view department user',
                'view own user',

                // Structure — lecture seule
                'view any department',
                'view any category',

                // Tags — peut créer et modifier
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — LECTURE SEULE
                'view any physical location',

                // Services / Cellules — lecture seule
                'view any service',

                // Demandes de destruction — pour son pôle
                'view department document destruction request',
                'approve document destruction request',
                'decline document destruction request',
            ]));

        // ── RÔLE 6 : Chef de Département ─────────────────────────────────
        // Portée : toute son Unité (SubDepartment) = toutes ses Cellules
        // Le GlobalScope filtre par sub_department_id automatiquement
        // ✅ Voit TOUTES les Cellules sous son Unité
        // ✅ Peut approuver et refuser les documents
        // ❌ Pas de création d'emplacements physiques ni d'utilisateurs
        Role::firstOrCreate(['name' => 'Chef de Département'])
            ->syncPermissions($pick([
                // Documents — toute son unité (GlobalScope → filtre par sub_department_id)
                'view department document',
                'view own document',
                'create document',
                'update document',
                'approve document',
                'decline document',

                // Utilisateurs — lecture pour son unité
                'view department user',
                'view own user',

                // Structure — lecture seule
                'view any department',
                'view any category',

                // Tags — peut créer
                'view any tag',
                'create tag',
                'update tag',

                // Emplacements physiques — lecture seule
                'view any physical location',

                // Services / Cellules — lecture seule
                'view any service',

                // Demandes de destruction — pour son unité
                'view department document destruction request',
                'approve document destruction request',
                'decline document destruction request',
            ]));

        // ── RÔLE 7 : user (simple collaborateur) ─────────────────────────
        // Portée : sa Cellule (Service) uniquement
        // Le GlobalScope filtre par service_id automatiquement
        // ✅ Voir et ajouter des documents de sa cellule
        // ❌ Pas d'approbation, pas de gestion
        Role::firstOrCreate(['name' => 'user'])
            ->syncPermissions($pick([
                // Documents — sa cellule seulement (GlobalScope → filtre par service_id)
                'view service document',
                'view own document',
                'create document',
                'update document',

                // Structure — lecture seule
                'view any category',
                'view any tag',
                'create tag',
            ]));
    }
}