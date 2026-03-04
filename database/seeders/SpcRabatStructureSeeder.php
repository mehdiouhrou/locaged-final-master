<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Service;

class SpcRabatStructureSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // DEPARTMENT 1 : Direction SPCR
        // (Mme. Imane BEYE - Directrice / Mme. Bouchra OUBARI - Secrétariat)
        // Pas de sous-structures : la direction travaille au niveau global
        // ---------------------------------------------------------------
        Department::firstOrCreate(
            ['name' => 'Direction SPCR'],
            ['description' => 'Direction Générale du Service Permanent de Contrôle de Rabat — Mme. Imane BEYE']
        );


        // ---------------------------------------------------------------
        // DEPARTMENT 2 : Pôle Suivi des Aspects Techniques & Investissements
        // Chef de Pôle : M. Mustapha FETTACH
        // ---------------------------------------------------------------
        $poleTechnique = Department::firstOrCreate(
            ['name' => 'Pôle Technique'],
            ['description' => 'Pôle Suivi des aspects techniques et investissements — M. Mustapha FETTACH']
        );

        // Unité Eau Potable (Mme. Lamyaa AZZIOUI - Coordinatrice)
        $uniteEauPotable = SubDepartment::firstOrCreate(
            ['name' => 'Unité Eau Potable', 'department_id' => $poleTechnique->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Investissements Eau Potable',       'sub_department_id' => $uniteEauPotable->id]);
        Service::firstOrCreate(['name' => 'Cellule Performances Techniques Eau Potable','sub_department_id' => $uniteEauPotable->id]);

        // Unité Assainissement (Mme. Lamyaa AZZIOUI)
        $uniteAssainissement = SubDepartment::firstOrCreate(
            ['name' => 'Unité Assainissement', 'department_id' => $poleTechnique->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Investissements Assainissement',        'sub_department_id' => $uniteAssainissement->id]);
        Service::firstOrCreate(['name' => 'Cellule Performances Techniques Assainissement','sub_department_id' => $uniteAssainissement->id]);

        // Unité Electricité (M. Houssam Eddine BIQICHE - Coordinateur)
        $uniteElectricite = SubDepartment::firstOrCreate(
            ['name' => 'Unité Electricité', 'department_id' => $poleTechnique->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Investissements Electricité',        'sub_department_id' => $uniteElectricite->id]);
        Service::firstOrCreate(['name' => 'Cellule Performances Techniques Electricité','sub_department_id' => $uniteElectricite->id]);

        // Unité Patrimoine & SI (Mme. Kaoutar DADOUCH - Coordinatrice)
        $unitePatrimoineSI = SubDepartment::firstOrCreate(
            ['name' => 'Unité Patrimoine & SI', 'department_id' => $poleTechnique->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Développement & Maintenance SI','sub_department_id' => $unitePatrimoineSI->id]);
        Service::firstOrCreate(['name' => 'Cellule Patrimoine',                     'sub_department_id' => $unitePatrimoineSI->id]);


        // ---------------------------------------------------------------
        // DEPARTMENT 3 : Pôle Administratif & Suivi des Aspects Financiers
        // Coordinateur : M. Miloud ZIAT
        // ---------------------------------------------------------------
        $poleAdmin = Department::firstOrCreate(
            ['name' => 'Pôle Administratif & Financier'],
            ['description' => 'Pôle administratif et Suivi des aspects Financiers — M. Miloud ZIAT (Coordinateur)']
        );

        // Unité Suivi des Budgets (M. Mehdi IBRAHIMI)
        $uniteBudgets = SubDepartment::firstOrCreate(
            ['name' => 'Unité Suivi des Budgets', 'department_id' => $poleAdmin->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Budgets et PLT',                 'sub_department_id' => $uniteBudgets->id]);
        Service::firstOrCreate(['name' => 'Cellule Valorisation des Investissements','sub_department_id' => $uniteBudgets->id]);

        // Unité Performances Financières (M. Miloud ZIAT)
        $unitePerfsFinancieres = SubDepartment::firstOrCreate(
            ['name' => 'Unité Performances Financières', 'department_id' => $poleAdmin->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Comptabilité & Finances',                    'sub_department_id' => $unitePerfsFinancieres->id]);
        Service::firstOrCreate(['name' => 'Cellule Hypothèses & Equilibre Economique des Contrats','sub_department_id' => $unitePerfsFinancieres->id]);

        // Unité Aspects Clientèle (Mme. Loubna ZMIRI)
        $uniteClientele = SubDepartment::firstOrCreate(
            ['name' => 'Unité Aspects Clientèle', 'department_id' => $poleAdmin->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Qualité de Service',                         'sub_department_id' => $uniteClientele->id]);
        Service::firstOrCreate(['name' => 'Cellule Performance Commerciale & Tarification',     'sub_department_id' => $uniteClientele->id]);

        // Unité Aspects Administratifs & RH
        $uniteAdminRH = SubDepartment::firstOrCreate(
            ['name' => 'Unité Aspects Admin & RH', 'department_id' => $poleAdmin->id],
            []
        );
        Service::firstOrCreate(['name' => 'Cellule Aspects Administratifs & RH','sub_department_id' => $uniteAdminRH->id]);
        Service::firstOrCreate(['name' => 'Cellule Audits & Juridique',          'sub_department_id' => $uniteAdminRH->id]);
    }
}
