<?php
/**
 * Script import SPCR Rabat — v8 (TEST VPS Hetzner)
 * ===================================================
 * Corrections v8 :
 *  - resolveBoxId : lookup case-sensitive (pas de strtolower) pour matcher
 *    exactement les noms en base (R1, E1, etc.)
 *  - expire_at : calculé depuis docDate + expiry_value/expiry_unit de la catégorie
 *    (pas 99 ans en dur)
 *  - categories chargées avec expiry_value + expiry_unit
 *
 * Colonnes Excel (feuille "Fichiers PDF") :
 *   A (0) = boîte      ex: 1.1.1.1
 *   B (1) = étiquette  ex: ALSA CITY BUS
 *   C (2) = nom fichier PDF
 *   D (3) = catégorie  (une des 22 catégories réelles en base)
 *   E (4) = date       (Excel date → getFormattedValue → MM/DD/YYYY)
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

function log_msg(string $m): void { echo "[" . date('H:i:s') . "] {$m}\n"; flush(); }
function log_ok(string $m): void  { echo "[OK]  {$m}\n"; flush(); }
function log_err(string $m): void { echo "[ERR] {$m}\n"; flush(); }

log_msg("=== IMPORT SPCR v8 — TEST VPS ===");
log_msg("Excel : {$EXCEL_PATH}");
log_msg("Docs  : {$DOCS_ROOT}");
log_msg("DRY   : " . ($DRY_RUN ? 'OUI' : 'NON'));

if (!file_exists($EXCEL_PATH)) { log_err("Excel introuvable"); exit(1); }
if (!is_dir($DOCS_ROOT))       { log_err("Dossier docs introuvable"); exit(1); }

$hasDocVersions = !empty(DB::select("SHOW TABLES LIKE 'document_versions'"));
$hasTags        = !empty(DB::select("SHOW TABLES LIKE 'tags'"));
log_msg("Tables : document_versions=" . ($hasDocVersions ? 'OUI' : 'NON') . " / tags=" . ($hasTags ? 'OUI' : 'NON'));
if (!$hasDocVersions) { log_err("Table document_versions introuvable"); exit(1); }

$polesDisponibles = [];
foreach (glob($DOCS_ROOT . '/*', GLOB_ONLYDIR) as $d) {
    $polesDisponibles[] = basename($d);
}
log_msg("Pôles : " . implode(', ', $polesDisponibles));

// ============================================================
// LECTURE EXCEL
// ============================================================
log_msg("Lecture Excel...");
$spreadsheet = IOFactory::load($EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Fichiers PDF') ?? $spreadsheet->getActiveSheet();
log_msg("Feuille : " . $sheet->getTitle());

$rows = [];
$header = true;
foreach ($sheet->getRowIterator() as $row) {
    if ($header) { $header = false; continue; }
    $cells = []; $cellsF = [];
    foreach ($row->getCellIterator('A', 'H') as $cell) {
        $cells[]  = trim((string)$cell->getValue());
        $cellsF[] = trim((string)$cell->getFormattedValue());
    }
    if (empty($cells[2])) continue;
    if (empty($cells[3])) continue;
    $rows[] = [
        'boite'          => $cells[0] ?? '',
        'etiquette'      => $cells[1] ?? '',
        'fichier'        => $cells[2] ?? '',
        'categorie'      => $cells[3] ?? '',
        'date_raw'       => $cells[4] ?? '',
        'date_formatted' => $cellsF[4] ?? '',
        'salle'          => !empty($cells[5]) ? $cells[5] : 'Archive Temara',
        'rangee'         => !empty($cells[6]) ? $cells[6] : '1',
        'etagere'        => !empty($cells[7]) ? $cells[7] : '1',
    ];
}
log_ok(count($rows) . " lignes lues dans l'Excel");
foreach (array_slice($rows, 0, 3) as $i => $r) {
    log_msg(sprintf("  L%d : boite=[%s] fichier=[%s] date_fmt=[%s]",
        $i+1, $r['boite'], $r['fichier'], $r['date_formatted']));
}

// ============================================================
// INDEX CATÉGORIES avec expiry
// ============================================================
$categories = DB::table('categories')
    ->get(['id', 'name', 'expiry_value', 'expiry_unit'])
    ->keyBy('name');
log_msg("Catégories en base : " . $categories->count());

// Debug expiry d'une catégorie
$sampleCat = $categories->first();
log_msg("Expiry exemple : [{$sampleCat->name}] → {$sampleCat->expiry_value} {$sampleCat->expiry_unit}");

// ============================================================
// CHARGER HIÉRARCHIE EN MÉMOIRE — sans strtolower (case-sensitive)
// ============================================================
log_msg("Chargement hiérarchie en mémoire...");

// rooms : keyBy nom exact
$roomByName = [];
foreach (DB::table('rooms')->get(['id', 'name']) as $r) {
    $roomByName[trim($r->name)] = $r->id;
}

// rows : keyBy "room_id|nom_exact"
$rowByKey = [];
foreach (DB::table('rows')->get(['id', 'room_id', 'name']) as $r) {
    $rowByKey["{$r->room_id}|" . trim($r->name)] = $r->id;
}

// shelves : keyBy "row_id|nom_exact"
$shelfByKey = [];
foreach (DB::table('shelves')->get(['id', 'row_id', 'name']) as $s) {
    $shelfByKey["{$s->row_id}|" . trim($s->name)] = $s->id;
}

// boxes : keyBy "shelf_id|nom_exact"
$boxByKey = [];
foreach (DB::table('boxes')->get(['id', 'shelf_id', 'name']) as $b) {
    $boxByKey["{$b->shelf_id}|" . trim($b->name)] = $b->id;
}

log_ok("Rooms: " . count($roomByName) . " / Rows: " . count($rowByKey)
    . " / Shelves: " . count($shelfByKey) . " / Boxes: " . count($boxByKey));

// Debug : afficher les noms exacts des rooms et shelves
log_msg("Rooms disponibles : " . implode(', ', array_keys($roomByName)));
$sampleShelves = array_slice(array_keys($shelfByKey), 0, 5);
log_msg("Shelves (5 premiers) : " . implode(', ', $sampleShelves));

function resolveBoxId(string $salle, string $rangee, string $etagere, string $boite): ?int {
    global $roomByName, $rowByKey, $shelfByKey, $boxByKey;

    $rowName   = 'R' . $rangee;   // ex: R1
    $shelfName = 'E' . $etagere;  // ex: E1

    if (!isset($roomByName[$salle])) {
        log_err("Room introuvable : [{$salle}] — rooms dispo: " . implode(', ', array_keys($roomByName)));
        return null;
    }
    $roomId = $roomByName[$salle];

    if (!isset($rowByKey["{$roomId}|{$rowName}"])) {
        log_err("Row introuvable : [{$rowName}] dans room [{$salle}] (room_id={$roomId})");
        return null;
    }
    $rowId = $rowByKey["{$roomId}|{$rowName}"];

    if (!isset($shelfByKey["{$rowId}|{$shelfName}"])) {
        log_err("Shelf introuvable : [{$shelfName}] dans row [{$rowName}] (row_id={$rowId})");
        return null;
    }
    $shelfId = $shelfByKey["{$rowId}|{$shelfName}"];

    if (!isset($boxByKey["{$shelfId}|{$boite}"])) {
        log_err("Box introuvable : [{$boite}] dans shelf [{$shelfName}] (shelf_id={$shelfId})");
        return null;
    }
    return $boxByKey["{$shelfId}|{$boite}"];
}

// ============================================================
// CACHE TAGS
// ============================================================
$tagCache = [];
function getOrCreateTag(string $name): int {
    global $tagCache;
    if (empty(trim($name))) return 0;
    $key = strtolower(trim($name));
    if (isset($tagCache[$key])) return $tagCache[$key];
    $ex = DB::table('tags')->where('name', $name)->first();
    $id = $ex ? $ex->id : DB::table('tags')->insertGetId([
        'name' => $name, 'created_at' => now(), 'updated_at' => now(),
    ]);
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
// PARSE DATE — gère MM/DD/YYYY (Excel US), DD/MM/YYYY, DD/MM/YY, etc.
// ============================================================
function parseDate(string $raw, string $formatted): ?string {
    $formats = ['m/d/Y', 'd/m/Y', 'd/m/y', 'm/d/y', 'Y-m-d', 'd-m-Y'];
    foreach ($formats as $fmt) {
        $parsed = date_create_from_format($fmt, $formatted);
        if ($parsed !== false) {
            $y = (int)$parsed->format('Y');
            if ($y >= 1900 && $y <= 2100) return $parsed->format('Y-m-d');
        }
    }
    // Fallback serial Excel
    if (is_numeric($raw) && (float)$raw > 1) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$raw);
            $y = (int)$dt->format('Y');
            if ($y >= 1900 && $y <= 2100) return $dt->format('Y-m-d');
        } catch (\Exception $e) {}
    }
    // Fallback raw texte
    foreach ($formats as $fmt) {
        $parsed = date_create_from_format($fmt, $raw);
        if ($parsed !== false) {
            $y = (int)$parsed->format('Y');
            if ($y >= 1900 && $y <= 2100) return $parsed->format('Y-m-d');
        }
    }
    return null;
}

// ============================================================
// CALCUL expire_at depuis catégorie
// ============================================================
function calcExpireAt(?string $docDate, object $category): string {
    $base = $docDate ?? date('Y-m-d');
    $value = (int)($category->expiry_value ?? 99);
    $unit  = $category->expiry_unit ?? 'years';
    // Normaliser l'unité pour strtotime
    $unitMap = [
        'year'   => 'years',  'years'  => 'years',
        'month'  => 'months', 'months' => 'months',
        'day'    => 'days',   'days'   => 'days',
    ];
    $unitStr = $unitMap[strtolower($unit)] ?? 'years';
    return date('Y-m-d', strtotime("{$base} +{$value} {$unitStr}"));
}

// ============================================================
// IMPORT
// ============================================================
$created = $skipped = $moved = $errors = $nocatErr = $nofileErr = 0;
$catErrList = [];
$now = now();

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

    // Box (lookup hiérarchique sans insert)
    $boxId = resolveBoxId($row['salle'], $row['rangee'], $row['etagere'], $row['boite']);

    // Date du document
    $docDate  = parseDate($row['date_raw'], $row['date_formatted']);

    // expire_at depuis catégorie
    $expireAt = calcExpireAt($docDate, $category);

    // Chemins storage
    $storageCatPath = $STORAGE_PATH . '/' . $poleCode . '/' . $catName;
    $destPath       = $storageCatPath . '/' . $nomFichier;
    $storageRelPath = 'spcr-import/' . $poleCode . '/' . $catName . '/' . $nomFichier;

    if ($DRY_RUN) {
        log_msg("[DRY] box=[{$boxId}] date=[{$docDate}] expire=[{$expireAt}] {$nomFichier}");
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
            'uid'         => Str::uuid()->toString(),
            'title'       => $title,
            'category_id' => $category->id,
            'status'      => 'approved',
            'entry_type'  => 'direct_archive',
            'created_by'  => $CREATED_BY,
            'expire_at'   => $expireAt,
            'is_expired'  => 0,
            'box_id'      => $boxId ?: null,
            'created_at'  => $docDate ? $docDate . ' 00:00:00' : $now,
            'updated_at'  => $now,
        ];
        if (!empty($metadata)) $docData['metadata'] = json_encode($metadata);
        if ($fileHash)         $docData['file_hash'] = $fileHash;

        $docId = DB::table('documents')->insertGetId($docData);

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

        if (!empty($etiquette)) {
            $tagId = getOrCreateTag($etiquette);
            if ($tagId) {
                DB::table('document_tags')->insert([
                    'document_id' => $docId,
                    'tag_id'      => $tagId,
                    'created_at'  => $docDate ? $docDate . ' 00:00:00' : $now,
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
