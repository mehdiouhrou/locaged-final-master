<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhysicalLocation;

class SpcRabatPhysicalLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            // ── Salle Archives Techniques ──────────────────────────
            // Rangée A → Eau Potable
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte EP-001', 'description' => 'Marchés travaux Eau Potable 2020-2022'],
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte EP-002', 'description' => 'Marchés travaux Eau Potable 2023-2024'],
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée A', 'shelf' => 'Étagère 2', 'box' => 'Boîte EP-003', 'description' => 'Rapports techniques EP 2022'],
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée A', 'shelf' => 'Étagère 2', 'box' => 'Boîte EP-004', 'description' => 'PV de réception EP 2023'],
            // Rangée B → Assainissement
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte AS-001', 'description' => 'Marchés travaux Assainissement 2021-2022'],
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte AS-002', 'description' => 'Marchés travaux Assainissement 2023-2024'],
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée B', 'shelf' => 'Étagère 2', 'box' => 'Boîte AS-003', 'description' => 'Plans d\'exécution Assainissement'],
            ['room' => 'Salle Archives Techniques', 'row' => 'Rangée B', 'shelf' => 'Étagère 2', 'box' => 'Boîte AS-004', 'description' => 'Rapports techniques AS 2022-2023'],

            // ── Salle Archives Financières ─────────────────────────
            // Rangée A → Budget & Comptabilité
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte BUD-001', 'description' => 'Budgets annuels 2020-2022'],
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte BUD-002', 'description' => 'Budgets annuels 2023-2024'],
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée A', 'shelf' => 'Étagère 2', 'box' => 'Boîte CPT-001', 'description' => 'Factures fournisseurs 2022'],
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée A', 'shelf' => 'Étagère 2', 'box' => 'Boîte CPT-002', 'description' => 'Bilans financiers 2021-2023'],
            // Rangée B → Marchés Publics
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte MP-001', 'description' => 'Appels d\'offres 2022'],
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte MP-002', 'description' => 'Appels d\'offres 2023-2024'],
            ['room' => 'Salle Archives Financières', 'row' => 'Rangée B', 'shelf' => 'Étagère 2', 'box' => 'Boîte MP-003', 'description' => 'Contrats prestataires 2023'],

            // ── Salle Archives RH ──────────────────────────────────
            ['room' => 'Salle Archives RH',         'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte RH-001', 'description' => 'Dossiers personnel A-F'],
            ['room' => 'Salle Archives RH',         'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte RH-002', 'description' => 'Dossiers personnel G-M'],
            ['room' => 'Salle Archives RH',         'row' => 'Rangée A', 'shelf' => 'Étagère 2', 'box' => 'Boîte RH-003', 'description' => 'Dossiers personnel N-Z'],
            ['room' => 'Salle Archives RH',         'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte RH-004', 'description' => 'Congés et absences 2023-2024'],
            ['room' => 'Salle Archives RH',         'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte RH-005', 'description' => 'Attestations de formation 2022-2024'],

            // ── Salle Archives Direction ───────────────────────────
            ['room' => 'Salle Archives Direction',  'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte DIR-001', 'description' => 'Courriers officiels 2023'],
            ['room' => 'Salle Archives Direction',  'row' => 'Rangée A', 'shelf' => 'Étagère 1', 'box' => 'Boîte DIR-002', 'description' => 'Courriers officiels 2024'],
            ['room' => 'Salle Archives Direction',  'row' => 'Rangée A', 'shelf' => 'Étagère 2', 'box' => 'Boîte DIR-003', 'description' => 'Comptes rendus réunions 2022-2024'],
            ['room' => 'Salle Archives Direction',  'row' => 'Rangée B', 'shelf' => 'Étagère 1', 'box' => 'Boîte DIR-004', 'description' => 'Décisions et notes internes 2020-2024'],
        ];

        foreach ($locations as $loc) {
            PhysicalLocation::create($loc);
        }

        $this->command->info('✓ ' . count($locations) . ' emplacements physiques SPC Rabat créés avec succès.');
    }
}
