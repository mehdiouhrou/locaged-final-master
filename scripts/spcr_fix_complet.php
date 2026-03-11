<?php
/**
 * spcr_fix_complet.php
 * 1. Vérifie/complète la structure Direction Générale
 * 2. Intègre les 2 nouvelles unités (Transport Urbain + Décharge)
 * 3. Crée les 7 catégories DG manquantes
 * 4. Corrige toutes les permanentes/définitives → 99 ans
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fix Complet SPCR ===\n\n";

// ════════════════════════════════════════════════
// PARTIE 1 — STRUCTURE DIRECTION GÉNÉRALE
// ════════════════════════════════════════════════
echo "--- Partie 1 : Structure Direction Générale ---\n";

// Département DG (id=2 déjà en base)
$dept_dg = DB::table('departments')->where('id', 2)->first();
if ($dept_dg) {
    echo "  [OK] Département DG : [{$dept_dg->id}] {$dept_dg->name}\n";
} else {
    $dept_dg_id = DB::table('departments')->insertGetId([
        'name'       => 'Direction Générale',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  [CRÉÉ] Département DG ID {$dept_dg_id}\n";
    $dept_dg = DB::table('departments')->find($dept_dg_id);
}
$dept_dg_id = $dept_dg->id; // = 2

// Unité Secrétariat (id=9 déjà en base)
$unit_secr = DB::table('sub_departments')->where('id', 9)->first();
if ($unit_secr) {
    echo "  [OK] Unité : [{$unit_secr->id}] {$unit_secr->name}\n";
} else {
    $unit_secr_id = DB::table('sub_departments')->insertGetId([
        'name'          => 'Secrétariat et Bureau d\'ordre',
        'department_id' => $dept_dg_id,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
    echo "  [CRÉÉ] Unité Secrétariat ID {$unit_secr_id}\n";
    $unit_secr = DB::table('sub_departments')->find($unit_secr_id);
}
$unit_secr_id = $unit_secr->id; // = 9

// Cellule Secrétariat (id=18 déjà en base)
$service_secr = DB::table('services')->where('id', 18)->first();
if ($service_secr) {
    echo "  [OK] Cellule : [{$service_secr->id}] {$service_secr->name}\n";
} else {
    $service_secr_id = DB::table('services')->insertGetId([
        'name'              => 'Secrétariat et Bureau d\'ordre du SPCR',
        'sub_department_id' => $unit_secr_id,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
    echo "  [CRÉÉ] Cellule Secrétariat ID {$service_secr_id}\n";
    $service_secr = DB::table('services')->find($service_secr_id);
}
$service_secr_id = $service_secr->id; // = 18

// ════════════════════════════════════════════════
// PARTIE 2 — 2 NOUVELLES UNITÉS + CELLULES
// ════════════════════════════════════════════════
echo "\n--- Partie 2 : Nouvelles unités ---\n";

$nouvelles_unites = [
    [
        'sub_dept_id' => 19,
        'sub_dept_name' => 'Service Permanent de Contrôle Rabat - Transport Urbain',
        'service_name'  => 'Cellule Transport Urbain',
    ],
    [
        'sub_dept_id' => 20,
        'sub_dept_name' => 'Service Permanent de Contrôle Rabat - Décharge',
        'service_name'  => 'Cellule Décharge',
    ],
];

$new_services = [];
foreach ($nouvelles_unites as $u) {
    // Vérifier unité
    $unit = DB::table('sub_departments')->where('id', $u['sub_dept_id'])->first();
    if ($unit) {
        echo "  [OK] Unité : [{$unit->id}] {$unit->name}\n";
    } else {
        DB::table('sub_departments')->insert([
            'id'            => $u['sub_dept_id'],
            'name'          => $u['sub_dept_name'],
            'department_id' => $dept_dg_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        echo "  [CRÉÉ] Unité ID {$u['sub_dept_id']} : {$u['sub_dept_name']}\n";
    }

    // Vérifier/créer cellule correspondante
    $service = DB::table('services')
        ->where('sub_department_id', $u['sub_dept_id'])
        ->first();

    if ($service) {
        echo "  [OK] Cellule : [{$service->id}] {$service->name}\n";
        $new_services[$u['sub_dept_id']] = $service->id;
    } else {
        $svc_id = DB::table('services')->insertGetId([
            'name'              => $u['service_name'],
            'sub_department_id' => $u['sub_dept_id'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        echo "  [CRÉÉ] Cellule ID {$svc_id} : {$u['service_name']}\n";
        $new_services[$u['sub_dept_id']] = $svc_id;
    }
}

// ════════════════════════════════════════════════
// PARTIE 3 — 7 CATÉGORIES DIRECTION GÉNÉRALE
// ════════════════════════════════════════════════
echo "\n--- Partie 3 : Catégories Direction Générale ---\n";

$dg_categories = [
    [
        'name'             => 'DG-Dossier N°1 : Instances de gouvernance',
        'retention_period' => 'Conservation permanente',
        'expiry_value'     => 99,
        'expiry_unit'      => 'years',
    ],
    [
        'name'             => 'DG-Dossier N°2 : Documentation Stratégique',
        'retention_period' => 'Conservation permanente',
        'expiry_value'     => 99,
        'expiry_unit'      => 'years',
    ],
    [
        'name'             => 'DG-Dossier N°3 : Textes réglementaires',
        'retention_period' => 'Conservation permanente',
        'expiry_value'     => 99,
        'expiry_unit'      => 'years',
    ],
    [
        'name'             => 'DG-Dossier N°4 : Pilotage des activités du SPCR',
        'retention_period' => 'Conservation permanente',
        'expiry_value'     => 99,
        'expiry_unit'      => 'years',
    ],
    [
        'name'             => 'DG-Dossier N°5 : Notes et décisions',
        'retention_period' => 'Conservation permanente',
        'expiry_value'     => 99,
        'expiry_unit'      => 'years',
    ],
    [
        'name'             => 'DG-Dossier N°6 : Courriers arrivée',
        'retention_period' => '5 ans',
        'expiry_value'     => 5,
        'expiry_unit'      => 'years',
    ],
    [
        'name'             => 'DG-Dossier N°7 : Courriers départ',
        'retention_period' => '5 ans',
        'expiry_value'     => 5,
        'expiry_unit'      => 'years',
    ],
];

$created = 0;
foreach ($dg_categories as $cat) {
    $exists = DB::table('categories')->where('name', $cat['name'])->first();
    if ($exists) {
        echo "  [SKIP] Existe déjà : {$cat['name']}\n";
        continue;
    }

    $cat_id = DB::table('categories')->insertGetId([
        'name'              => $cat['name'],
        'retention_period'  => $cat['retention_period'],
        'expiry_value'      => $cat['expiry_value'],
        'expiry_unit'       => $cat['expiry_unit'],
        'sub_department_id' => $unit_secr_id,
        'service_id'        => $service_secr_id,
        'description'       => null,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    // Partage propriétaire
    DB::table('category_service')->insertOrIgnore([
        'category_id' => $cat_id,
        'service_id'  => $service_secr_id,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    echo "  [CRÉÉ] ID {$cat_id} : {$cat['name']}\n";
    $created++;
}

echo "\n✅ {$created} catégorie(s) DG créées\n";

// ════════════════════════════════════════════════
// PARTIE 4 — FIX PERMANENTES → 99 ANS
// ════════════════════════════════════════════════
echo "\n--- Partie 4 : Conservation permanente → 99 ans ---\n";

$to_fix = DB::table('categories')
    ->where(function($q) {
        $q->whereNull('expiry_value')
          ->orWhereIn('expiry_unit', ['permanent', 'définitive', 'illimité', 'definitive', 'permanente']);
    })->get(['id', 'name']);

echo count($to_fix) . " catégorie(s) à corriger :\n";
foreach ($to_fix as $c) {
    echo "  [{$c->id}] {$c->name}\n";
}

$updated = DB::table('categories')
    ->where(function($q) {
        $q->whereNull('expiry_value')
          ->orWhereIn('expiry_unit', ['permanent', 'définitive', 'illimité', 'definitive', 'permanente']);
    })->update([
        'expiry_value' => 99,
        'expiry_unit'  => 'years',
        'updated_at'   => now(),
    ]);

echo "\n✅ {$updated} catégorie(s) → 99 ans\n";

// ════════════════════════════════════════════════
// VÉRIFICATION FINALE
// ════════════════════════════════════════════════
echo "\n=== Vérification finale ===\n";
echo "Départements : " . DB::table('departments')->count() . "\n";
echo "Unités       : " . DB::table('sub_departments')->count() . "\n";
echo "Cellules     : " . DB::table('services')->count() . "\n";
echo "Catégories   : " . DB::table('categories')->count() . " (attendu : 76)\n";
echo "Partages     : " . DB::table('category_service')->count() . "\n";

$bad = DB::table('categories')
    ->where(function($q) {
        $q->whereNull('expiry_value')
          ->orWhereIn('expiry_unit', ['permanent', 'définitive', 'illimité', 'definitive', 'permanente']);
    })->count();

echo $bad === 0
    ? "\n✅ Toutes les durées de conservation sont correctes\n"
    : "\n⚠️  {$bad} catégorie(s) encore non corrigées\n";

echo "\n=== Terminé ===\n";
