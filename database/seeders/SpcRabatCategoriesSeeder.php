<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Service;

class SpcRabatCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::withoutGlobalScopes()->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ── PÔLE TECHNIQUE ─────────────────────────────────────────
        $poleTech   = Department::where('name', 'Pôle Technique')->first();
        $unitEP     = SubDepartment::where('name', 'Unité Eau Potable')->first();
        $cellInvEP  = Service::where('name', 'Cellule Investissements Eau Potable')->first();
        $unitAS     = SubDepartment::where('name', 'Unité Assainissement')->first();
        $cellInvAS  = Service::where('name', 'Cellule Investissements Assainissement')->first();

        foreach ([
            ['name' => 'Marchés de travaux EP',  'description' => 'Contrats et marchés liés aux travaux d\'eau potable',       'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Rapports techniques EP',  'description' => 'Rapports d\'avancement et de réception eau potable',        'expiry_value' => 5,  'expiry_unit' => 'years'],
            ['name' => 'PV de réception EP',      'description' => 'Procès-verbaux de réception des travaux eau potable',       'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create(array_merge($cat, [
                'department_id'     => $poleTech?->id,
                'sub_department_id' => $unitEP?->id,
                'service_id'        => $cellInvEP?->id,
            ]));
        }

        foreach ([
            ['name' => 'Marchés de travaux AS',  'description' => 'Contrats et marchés liés aux travaux d\'assainissement',    'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Rapports techniques AS',  'description' => 'Rapports d\'avancement et de réception assainissement',     'expiry_value' => 5,  'expiry_unit' => 'years'],
            ['name' => 'Plans d\'exécution AS',   'description' => 'Plans et schémas d\'exécution des réseaux assainissement',  'expiry_value' => 15, 'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create(array_merge($cat, [
                'department_id'     => $poleTech?->id,
                'sub_department_id' => $unitAS?->id,
                'service_id'        => $cellInvAS?->id,
            ]));
        }

        // ── PÔLE ADMINISTRATIF & FINANCIER ──────────────────────────
        $poleAdmin  = Department::where('name', 'Pôle Administratif & Financier')->first();

        $unitBud    = SubDepartment::where('name', 'Unité Suivi des Budgets')->first();
        $cellBud    = Service::where('name', 'Cellule Budgets et PLT')->first();

        $unitPerf   = SubDepartment::where('name', 'Unité Performances Financières')->first();
        $cellCpt    = Service::where('name', 'Cellule Comptabilité & Finances')->first();

        $unitRH     = SubDepartment::where('name', 'Unité Aspects Admin & RH')->first();
        $cellRH     = Service::where('name', 'Cellule Aspects Administratifs & RH')->first();
        $cellJur    = Service::where('name', 'Cellule Audits & Juridique')->first();

        foreach ([
            ['name' => 'Budgets annuels',         'description' => 'Documents budgétaires annuels prévisionnels',              'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Plans Long Terme (PLT)',   'description' => 'Plans de développement long terme',                        'expiry_value' => 20, 'expiry_unit' => 'years'],
            ['name' => 'Suivis trimestriels',      'description' => 'Tableaux de suivi et reporting trimestriel',               'expiry_value' => 5,  'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create(array_merge($cat, [
                'department_id'     => $poleAdmin?->id,
                'sub_department_id' => $unitBud?->id,
                'service_id'        => $cellBud?->id,
            ]));
        }

        foreach ([
            ['name' => 'Factures fournisseurs',   'description' => 'Factures et pièces comptables fournisseurs',               'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Bilans financiers',        'description' => 'Bilans et états financiers annuels',                       'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create(array_merge($cat, [
                'department_id'     => $poleAdmin?->id,
                'sub_department_id' => $unitPerf?->id,
                'service_id'        => $cellCpt?->id,
            ]));
        }

        foreach ([
            ['name' => 'Dossiers du personnel',   'description' => 'Dossiers individuels administratifs des agents',           'expiry_value' => 50, 'expiry_unit' => 'years'],
            ['name' => 'Congés et absences',       'description' => 'Demandes et justificatifs de congés et absences',          'expiry_value' => 5,  'expiry_unit' => 'years'],
            ['name' => 'Formations',               'description' => 'Attestations et programmes de formation du personnel',     'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create(array_merge($cat, [
                'department_id'     => $poleAdmin?->id,
                'sub_department_id' => $unitRH?->id,
                'service_id'        => $cellRH?->id,
            ]));
        }

        foreach ([
            ['name' => 'Appels d\'offres',         'description' => 'Dossiers d\'appels d\'offres publiés',                    'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Contrats et conventions',  'description' => 'Contrats signés avec les prestataires',                   'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create(array_merge($cat, [
                'department_id'     => $poleAdmin?->id,
                'sub_department_id' => $unitRH?->id,
                'service_id'        => $cellJur?->id,
            ]));
        }

        // ── DIRECTION SPCR ──────────────────────────────────────────
        $direction  = Department::where('name', 'Direction SPCR')->first();

        foreach ([
            ['name' => 'Courriers officiels',         'description' => 'Courriers entrants et sortants de la Direction',       'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Comptes rendus de réunion',   'description' => 'PV et comptes rendus des réunions de direction',       'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Décisions et notes internes', 'description' => 'Notes de service et décisions officielles',            'expiry_value' => 20, 'expiry_unit' => 'years'],
        ] as $cat) {
            Category::create([
                'name'              => $cat['name'],
                'description'       => $cat['description'],
                'department_id'     => $direction?->id,
                'sub_department_id' => null,
                'service_id'        => null,
                'expiry_value'      => $cat['expiry_value'],
                'expiry_unit'       => $cat['expiry_unit'],
            ]);
        }

        $this->command->info('✓ Catégories SPC Rabat créées avec succès (19 catégories).');
    }
}
