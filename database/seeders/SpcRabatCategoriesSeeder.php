<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpcRabatCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        echo "=== SpcRabatCategoriesSeeder (v2) ===" . PHP_EOL;

        $restrictedRoleNames = [
            'master',
            'Directrice du SPCR',
            'IT Admin',
            'Assistante de Direction',
            'Chargée de dépôt',
        ];

        $restrictedRoleIds = DB::table('roles')
            ->whereIn('name', $restrictedRoleNames)
            ->pluck('id', 'name');

        echo "Rôles restreints trouvés : " . $restrictedRoleIds->count() . "/" . count($restrictedRoleNames) . PHP_EOL;
        foreach ($restrictedRoleNames as $rn) {
            if (!$restrictedRoleIds->has($rn)) {
                echo "  WARNING Role introuvable : {$rn}" . PHP_EOL;
            }
        }

        $allServiceIds = DB::table('services')->pluck('id')->toArray();
        echo "Services en base : " . count($allServiceIds) . PHP_EOL;

        $poleTechniqueServiceIds = DB::table('services as s')
            ->join('sub_departments as sd', 'sd.id', '=', 's.sub_department_id')
            ->join('departments as d', 'd.id', '=', 'sd.department_id')
            ->where('d.name', 'Pôle Technique')
            ->pluck('s.id')
            ->toArray();

        echo "Services Pôle Technique trouvés : " . count($poleTechniqueServiceIds) . PHP_EOL;
        if (empty($poleTechniqueServiceIds)) {
            echo "  WARNING Aucun service Pôle Technique - verifier le nom du departement en base" . PHP_EOL;
        }

        $categories = [
            ['name' => 'ALSA CITY BUS',                                   'group' => 'transport'],
            ['name' => 'SPC transport',                                   'group' => 'transport'],
            ['name' => 'Société Tramway Rabat Salé',                      'group' => 'transport'],
            ['name' => 'Rabat Région Mobilité',                           'group' => 'transport'],
            ['name' => 'Transport urbain – Kénitra',                      'group' => 'transport'],
            ['name' => 'STAREO',                                          'group' => 'transport'],
            ['name' => 'Décharge OUM AZZA',                              'group' => 'dechets'],
            ['name' => 'Gestion Déléguée des Déchets Ménagers de Rabat', 'group' => 'dechets'],
            ['name' => 'REDAL',                                           'group' => 'all'],
            ['name' => 'ECI- ALASSIMA',                                   'group' => 'all'],
            ['name' => '3RP',                                             'group' => 'all'],
            ['name' => 'RRA',                                             'group' => 'all'],
            ['name' => 'TEODEM',                                          'group' => 'all'],
            ['name' => 'SOS NOD',                                         'group' => 'all'],
            ['name' => 'BFIVE Consulting',                                'group' => 'all'],
            ['name' => 'Autorité Délégante',                             'group' => 'all'],
            ['name' => 'Dahirs / Textes juridiques',                      'group' => 'all'],
            ['name' => 'Wilaya Rabat Salé Kenitra',                      'group' => 'all'],
            ['name' => 'Cour des comptes',                                'group' => 'all'],
            ['name' => "Ministère de l'Équipement et du Transport",      'group' => 'all'],
            ['name' => 'SOCIETE MADERASATI',                              'group' => 'all'],
            ['name' => 'SPCR',                                            'group' => 'all'],
        ];

        $now = now();
        $created = 0;
        $existing = 0;
        $totalCategoryService = 0;
        $totalCategoryRole = 0;

        foreach ($categories as $cat) {
            $row = DB::table('categories')->where('name', $cat['name'])->first();

            if (!$row) {
                $catId = DB::table('categories')->insertGetId([
                    'name'         => $cat['name'],
                    'description'  => null,
                    'expiry_value' => 10,
                    'expiry_unit'  => 'years',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
                $created++;
                echo "  OK Creee : {$cat['name']}" . PHP_EOL;
            } else {
                $catId = $row->id;
                $existing++;
                echo "  -- Existe deja : {$cat['name']} (id={$catId})" . PHP_EOL;
            }

            if ($cat['group'] === 'all') {
                $serviceIds = $allServiceIds;
            } elseif ($cat['group'] === 'dechets') {
                $serviceIds = $poleTechniqueServiceIds;
            } else {
                $serviceIds = [];
            }

            foreach ($serviceIds as $sid) {
                $exists = DB::table('category_service')
                    ->where('category_id', $catId)
                    ->where('service_id', $sid)
                    ->exists();
                if (!$exists) {
                    DB::table('category_service')->insert([
                        'category_id' => $catId,
                        'service_id'  => $sid,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                    $totalCategoryService++;
                }
            }

            if (in_array($cat['group'], ['transport', 'dechets'])) {
                foreach ($restrictedRoleIds as $roleName => $roleId) {
                    $exists = DB::table('category_role')
                        ->where('category_id', $catId)
                        ->where('role_id', $roleId)
                        ->exists();
                    if (!$exists) {
                        DB::table('category_role')->insert([
                            'category_id' => $catId,
                            'role_id'     => $roleId,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                        $totalCategoryRole++;
                    }
                }
            }
        }

        echo PHP_EOL . "=== Résumé ===" . PHP_EOL;
        echo "Categories creees    : {$created}" . PHP_EOL;
        echo "Categories existantes: {$existing}" . PHP_EOL;
        echo "category_service     : {$totalCategoryService} inseres" . PHP_EOL;
        echo "category_role        : {$totalCategoryRole} inseres" . PHP_EOL;
        echo "=== Termine ===" . PHP_EOL;
    }
}
