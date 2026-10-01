<?php
/**
 * Script import SPCR Rabat — FINAL
 * ==================================
 * Lit l'Excel (sheet "Fichiers PDF"), crée les emplacements physiques,
 * déplace les fichiers et insère dans documents + document_attachments.
 *
 * Placer dans : C:\inetpub\Locaged\
 * Lancer      : php import_spcr_final.php
 *
 * CONFIG : adapter les 3 chemins ci-dessous
 */

// ============================================================
// CONFIG
// ============================================================
$DOCS_ROOT    = '/var/www/locaged-v2/test_docs'; // racine A1, A2...
$EXCEL_PATH   = '/var/www/locaged-v2/Inventaire_SPCR_pour injection copie.xlsx';
$STORAGE_PATH = '/var/www/locaged-v2/storage/app/private/spcr-import';
$CREATED_BY   = 1;       // ID user master
$DRY_RUN      = false;   // true = simulation, rien n'est écrit

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

// ============================================================
// HELPERS
// ============================================================
function log_msg(string $m): void { echo "[" . date('H:i:s') . "] {$m}\n"; flush(); }
function log_ok(string $m): void  { echo "[OK]  {$m}\n"; flush(); }
function log_err(string $m): void { echo "[ERR] {$m}\n"; flush(); }

// ============================================================
// VÉRIFICATIONS INITIALES
// ============================================================
log_msg("=== IMPORT SPCR FINAL ===");
log_msg("Excel : {$EXCEL_PATH}");
log_msg("Docs  : {$DOCS_ROOT}");
log_msg("DRY   : " . ($DRY_RUN ? 'OUI' : 'NON'));

if (!file_exists($EXCEL_PATH)) {
    log_err("Excel introuvable : {$EXCEL_PATH}"); exit(1);
}
if (!is_dir($DOCS_ROOT)) {
    log_err("Dossier docs introuvable : {$DOCS_ROOT}"); exit(1);
}
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    log_err("PhpSpreadsheet non installé.");
    log_err("Lancer : composer require phpoffice/phpspreadsheet --no-interaction");
    exit(1);
}

// ============================================================
// LECTURE EXCEL — sheet "Fichiers PDF"
// Colonnes : 0=cote, 1=catégorie, 2=nom_fichier, 4=date, 5=salle, 6=rangée, 7=étagère
// ============================================================
log_msg("Lecture Excel...");
$spreadsheet = IOFactory::load($EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Fichiers PDF') ?? $spreadsheet->getActiveSheet();

$rows = [];
$header = true;
foreach ($sheet->getRowIterator() as $row) {
    if ($header) { $header = false; continue; }
    $cells = [];
    foreach ($row->getCellIterator('A', 'H') as $cell) {
        $cells[] = trim((string)$cell->getValue());
    }
    if (empty($cells[2])) continue; // pas de nom de fichier = ligne vide
    $rows[] = [
        'cote'       => $cells[0] ?? '',
        'categorie'  => $cells[1] ?? '',
        'fichier'    => $cells[2] ?? '',
        'date'       => $cells[4] ?? '',
        'salle'      => $cells[5] ?? 'Archive Temara',
        'rangee'     => $cells[6] ?? '',
        'etagere'    => $cells[7] ?? '',
    ];
}
log_ok(count($rows) . " lignes lues dans l'Excel");

// ============================================================
// INDEX DES CATÉGORIES (par nom)
// ============================================================
$categories = DB::table('categories')
    
    ->get(['id', 'name'])
    ->keyBy('name');

// ============================================================
// CRÉER / RÉCUPÉRER LES EMPLACEMENTS PHYSIQUES
// Structure : PhysicalLocation (salle) → Row → Shelf → Box
// On crée une PhysicalLocation "Archive Temara" si elle n'existe pas
// Puis les Rows et Shelves à la volée
// ============================================================

// PhysicalLocation (salle)
function getOrCreateLocation(string $nom): int {
    $loc = DB::table('physical_locations')->where('name', $nom)->first();
    if ($loc) return $loc->id;
    return DB::table('physical_locations')->insertGetId([
        'name'       => $nom,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Room
function getOrCreateRoom(int $locationId, string $nom): int {
    $r = DB::table('rooms')->where('physical_location_id', $locationId)->where('name', $nom)->first();
    if ($r) return $r->id;
    return DB::table('rooms')->insertGetId([
        'physical_location_id' => $locationId,
        'name'       => $nom,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Row
function getOrCreateRow(int $roomId, string $nom): int {
    $r = DB::table('rows')->where('room_id', $roomId)->where('name', $nom)->first();
    if ($r) return $r->id;
    return DB::table('rows')->insertGetId([
        'room_id'    => $roomId,
        'name'       => $nom,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Shelf
function getOrCreateShelf(int $rowId, string $nom): int {
    $s = DB::table('shelves')->where('row_id', $rowId)->where('name', $nom)->first();
    if ($s) return $s->id;
    return DB::table('shelves')->insertGetId([
        'row_id'     => $rowId,
        'name'       => $nom,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Box (par cote topographique unique)
function getOrCreateBox(int $shelfId, string $cote, string $nom): int {
    $b = DB::table('boxes')->where('name', $cote)->first();
    if ($b) return $b->id;
    return DB::table('boxes')->insertGetId([
        'shelf_id'   => $shelfId,
        'name'       => $cote,  // cote = identifiant unique (ex: 1.1.1.1)
        'label'      => $nom,
        'status'     => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Cache pour éviter les requêtes répétées
$locationCache = [];
$boxCache      = [];

function resolveBox(array $row): ?int {
    global $locationCache, $boxCache, $DRY_RUN;

    $cote    = $row['cote'];
    $salle   = $row['salle'] ?: 'Archive Temara';
    $rangee  = $row['rangee'] ?: '1';
    $etagere = $row['etagere'] ?: '1';

    if (!$cote) return null;
    if (isset($boxCache[$cote])) return $boxCache[$cote];
    if ($DRY_RUN) return null;

    $cacheKey = "{$salle}|{$rangee}|{$etagere}";
    if (!isset($locationCache[$cacheKey])) {
        $locId   = getOrCreateLocation($salle);
        $roomId  = getOrCreateRoom($locId, $salle);
        $rowId   = getOrCreateRow($roomId, "Rangée {$rangee}");
        $shelfId = getOrCreateShelf($rowId, "Étagère {$etagere}");
        $locationCache[$cacheKey] = $shelfId;
    }
    $shelfId = $locationCache[$cacheKey];

    $boxId = getOrCreateBox($shelfId, $cote, $cote);
    $boxCache[$cote] = $boxId;
    return $boxId;
}

// ============================================================
// IMPORT
// ============================================================
$created  = 0;
$skipped  = 0;
$moved    = 0;
$errors   = 0;
$nocatCount = 0;
$nofile   = 0;

$expireAt = date('Y-m-d', strtotime('+99 years'));
$now = now();

log_msg("Démarrage import...\n");

foreach ($rows as $idx => $row) {
    $nomFichier = $row['fichier'];
    $catName    = $row['categorie'];

    // Catégorie
    $category = $categories->get($catName)
        ?? $categories->first(fn($c) => strtolower(trim($c->name)) === strtolower(trim($catName)));

    if (!$category) {
        if ($nocatCount < 5) log_err("Cat. non trouvée : [{$catName}] pour {$nomFichier}");
        $nocatCount++;
        $errors++;
        continue;
    }

    // Déduplication
    $title = pathinfo($nomFichier, PATHINFO_FILENAME);
    $existing = DB::table('documents')
        ->where('title', $title)
        ->where('category_id', $category->id)
        
        ->first();

    if ($existing) {
        $skipped++;
        continue;
    }

    // Trouver le fichier source
    // Cherche dans toutes les sous-arborescences de $DOCS_ROOT
    $sourcePath = null;
    $poleCode   = null;

    // D'abord chercher dans le dossier attendu selon la catégorie
    $poles = ['A1','A2','A3','A4','A5','A6'];
    foreach ($poles as $pole) {
        $candidate = $DOCS_ROOT . DIRECTORY_SEPARATOR . $pole . DIRECTORY_SEPARATOR . $catName . DIRECTORY_SEPARATOR . $nomFichier;
        if (file_exists($candidate)) {
            $sourcePath = $candidate;
            $poleCode   = $pole;
            break;
        }
    }

    if (!$sourcePath) {
        if ($nofile < 5) log_err("Fichier introuvable : {$nomFichier}");
        $nofile++;
        $errors++;
        continue;
    }

    // Emplacement physique (box)
    $boxId = resolveBox($row);

    // Date document
    $docDate = null;
    if (!empty($row['date'])) {
        // PhpSpreadsheet retourne les dates Excel comme float ou string
        if (is_numeric($row['date'])) {
            $docDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$row['date'])->format('Y-m-d');
        } else {
            // Tenter parsing manuel DD/MM/YYYY ou YYYY-MM-DD
            $parsed = date_create_from_format('d/m/Y', $row['date'])
                   ?? date_create_from_format('Y-m-d', $row['date']);
            $docDate = $parsed ? $parsed->format('Y-m-d') : null;
        }
    }

    // Chemin storage
    $storageCatPath = $STORAGE_PATH . DIRECTORY_SEPARATOR . $poleCode . DIRECTORY_SEPARATOR . $catName;
    $destPath       = $storageCatPath . DIRECTORY_SEPARATOR . $nomFichier;
    $storageRelPath = 'spcr-import/' . $poleCode . '/' . $catName . '/' . $nomFichier;

    if ($DRY_RUN) {
        log_msg("[DRY] {$catName} | {$nomFichier}");
        $created++;
        continue;
    }

    // Créer le dossier destination si besoin
    if (!is_dir($storageCatPath)) {
        mkdir($storageCatPath, 0755, true);
    }

    // Déplacer le fichier
    if (!rename($sourcePath, $destPath)) {
        log_err("Impossible de déplacer : {$nomFichier}");
        $errors++;
        continue;
    }
    $moved++;

    try {
        $docData = [
            'uid'         => Str::uuid()->toString(),
            'title'       => $title,
            'category_id' => $category->id,
            'status'      => 'approved',
            'entry_type'  => 'direct_archive',
            'created_by'  => $CREATED_BY,
            'expire_at'   => $expireAt,
            'is_expired'  => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];

        // Champs optionnels
        if ($boxId)   $docData['box_id']        = $boxId;
        if ($docDate) $docData['metadata']       = json_encode(['document_date' => $docDate]);

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
        if ($created % 100 === 0) {
            log_ok("{$created} documents importés...");
        }

    } catch (\Exception $e) {
        log_err("DB Error [{$nomFichier}] : " . $e->getMessage());
        rename($destPath, $sourcePath); // rollback move
        $moved--;
        $errors++;
    }
}

// ============================================================
// RÉSUMÉ
// ============================================================
log_msg("\n========= RÉSUMÉ FINAL =========");
log_ok("Documents importés : {$created}");
log_ok("Fichiers déplacés  : {$moved}");
log_msg("Déjà en base       : {$skipped}");
log_msg("Fichiers manquants : {$nofile}");
log_msg("Catégories inconnues: {$nocatCount}");
if ($errors > 0) {
    log_err("Total erreurs      : {$errors}");
} else {
    log_ok("Erreurs            : 0");
}
log_msg("Durée              : " . round(microtime(true) - LARAVEL_START, 2) . "s");
