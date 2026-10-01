<?php
/**
 * Seeder emplacements physiques SPCR Rabat
 * ==========================================
 * Lit l'Excel des boîtes et crée tous les physical_locations en base.
 * À lancer AVANT l'import des documents.
 *
 * Source Excel : Inventaire_SPCR_pour injection copie.xlsx
 * Colonnes : A=boite, F=salle, G=rangée, H=étagère
 *
 * Usage : php seed_physical_locations.php
 * Options :
 *   --dry    Mode simulation (affiche sans insérer)
 *   --reset  Supprime les emplacements spcr-import existants avant d'insérer
 */

$DRY_RUN   = in_array('--dry', $argv ?? []);
$DO_RESET  = in_array('--reset', $argv ?? []);

$EXCEL_PATH = __DIR__ . '/Inventaire_SPCR_pour injection copie.xlsx';

// ============================================================
// BOOTSTRAP LARAVEL
// ============================================================
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

function log_msg(string $m): void { echo "[" . date('H:i:s') . "] {$m}\n"; flush(); }
function log_ok(string $m): void  { echo "[OK]  {$m}\n"; flush(); }
function log_err(string $m): void { echo "[ERR] {$m}\n"; flush(); }

log_msg("=== SEEDER EMPLACEMENTS PHYSIQUES SPCR ===");
log_msg("Excel : {$EXCEL_PATH}");
log_msg("DRY   : " . ($DRY_RUN ? 'OUI' : 'NON'));
log_msg("RESET : " . ($DO_RESET ? 'OUI' : 'NON'));

if (!file_exists($EXCEL_PATH)) {
    log_err("Excel introuvable : {$EXCEL_PATH}");
    exit(1);
}

// ============================================================
// LECTURE EXCEL — extraire boite (A), salle (F), rangée (G), étagère (H)
// ============================================================
log_msg("Lecture Excel...");
$spreadsheet = IOFactory::load($EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Fichiers PDF') ?? $spreadsheet->getActiveSheet();
log_msg("Feuille : " . $sheet->getTitle());

$locations = [];  // clé unique = "salle|rangée|étagère|boite"
$header = true;

foreach ($sheet->getRowIterator() as $row) {
    if ($header) { $header = false; continue; }
    $cells = [];
    foreach ($row->getCellIterator('A', 'H') as $cell) {
        $cells[] = trim((string)$cell->getValue());
    }

    $boite   = $cells[0] ?? '';
    $salle   = !empty($cells[5]) ? $cells[5] : 'Archive Temara';
    $rangee  = !empty($cells[6]) ? 'Rangée ' . $cells[6] : 'Rangée 1';
    $etagere = !empty($cells[7]) ? 'Étagère ' . $cells[7] : 'Étagère 1';

    if (empty($boite)) continue;

    $key = "{$salle}|{$rangee}|{$etagere}|{$boite}";
    if (!isset($locations[$key])) {
        $locations[$key] = [
            'room'  => $salle,
            'row'   => $rangee,
            'shelf' => $etagere,
            'box'   => $boite,
        ];
    }
}

$totalDistinct = count($locations);
log_ok("{$totalDistinct} emplacements distincts trouvés dans l'Excel");

// Aperçu des 5 premiers
foreach (array_slice($locations, 0, 5) as $key => $loc) {
    log_msg("  salle=[{$loc['room']}] rangée=[{$loc['row']}] étagère=[{$loc['shelf']}] boîte=[{$loc['box']}]");
}

// ============================================================
// RESET (optionnel — supprime uniquement si description = 'spcr-import')
// Sinon on fait juste getOrCreate
// ============================================================
if ($DO_RESET && !$DRY_RUN) {
    // Supprimer uniquement les physical_locations qui ne sont référencés
    // par aucun document (pour éviter de casser les docs déjà importés)
    $deleted = DB::table('physical_locations')
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('documents')
              ->whereColumn('documents.physical_location_id', 'physical_locations.id');
        })
        ->whereIn('room', array_unique(array_column($locations, 'room')))
        ->delete();
    log_msg("RESET : {$deleted} emplacements sans documents supprimés");
}

// ============================================================
// INSERTION EMPLACEMENTS (getOrCreate)
// ============================================================
if ($DRY_RUN) {
    log_msg("[DRY] Simulation terminée — {$totalDistinct} emplacements à créer/ignorer");
    exit(0);
}

$inserted  = 0;
$existing  = 0;
$errors    = 0;
$now       = now();

// Charger les emplacements existants en mémoire pour éviter N+1
$existingLocs = DB::table('physical_locations')
    ->get(['id', 'room', 'row', 'shelf', 'box'])
    ->keyBy(fn($l) => "{$l->room}|{$l->row}|{$l->shelf}|{$l->box}");

log_msg("Emplacements déjà en base : " . $existingLocs->count());
log_msg("\nInsertion...\n");

$toInsert = [];
foreach ($locations as $key => $loc) {
    if ($existingLocs->has($key)) {
        $existing++;
        continue;
    }
    $toInsert[] = [
        'room'       => $loc['room'],
        'row'        => $loc['row'],
        'shelf'      => $loc['shelf'],
        'box'        => $loc['box'],
        'created_at' => $now,
        'updated_at' => $now,
    ];
}

// Insertion en lots de 500
$chunks = array_chunk($toInsert, 500);
foreach ($chunks as $chunk) {
    try {
        DB::table('physical_locations')->insert($chunk);
        $inserted += count($chunk);
        log_ok("  {$inserted} emplacements insérés...");
    } catch (\Exception $e) {
        log_err("Erreur insertion lot : " . $e->getMessage());
        $errors++;
    }
}

// ============================================================
// RÉSUMÉ
// ============================================================
log_msg("\n========= RÉSUMÉ SEEDER =========");
log_ok("Emplacements distincts dans Excel : {$totalDistinct}");
log_ok("Déjà en base (ignorés)            : {$existing}");
log_ok("Insérés maintenant                : {$inserted}");
if ($errors > 0) log_err("Erreurs                           : {$errors}");
else             log_ok("Erreurs                           : 0");
log_msg("Durée                             : " . round(microtime(true) - LARAVEL_START, 2) . "s");

// Afficher le total final en base
$totalEnBase = DB::table('physical_locations')->count();
log_msg("Total physical_locations en base  : {$totalEnBase}");
