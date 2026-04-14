<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Même jeu de catégories que la branche spcr, adapté au schéma demo-v2 :
 * pas de department_id / service_id sur categories — rattachement via pivot category_service.
 */
class SpcRabatCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('category_service')) {
            $this->command->warn('Table category_service absente : exécutez les migrations, puis relancez ce seeder.');

            return;
        }

        // --- Pole Technique ---
        $cellInvEP = Service::where('name', 'Cellule Investissements Eau Potable')->first();
        $cellInvAS = Service::where('name', 'Cellule Investissements Assainissement')->first();

        foreach ([
            ['name' => 'Marchés de travaux EP', 'description' => 'Contrats et marchés liés aux travaux d\'eau potable', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Rapports techniques EP', 'description' => 'Rapports d\'avancement et de réception eau potable', 'expiry_value' => 5, 'expiry_unit' => 'years'],
            ['name' => 'PV de réception EP', 'description' => 'Procès-verbaux de réception des travaux eau potable', 'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, $cellInvEP?->id);
        }

        foreach ([
            ['name' => 'Marchés de travaux AS', 'description' => 'Contrats et marchés liés aux travaux d\'assainissement', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Rapports techniques AS', 'description' => 'Rapports d\'avancement et de réception assainissement', 'expiry_value' => 5, 'expiry_unit' => 'years'],
            ['name' => 'Plans d\'exécution AS', 'description' => 'Plans et schémas d\'exécution des réseaux assainissement', 'expiry_value' => 15, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, $cellInvAS?->id);
        }

        // --- Pole Administratif & Financier ---
        $cellBud = Service::where('name', 'Cellule Budgets et PLT')->first();
        $cellCpt = Service::where('name', 'Cellule Comptabilité & Finances')->first();
        $cellRH = Service::where('name', 'Cellule Aspects Administratifs & RH')->first();
        $cellJur = Service::where('name', 'Cellule Audits & Juridique')->first();

        foreach ([
            ['name' => 'Budgets annuels', 'description' => 'Documents budgétaires annuels prévisionnels', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Plans Long Terme (PLT)', 'description' => 'Plans de développement long terme', 'expiry_value' => 20, 'expiry_unit' => 'years'],
            ['name' => 'Suivis trimestriels', 'description' => 'Tableaux de suivi et reporting trimestriel', 'expiry_value' => 5, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, $cellBud?->id);
        }

        foreach ([
            ['name' => 'Factures fournisseurs', 'description' => 'Factures et pièces comptables fournisseurs', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Bilans financiers', 'description' => 'Bilans et états financiers annuels', 'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, $cellCpt?->id);
        }

        foreach ([
            ['name' => 'Dossiers du personnel', 'description' => 'Dossiers individuels administratifs des agents', 'expiry_value' => 50, 'expiry_unit' => 'years'],
            ['name' => 'Congés et absences', 'description' => 'Demandes et justificatifs de congés et absences', 'expiry_value' => 5, 'expiry_unit' => 'years'],
            ['name' => 'Formations', 'description' => 'Attestations et programmes de formation du personnel', 'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, $cellRH?->id);
        }

        foreach ([
            ['name' => 'Appels d\'offres', 'description' => 'Dossiers d\'appels d\'offres publiés', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Contrats et conventions', 'description' => 'Contrats signés avec les prestataires', 'expiry_value' => 10, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, $cellJur?->id);
        }

        // --- Direction SPCR (categories sans rattachement service) ---

        foreach ([
            ['name' => 'Courriers officiels', 'description' => 'Courriers entrants et sortants de la Direction', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Comptes rendus de réunion', 'description' => 'PV et comptes rendus des réunions de direction', 'expiry_value' => 10, 'expiry_unit' => 'years'],
            ['name' => 'Décisions et notes internes', 'description' => 'Notes de service et décisions officielles', 'expiry_value' => 20, 'expiry_unit' => 'years'],
        ] as $cat) {
            $this->upsertCategoryWithService($cat, null);
        }

        $this->command->info('Catégories SPC Rabat créées ou mises à jour (19 catégories), pivot category_service.');
    }

    /**
     * @param  array{name: string, description: string, expiry_value: int, expiry_unit: string}  $cat
     */
    private function upsertCategoryWithService(array $cat, ?int $serviceId): void
    {
        $category = Category::withoutGlobalScopes()->updateOrCreate(
            ['name' => $cat['name']],
            [
                'description' => $cat['description'],
                'expiry_value' => $cat['expiry_value'],
                'expiry_unit' => $cat['expiry_unit'],
            ]
        );

        if ($serviceId !== null) {
            $category->services()->sync([$serviceId]);
        } else {
            $category->services()->sync([]);
        }
    }
}
