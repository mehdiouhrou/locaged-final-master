<?php
/**
 * Seeder hiérarchique emplacements SPCR — v2
 * ===========================================
 * Crée la hiérarchie Room → Row → Shelf → Box depuis l'Excel.
 * L'Excel a : boite(A), salle(F), rangée(G), étagère(H)
 *
 * Structure DB :
 *   rooms  : id, name, department_id
 *   rows   : id, room_id, name
 *   shelves: id, row_id, name
 *   boxes  : id, shelf_id, service_id, name, box_number
 *
 * Usage :
 *   php seed_locations_v2.php          — insert
 *   php seed_locations_v2.php --dry    — simulation
 */

$DRY_RUN = in_array('--dry', $argv ?? []);
$EXCEL_PATH = __DIR__ . '/Inventaire_SPCR_pour injection copie.xlsx';

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

function log_msg(string $m): void { echo "[" . date('H:i:s') . "] {$m}\n"; flush(); }
function log_ok(string $m): void  { echo "[OK]  {$m}\n"; flush(); }
function log_err(string $m): void { echo "[ERR] {$m}\n"; flush(); }

log_msg("=== SEEDER EMPLACEMENTS HIÉRARCHIQUES SPCR v2 ===");
log_msg("DRY : " . ($DRY_RUN ? 'OUI' : 'NON'));

// ============================================================
// LECTURE EXCEL — extraire combinaisons uniques salle/rangée/étagère/boite
// ============================================================
log_msg("Lecture Excel...");
$spreadsheet = IOFactory::load($EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Fichiers PDF') ?? $spreadsheet->getActiveSheet();

$locations = [];
$header = true;
foreach ($sheet->getRowIterator() as $row) {
    if ($header) { $header = false; continue; }
    $cells = [];
    foreach ($row->getCellIterator('A', 'H') as $cell) {
        $cells[] = trim((string)$cell->getValue());
    }
    $boite   = $cells[0] ?? '';
    $salle   = !empty($cells[5]) ? $cells[5] : 'Archive Temara';
    $rangee  = !empty($cells[6]) ? $cells[6] : '1';
    $etagere = !empty($cells[7]) ? $cells[7] : '1';

    if (empty($boite)) continue;

    $key = "{$salle}|{$rangee}|{$etagere}|{$boite}";
    $locations[$key] = [
        'salle'   => $salle,
        'rangee'  => $rangee,
        'etagere' => $etagere,
        'boite'   => $boite,
    ];
}

log_ok(count($locations) . " emplacements distincts dans l'Excel");

// Aperçu
foreach (array_slice($locations, 0, 3) as $k => $l) {
    log_msg("  salle=[{$l['salle']}] rangée=[{$l['rangee']}] étagère=[{$l['etagere']}] boîte=[{$l['boite']}]");
}

if ($DRY_RUN) {
    log_msg("[DRY] Simulation terminée.");
    exit(0);
}

$now = now();

// ============================================================
// ROOMS — getOrCreate par nom
// ============================================================
$roomCache = [];
function getOrCreateRoom(string $name): int {
    global $roomCache, $now;
    $key = strtolower(trim($name));
    if (isset($roomCache[$key])) return $roomCache[$key];

    $existing = DB::table('rooms')->where('name', $name)->first();
    if ($existing) {
        $roomCache[$key] = $existing->id;
        return $existing->id;
    }
    $id = DB::table('rooms')->insertGetId(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    $roomCache[$key] = $id;
    log_ok("Room créée : [{$id}] {$name}");
    return $id;
}

// ============================================================
// ROWS — getOrCreate par room_id + nom
// ============================================================
$rowCache = [];
function getOrCreateRow(int $roomId, string $name): int {
    global $rowCache, $now;
    $key = "{$roomId}|{$name}";
    if (isset($rowCache[$key])) return $rowCache[$key];

    $existing = DB::table('rows')->where('room_id', $roomId)->where('name', $name)->first();
    if ($existing) {
        $rowCache[$key] = $existing->id;
        return $existing->id;
    }
    $id = DB::table('rows')->insertGetId(['room_id' => $roomId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    $rowCache[$key] = $id;
    return $id;
}

// ============================================================
// SHELVES — getOrCreate par row_id + nom
// ============================================================
$shelfCache = [];
function getOrCreateShelf(int $rowId, string $name): int {
    global $shelfCache, $now;
    $key = "{$rowId}|{$name}";
    if (isset($shelfCache[$key])) return $shelfCache[$key];

    $existing = DB::table('shelves')->where('row_id', $rowId)->where('name', $name)->first();
    if ($existing) {
        $shelfCache[$key] = $existing->id;
        return $existing->id;
    }
    $id = DB::table('shelves')->insertGetId(['row_id' => $rowId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    $shelfCache[$key] = $id;
    return $id;
}

// ============================================================
// BOXES — getOrCreate par shelf_id + name (= numéro boîte)
// ============================================================
$boxCache = [];
function getOrCreateBox(int $shelfId, string $boite): int {
    global $boxCache, $now;
    $key = "{$shelfId}|{$boite}";
    if (isset($boxCache[$key])) return $boxCache[$key];

    $existing = DB::table('boxes')->where('shelf_id', $shelfId)->where('name', $boite)->first();
    if ($existing) {
        $boxCache[$key] = $existing->id;
        return $existing->id;
    }
    $id = DB::table('boxes')->insertGetId([
        'shelf_id'   => $shelfId,
        'name'       => $boite,
        'box_number' => $boite,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $boxCache[$key] = $id;
    return $id;
}

// ============================================================
// INSERTION
// ============================================================
log_msg("\nInsertion hiérarchie...\n");

$inserted = ['rooms' => 0, 'rows' => 0, 'shelves' => 0, 'boxes' => 0];
$progress = 0;

foreach ($locations as $key => $loc) {
    $roomId  = getOrCreateRoom($loc['salle']);
    $rowId   = getOrCreateRow($roomId, 'R' . $loc['rangee']);
    $shelfId = getOrCreateShelf($rowId, 'E' . $loc['etagere']);
    $boxId   = getOrCreateBox($shelfId, $loc['boite']);

    $progress++;
    if ($progress % 200 === 0) log_ok("{$progress} emplacements traités...");
}

// ============================================================
// RÉSUMÉ
// ============================================================
log_msg("\n========= RÉSUMÉ SEEDER v2 =========");
log_ok("Total rooms   en base : " . DB::table('rooms')->count());
log_ok("Total rows    en base : " . DB::table('rows')->count());
log_ok("Total shelves en base : " . DB::table('shelves')->count());
log_ok("Total boxes   en base : " . DB::table('boxes')->count());
log_msg("Durée : " . round(microtime(true) - LARAVEL_START, 2) . "s");
