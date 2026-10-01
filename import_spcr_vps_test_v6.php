<?php
/**
 * Script import SPCR Rabat — v6 (TEST VPS Hetzner)
 * ===================================================
 * Corrections v6 :
 *  - box_id (hiérarchie Room→Row→Shelf→Box) au lieu de physical_location_id
 *  - Étiquettes dans tags + document_tags
 *  - SHA-256 calculé à l'import
 *  - Date lue via getFormattedValue() pour éviter les serials Excel
 *
 * Colonnes Excel (feuille "Fichiers PDF") :
 *   A (0) = boîte      ex: 1.1.1.1
 *   B (1) = étiquette  ex: ALSA CITY BUS
 *   C (2) = nom fichier PDF
 *   D (3) = catégorie  (une des 22 catégories réelles en base)
 *   E (4) = date
 *   F (5) = salle
 *   G (6) = rangée
 *   H (7) = étagère
 */

$DOCS_ROOT    = __DIR__ . '/test_docs';
$EXCEL_PATH   = __DIR__ . '/Inventaire_SPCR_pour injection copie.xlsx';
$STORAGE_PATH = __DIR__ . '/storage/app/private/spcr-import';
$CREATED_BY   = 1;
$DRY_RUN      = false;

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

function log_msg(string $m): void { echo "[" . date('H:i:s') . "] {$m}\n"; flush(); }
function log_ok(string $m): void  { echo "[OK]  {$m}\n"; flush(); }
function log_err(string $m): void { echo "[ERR] {$m}\n"; flush(); }

log_msg("=== IMPORT SPCR v6 — TEST VPS ===");
log_msg("Excel : {$EXCEL_PATH}");
log_msg("Docs  : {$DOCS_ROOT}");
log_msg("DRY   : " . ($DRY_RUN ? 'OUI' : 'NON'));

if (!file_exists($EXCEL_PATH)) { log_err("Excel introuvable"); exit(1); }
if (!is_dir($DOCS_ROOT))       { log_err("Dossier docs introuvable"); exit(1); }

// Vérifier tables
$hasDocVersions = !empty(DB::select("SHOW TABLES LIKE 'document_versions'"));
$hasTags        = !empty(DB::select("SHOW TABLES LIKE 'tags'"));
log_msg("Tables : document_versions=" . ($hasDocVersions ? 'OUI' : 'NON') . " / tags=" . ($hasTags ? 'OUI' : 'NON'));
if (!$hasDocVersions) { log_err("Table document_versions introuvable"); exit(1); }

// Pôles disponibles
$polesDisponibles = [];
foreach (glob($DOCS_ROOT . '/*', GLOB_ONLYDIR) as $d) {
    $polesDisponibles[] = basename($d);
}
log_msg("Pôles : " . implode(', ', $polesDisponibles));

// ============================================================
// LECTURE EXCEL — getFormattedValue() pour la date
// ============================================================
log_msg("Lecture Excel...");
$spreadsheet = IOFactory::load($EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Fichiers PDF') ?? $spreadsheet->getActiveSheet();
log_msg("Feuille : " . $sheet->getTitle());

$rows = [];
$header = true;
foreach ($sheet->getRowIterator() as $row) {
    if ($header) { $header = false; continue; }
    $cells     = [];
    $cellsFormatted = [];
    $ci = 0;
    foreach ($row->getCellIterator('A', 'H') as $cell) {
        $cells[]          = trim((string)$cell->getValue());
        $cellsFormatted[] = trim((string)$cell->getFormattedValue());
        $ci++;
    }
    if (empty($cells[2])) continue;
    if (empty($cells[3])) continue;

    $rows[] = [
        'boite'          => $cells[0] ?? '',
        'etiquette'      => $cells[1] ?? '',
        'fichier'        => $cells[2] ?? '',
        'categorie'      => $cells[3] ?? '',
        'date_raw'       => $cells[4] ?? '',         // valeur brute (peut être serial)
        'date_formatted' => $cellsFormatted[4] ?? '', // valeur formatée (dd/mm/yyyy)
        'salle'          => !empty($cells[5]) ? $cells[5] : 'Archive Temara',
        'rangee'         => !empty($cells[6]) ? $cells[6] : '1',
        'etagere'        => !empty($cells[7]) ? $cells[7] : '1',
    ];
}
log_ok(count($rows) . " lignes lues dans l'Excel");

foreach (array_slice($rows, 0, 5) as $i => $r) {
    log_msg(sprintf("  L%d : boite=[%s] etiq=[%s] fichier=[%s] cat=[%s] date_fmt=[%s] salle=[%s]",
        $i+1, $r['boite'], $r['etiquette'], $r['fichier'], $r['categorie'], $r['date_formatted'], $r['salle']));
}

// ============================================================
// INDEX CATÉGORIES
// ============================================================
$categories = DB::table('categories')->get(['id', 'name'])->keyBy('name');
log_msg("Catégories en base : " . $categories->count());

// ============================================================
// CACHES HIÉRARCHIE (Room → Row → Shelf → Box)
// ============================================================
$roomCache  = [];
$rowCache   = [];
$shelfCache = [];
$boxCache   = [];
$now        = now();

function getOrCreateRoom(string $name): int {
    global $roomCache, $now, $DRY_RUN;
    $key = strtolower(trim($name));
    if (isset($roomCache[$key])) return $roomCache[$key];
    if ($DRY_RUN) return 0;
    $ex = DB::table('rooms')->where('name', $name)->first();
    $id = $ex ? $ex->id : DB::table('rooms')->insertGetId(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    return $roomCache[$key] = $id;
}

function getOrCreateRow(int $roomId, string $name): int {
    global $rowCache, $now, $DRY_RUN;
    $key = "{$roomId}|{$name}";
    if (isset($rowCache[$key])) return $rowCache[$key];
    if ($DRY_RUN) return 0;
    $ex = DB::table('rows')->where('room_id', $roomId)->where('name', $name)->first();
    $id = $ex ? $ex->id : DB::table('rows')->insertGetId(['room_id' => $roomId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    return $rowCache[$key] = $id;
}

function getOrCreateShelf(int $rowId, string $name): int {
    global $shelfCache, $now, $DRY_RUN;
    $key = "{$rowId}|{$name}";
    if (isset($shelfCache[$key])) return $shelfCache[$key];
    if ($DRY_RUN) return 0;
    $ex = DB::table('shelves')->where('row_id', $rowId)->where('name', $name)->first();
    $id = $ex ? $ex->id : DB::table('shelves')->insertGetId(['row_id' => $rowId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    return $shelfCache[$key] = $id;
}

function getOrCreateBox(int $shelfId, string $boite): int {
    global $boxCache, $now, $DRY_RUN;
    $key = "{$shelfId}|{$boite}";
    if (isset($boxCache[$key])) return $boxCache[$key];
    if ($DRY_RUN) return 0;
    $ex = DB::table('boxes')->where('shelf_id', $shelfId)->where('name', $boite)->first();
    $id = $ex ? $ex->id : DB::table('boxes')->insertGetId([
        'shelf_id'   => $shelfId,
        'name'       => $boite,
        'box_number' => $boite,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return $boxCache[$key] = $id;
}

// ============================================================
// CACHE TAGS
// ============================================================
$tagCache = [];
function getOrCreateTag(string $name): int {
    global $tagCache, $now, $DRY_RUN;
    if (empty(trim($name))) return 0;
    $key = strtolower(trim($name));
    if (isset($tagCache[$key])) return $tagCache[$key];
    if ($DRY_RUN) return 0;
    $ex = DB::table('tags')->where('name', $name)->first();
    $id = $ex ? $ex->id : DB::table('tags')->insertGetId(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
    return $tagCache[$key] = $id;
}

// ============================================================
// NORMALISE TEXTE
// ============================================================
function normalizeStr(string $s): string {
    $s = str_replace(["\u{2019}", "\u{2018}", "\u{02BC}", "\u{FF07}"], "'", $s);
    $s = str_replace(["\u{00A0}", "\u{202F}"], " ", $s);
    return strtolower(trim($s));
}

// ============================================================
// PARSE DATE — priorité à la valeur formatée (dd/mm/yyyy)
// ============================================================
function parseDate(string $raw, string $formatted): ?string {
    // Essayer la valeur formatée d'abord
    foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $fmt) {
        $parsed = date_create_from_format($fmt, $formatted);
        if ($parsed) return $parsed->format('Y-m-d');
    }
    // Fallback : serial Excel
    if (is_numeric($raw)) {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$raw)->format('Y-m-d');
        } catch (\Exception $e) {}
    }
    // Fallback : raw texte
    foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y'] as $fmt) {
        $parsed = date_create_from_format($fmt, $raw);
        if ($parsed) return $parsed->format('Y-m-d');
    }
    return null;
}

// ============================================================
// IMPORT
// ============================================================
$created = $skipped = $moved = $errors = $nocatErr = $nofileErr = 0;
$catErrList = [];
$expireAt = date('Y-m-d', strtotime('+99 years'));

log_msg("\nDémarrage import...\n");

foreach ($rows as $row) {
    $nomFichier = $row['fichier'];
    $catName    = $row['categorie'];
    $etiquette  = $row['etiquette'];

    // Catégorie
    $category = $categories->get($catName)
        ?? $categories->first(fn($c) => normalizeStr($c->name) === normalizeStr($catName));

    if (!$category) {
        if (!isset($catErrList[$catName])) { log_err("Cat. inconnue : [{$catName}]"); $catErrList[$catName] = 0; }
        $catErrList[$catName]++; $nocatErr++; $errors++;
        continue;
    }

    // Déduplication
    $title = pathinfo($nomFichier, PATHINFO_FILENAME);
    $existing = DB::table('documents')
        ->where('title', $title)->where('category_id', $category->id)->whereNull('deleted_at')->first();
    if ($existing) { $skipped++; continue; }

    // Recherche fichier
    $sourcePath = $poleCode = null;
    foreach ($polesDisponibles as $pole) {
        $candidate = $DOCS_ROOT . '/' . $pole . '/' . $catName . '/' . $nomFichier;
        if (file_exists($candidate)) { $sourcePath = $candidate; $poleCode = $pole; break; }
    }
    if (!$sourcePath) {
        if ($nofileErr < 10) log_err("Fichier introuvable : {$nomFichier} (cat: {$catName})");
        $nofileErr++; $errors++;
        continue;
    }

    // Hiérarchie emplacement
    $roomId  = getOrCreateRoom($row['salle']);
    $rowId   = getOrCreateRow($roomId, 'R' . $row['rangee']);
    $shelfId = getOrCreateShelf($rowId, 'E' . $row['etagere']);
    $boxId   = getOrCreateBox($shelfId, $row['boite']);

    // Date
    $docDate = parseDate($row['date_raw'], $row['date_formatted']);

    // Chemins storage
    $storageCatPath = $STORAGE_PATH . '/' . $poleCode . '/' . $catName;
    $destPath       = $storageCatPath . '/' . $nomFichier;
    $storageRelPath = 'spcr-import/' . $poleCode . '/' . $catName . '/' . $nomFichier;

    if ($DRY_RUN) {
        log_msg("[DRY] {$poleCode}/{$catName}/{$nomFichier} | etiq=[{$etiquette}] | box=[{$boxId}] | date=[{$docDate}]");
        $created++;
        continue;
    }

    if (!is_dir($storageCatPath)) mkdir($storageCatPath, 0755, true);

    if (!copy($sourcePath, $destPath)) {
        log_err("Impossible de copier : {$nomFichier}"); $errors++;
        continue;
    }
    $moved++;

    $fileHash = hash_file('sha256', $destPath) ?: null;

    try {
        $metadata = [];
        if ($docDate) $metadata['document_date'] = $docDate;

        $docData = [
            'uid'        => Str::uuid()->toString(),
            'title'      => $title,
            'category_id'=> $category->id,
            'status'     => 'approved',
            'entry_type' => 'direct_archive',
            'created_by' => $CREATED_BY,
            'expire_at'  => $expireAt,
            'is_expired' => 0,
            'box_id'     => $boxId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (!empty($metadata)) $docData['metadata'] = json_encode($metadata);
        if ($fileHash)         $docData['file_hash'] = $fileHash;

        $docId = DB::table('documents')->insertGetId($docData);

        // document_versions
        DB::table('document_versions')->insert([
            'document_id'    => $docId,
            'version_number' => 1.0,
            'file_path'      => $storageRelPath,
            'file_type'      => 'application/pdf',
            'uploaded_by'    => $CREATED_BY,
            'uploaded_at'    => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // Tags (étiquette)
        if (!empty($etiquette)) {
            $tagId = getOrCreateTag($etiquette);
            if ($tagId) {
                DB::table('document_tags')->insert([
                    'document_id' => $docId,
                    'tag_id'      => $tagId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        $created++;
        if ($created % 50 === 0) log_ok("{$created} documents importés...");

    } catch (\Exception $e) {
        log_err("DB Error [{$nomFichier}] : " . $e->getMessage());
        @unlink($destPath); $moved--; $errors++;
    }
}

if (!empty($catErrList)) {
    log_msg("\nCatégories introuvables (" . count($catErrList) . ") :");
    foreach ($catErrList as $name => $count) log_msg("  [{$name}] x{$count}");
}

log_msg("\n========= RÉSUMÉ FINAL =========");
log_ok("Documents importés : {$created}");
log_ok("Fichiers copiés    : {$moved}");
log_msg("Déjà en base       : {$skipped}");
log_msg("Fichiers manquants : {$nofileErr}");
log_msg("Cat. inconnues     : {$nocatErr}");
if ($errors > 0) log_err("Total erreurs      : {$errors}");
else             log_ok("Erreurs            : 0");
log_msg("Durée              : " . round(microtime(true) - LARAVEL_START, 2) . "s");
