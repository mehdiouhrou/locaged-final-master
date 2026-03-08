<?php
/**
 * SPCR — Fix catégories : department_id + sub_department_id + expiry_value/unit
 * À lancer depuis la racine : php scripts/spcr_fix_categories.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────────────────────
// HELPER : parser la durée de conservation → expiry_value + expiry_unit
// ─────────────────────────────────────────────────────────────
function parseExpiry($retention) {
    if (!$retention) return [null, null];
    $r = strtolower(trim($retention));

    if (str_contains($r, 'permanent'))  return [null, 'permanent'];
    if (str_contains($r, 'définitiv'))  return [null, 'définitive'];

    // "10 ans après départ du salarié" → 10 ans
    if (preg_match('/(\d+)\s*ans/', $r, $m)) return [(int)$m[1], 'years'];

    // "5 à 10 ans" → on prend le max
    if (preg_match('/(\d+)\s*[àa]\s*(\d+)\s*ans/', $r, $m)) return [(int)$m[2], 'years'];

    // "5 ans"
    if (preg_match('/(\d+)/', $r, $m)) return [(int)$m[1], 'years'];

    return [null, null];
}

// ─────────────────────────────────────────────────────────────
// RÉCUPÉRER LA HIÉRARCHIE depuis la base
// ─────────────────────────────────────────────────────────────
// service_id → [sub_department_id, department_id]
$services = DB::table('services as s')
    ->join('sub_departments as sd', 's.sub_department_id', '=', 'sd.id')
    ->select('s.id as service_id', 'sd.id as sub_department_id', 'sd.department_id')
    ->get();

$map = [];
foreach ($services as $s) {
    $map[$s->service_id] = [
        'sub_department_id' => $s->sub_department_id,
        'department_id'     => $s->department_id,
    ];
}

echo "=== FIX CATÉGORIES ===\n";
echo "Mapping services chargé : " . count($map) . " services\n\n";

// ─────────────────────────────────────────────────────────────
// METTRE À JOUR TOUTES LES CATÉGORIES
// ─────────────────────────────────────────────────────────────
$categories = DB::table('categories')->get();
$fixed = 0;
$skipped = 0;

foreach ($categories as $cat) {
    $service_id = $cat->service_id;

    if (!$service_id || !isset($map[$service_id])) {
        echo "  [skip] ID:{$cat->id} - " . substr($cat->name, 0, 50) . " (pas de service_id)\n";
        $skipped++;
        continue;
    }

    [$expiry_value, $expiry_unit] = parseExpiry($cat->retention_period);

    DB::table('categories')->where('id', $cat->id)->update([
        'department_id'     => $map[$service_id]['department_id'],
        'sub_department_id' => $map[$service_id]['sub_department_id'],
        'expiry_value'      => $expiry_value,
        'expiry_unit'       => $expiry_unit,
        'updated_at'        => now(),
    ]);

    echo "  [ok] ID:{$cat->id} dept:{$map[$service_id]['department_id']} subdept:{$map[$service_id]['sub_department_id']} expiry:{$expiry_value} {$expiry_unit} | " . substr($cat->name, 0, 50) . "\n";
    $fixed++;
}

echo "\n=== RÉSULTAT ===\n";
echo "  Catégories mises à jour : $fixed\n";
echo "  Ignorées (sans service) : $skipped\n";

// ─────────────────────────────────────────────────────────────
// VÉRIFICATION RAPIDE
// ─────────────────────────────────────────────────────────────
echo "\n=== VÉRIFICATION ===\n";
$nullDept = DB::table('categories')->whereNull('department_id')->count();
$nullSub  = DB::table('categories')->whereNull('sub_department_id')->count();
$nullExp  = DB::table('categories')->whereNull('expiry_unit')->count();

echo "  Catégories sans department_id    : $nullDept\n";
echo "  Catégories sans sub_department_id : $nullSub\n";
echo "  Catégories sans expiry_unit       : $nullExp\n";

if ($nullDept == 0 && $nullSub == 0) {
    echo "\n✅ Toutes les catégories ont leur hiérarchie complète !\n";
} else {
    echo "\n⚠️  Certaines catégories n'ont pas de hiérarchie — vérifier les service_id NULL.\n";
}
