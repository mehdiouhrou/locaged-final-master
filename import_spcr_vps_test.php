<?php
/**
 * Script import SPCR Rabat — v3 (TEST VPS Hetzner)
 * ===================================================
 * DOCS_ROOT    = /var/www/locaged-v2/test_docs
 * EXCEL_PATH   = /var/www/locaged-v2/Inventaire_SPCR_pour injection copie.xlsx
 * STORAGE_PATH = /var/www/locaged-v2/storage/app/private/spcr-import
 */

// ============================================================
// CONFIG
// ============================================================
$DOCS_ROOT    = __DIR__ . '/test_docs';
$EXCEL_PATH   = __DIR__ . '/Inventaire_SPCR_pour injection copie.xlsx';
$STORAGE_PATH = __DIR__ . '/storage/app/private/spcr-import';
$CREATED_BY   = 1;
$DRY_RUN      = false;

// ============================================================
// BOOTSTRAP
// ============================================================
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

// ============================================================
// VÉRIFICATIONS
// ============================================================
log_msg("=== IMPORT SPCR v3 — TEST VPS ===");
log_msg("Excel : {$EXCEL_PATH}");
log_msg("Docs  : {$DOCS_ROOT}");
log_msg("DRY   : " . ($DRY_RUN ? 'OUI' : 'NON'));

if (!file_exists($EXCEL_PATH)) { log_err("Excel introuvable : {$EXCEL_PATH}"); exit(1); }
if (!is_dir($DOCS_ROOT))       { log_err("Dossier docs introuvable : {$DOCS_ROOT}"); exit(1); }
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    log_err("PhpSpreadsheet non installé. Lancer : composer require phpoffice/phpspreadsheet --no-dev");
    exit(1);
}

// ============================================================
// LISTE LES DOSSIERS DISPONIBLES DANS test_docs
// ============================================================
$polesDisponibles = [];
foreach (glob($DOCS_ROOT . '/*', GLOB_ONLYDIR) as $d) {
    $polesDisponibles[] = basename($d);
}
log_msg("Pôles trouvés dans test_docs : " . implode(', ', $polesDisponibles));

// ============================================================
// LECTURE EXCEL
// Colonnes : 0=boite(1.1.1.1), 1=categorie, 2=nom_fichier, 4=date, 5=salle, 6=rangee, 7=etagere
// ============================================================
log_msg("Lecture Excel...");
$spreadsheet = IOFactory::load($EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Fichiers PDF') ?? $spreadsheet->getActiveSheet();
log_msg("Feuille chargée : " . $sheet->getTitle());

$rows = [];
$header = true;
foreach ($sheet->getRowIterator() as $row) {
    if ($header) { $header = false; continue; }
    $cells = [];
    foreach ($row->getCellIterator('A', 'H') as $cell) {
        $cells[] = trim((string)$cell->getValue());
    }
    if (empty($cells[2])) continue;
    $rows[] = [
        'boite'    => $cells[0] ?? '',
        'categorie'=> $cells[1] ?? '',
        'fichier'  => $cells[2] ?? '',
        'date_raw' => $cells[4] ?? '',
        'salle'    => !empty($cells[5]) ? $cells[5] : 'Archive Temara',
        'rangee'   => !empty($cells[6]) ? $cells[6] : '1',
        'etagere'  => !empty($cells[7]) ? $cells[7] : '1',
    ];
}
log_ok(count($rows) . " lignes lues dans l'Excel");

// Afficher les 3 premières lignes pour vérif
foreach (array_slice($rows, 0, 3) as $i => $r) {
    log_msg("  Ligne " . ($i+1) . " : boite=[{$r['boite']}] cat=[{$r['categorie']}] fichier=[{$r['fichier']}] date=[{$r['date_raw']}] salle=[{$r['salle']}]");
}

// ============================================================
// INDEX CATÉGORIES
// ============================================================
$categories = DB::table('categories')
    ->get(['id', 'name'])
    ->keyBy('name');
log_msg("Catégories en base : " . $categories->count());

// ============================================================
// CACHE PHYSICAL LOCATIONS
// physical_locations : id, room, row, shelf, box, description
// ============================================================
$locationCache = [];

function getOrCreateLocation(string $room, string $row, string $shelf, string $box): int {
    global $locationCache, $DRY_RUN;
    $key = "{$room}|{$row}|{$shelf}|{$box}";
    if (isset($locationCache[$key])) return $locationCache[$key];
    if ($DRY_RUN) return 0;

    $existing = DB::table('physical_locations')
        ->where('room', $room)
        ->where('row', $row)
        ->where('shelf', $shelf)
        ->where('box', $box)
        ->first();

    if ($existing) {
        $locationCache[$key] = $existing->id;
        return $existing->id;
    }

    $id = DB::table('physical_locations')->insertGetId([
        'room'       => $room,
        'row'        => $row,
        'shelf'      => $shelf,
        'box'        => $box,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $locationCache[$key] = $id;
    return $id;
}

// ============================================================
// PARSE DATE
// ============================================================
function parseDate(string $raw): ?string {
    if (empty($raw)) return null;
    if (is_numeric($raw)) {
        try {
            return XlDate::excelToDateTimeObject((float)$raw)->format('Y-m-d');
        } catch (\Exception $e) { return null; }
    }
    $parsed = date_create_from_format('d/m/Y', $raw)
           ?: date_create_from_format('Y-m-d', $raw)
           ?: date_create_from_format('d-m-Y', $raw)
           ?: date_create_from_format('m/d/Y', $raw);
    return $parsed ? $parsed->format('Y-m-d') : null;
}

// ============================================================
// IMPORT
// ============================================================
$created   = 0;
$skipped   = 0;
$moved     = 0;
$errors    = 0;
$nocatErr  = 0;
$nofileErr = 0;
$catErrList = [];

$expireAt = date('Y-m-d', strtotime('+99 years'));
$now = now();

log_msg("Démarrage import...\n");

foreach ($rows as $idx => $row) {
    $nomFichier = $row['fichier'];
    $catName    = $row['categorie'];

    // Catégorie — exact puis insensible à la casse
    $category = $categories->get($catName)
        ?? $categories->first(fn($c) => strtolower(trim($c->name)) === strtolower(trim($catName)));

    if (!$category) {
        if (!isset($catErrList[$catName])) {
            log_err("Cat. inconnue : [{$catName}]");
            $catErrList[$catName] = 0;
        }
        $catErrList[$catName]++;
        $nocatErr++;
        $errors++;
        continue;
    }

    // Déduplication par title + category_id
    $title = pathinfo($nomFichier, PATHINFO_FILENAME);
    $existing = DB::table('documents')
        ->where('title', $title)
        ->where('category_id', $category->id)
        ->whereNull('deleted_at')
        ->first();

    if ($existing) { $skipped++; continue; }

    // Recherche fichier dans tous les pôles
    $sourcePath = null;
    $poleCode   = null;
    foreach ($polesDisponibles as $pole) {
        $candidate = $DOCS_ROOT . '/' . $pole . '/' . $catName . '/' . $nomFichier;
        if (file_exists($candidate)) {
            $sourcePath = $candidate;
            $poleCode   = $pole;
            break;
        }
    }

    if (!$sourcePath) {
        if ($nofileErr < 10) log_err("Fichier introuvable : {$nomFichier} (cat: {$catName})");
        $nofileErr++;
        $errors++;
        continue;
    }

    // Emplacement physique
    $locId = getOrCreateLocation(
        $row['salle'],
        'Rangée ' . $row['rangee'],
        'Étagère ' . $row['etagere'],
        $row['boite']
    );

    // Date document
    $docDate = parseDate($row['date_raw']);

    // Chemins storage
    $storageCatPath = $STORAGE_PATH . '/' . $poleCode . '/' . $catName;
    $destPath       = $storageCatPath . '/' . $nomFichier;
    $storageRelPath = 'spcr-import/' . $poleCode . '/' . $catName . '/' . $nomFichier;

    if ($DRY_RUN) {
        log_msg("[DRY] {$poleCode}/{$catName}/{$nomFichier}");
        $created++;
        continue;
    }

    if (!is_dir($storageCatPath)) mkdir($storageCatPath, 0755, true);

    // Sur VPS on utilise copy() pour garder les fichiers dans test_docs
    if (!copy($sourcePath, $destPath)) {
        log_err("Impossible de copier : {$nomFichier}");
        $errors++;
        continue;
    }
    $moved++;

    try {
        $docData = [
            'uid'                  => Str::uuid()->toString(),
            'title'                => $title,
            'category_id'          => $category->id,
            'status'               => 'approved',
            'entry_type'           => 'direct_archive',
            'created_by'           => $CREATED_BY,
            'expire_at'            => $expireAt,
            'is_expired'           => 0,
            'physical_location_id' => $locId ?: null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];
        if ($docDate) {
            $docData['metadata'] = json_encode(['document_date' => $docDate]);
        }

        $docId = DB::table('documents')->insertGetId($docData);

        DB::table('document_attachments')->insert([
            'document_id' => $docId,
            'uploaded_by' => $CREATED_BY,
            'label'       => $nomFichier,
            'file_path'   => $storageRelPath,
            'file_type'   => 'application/pdf',
            'uploaded_at' => $now,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $created++;
        if ($created % 50 === 0) log_ok("{$created} documents importés...");

    } catch (\Exception $e) {
        log_err("DB Error [{$nomFichier}] : " . $e->getMessage());
        @unlink($destPath);
        $moved--;
        $errors++;
    }
}

// Résumé catégories inconnues si plusieurs
if (!empty($catErrList)) {
    log_msg("\nCatégories introuvables en base (" . count($catErrList) . " distinctes) :");
    foreach ($catErrList as $name => $count) {
        log_msg("  [{$name}] x{$count}");
    }
}

// ============================================================
// RÉSUMÉ
// ============================================================
log_msg("\n========= RÉSUMÉ FINAL =========");
log_ok("Documents importés   : {$created}");
log_ok("Fichiers copiés      : {$moved}");
log_msg("Déjà en base         : {$skipped}");
log_msg("Fichiers manquants   : {$nofileErr}");
log_msg("Catégories inconnues : {$nocatErr}");
if ($errors > 0) log_err("Total erreurs        : {$errors}");
else             log_ok("Erreurs              : 0");
log_msg("Durée                : " . round(microtime(true) - LARAVEL_START, 2) . "s");
