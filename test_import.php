<?php
/**
 * Script de test import v2 — sans Excel
 * Scanne test_docs/A1/ALSA CITY BUS/ et importe les PDFs dans la catégorie ALSA CITY BUS
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function log_msg($msg) { echo "[" . date('H:i:s') . "] {$msg}\n"; flush(); }
function log_ok($msg)  { echo "[OK]  {$msg}\n"; flush(); }
function log_err($msg) { echo "[ERR] {$msg}\n"; flush(); }

log_msg("=== TEST IMPORT SPCR v2 ===");

// Config
$SOURCE_DIR   = __DIR__ . '/test_docs/A1/ALSA CITY BUS';
$STORAGE_PATH = __DIR__ . '/storage/app/private/spcr-import/A1';
$NOM_CAT      = 'ALSA CITY BUS';
$CREATED_BY   = 1; // master user id
$DRY_RUN      = false;

// Catégorie
$category = DB::table('categories')->where('name', $NOM_CAT)->first();
if (!$category) {
    log_err("Catégorie '{$NOM_CAT}' non trouvée. Lancez le seeder d'abord.");
    exit(1);
}
log_ok("Catégorie trouvée : {$NOM_CAT} (id={$category->id})");

// Vérifier le dossier source
if (!is_dir($SOURCE_DIR)) {
    log_err("Dossier source introuvable : {$SOURCE_DIR}");
    exit(1);
}

$files = array_filter(glob($SOURCE_DIR . '/*'), fn($f) => strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf');
$files = array_values($files);
log_msg("Fichiers PDF trouvés : " . count($files));

if (empty($files)) {
    log_err("Aucun PDF dans {$SOURCE_DIR}");
    exit(1);
}

// Créer le dossier storage
if (!$DRY_RUN && !is_dir($STORAGE_PATH)) {
    mkdir($STORAGE_PATH, 0755, true);
    log_msg("Dossier storage créé : {$STORAGE_PATH}");
}

// Calcul expire_at : date du jour + 10 ans
$expireAt = date('Y-m-d', strtotime('+10 years'));

$created = 0;
$skipped = 0;
$now = now();

foreach ($files as $sourcePath) {
    $nomFichier = basename($sourcePath);
    $title      = pathinfo($nomFichier, PATHINFO_FILENAME); // nom sans .pdf

    // Doublon : chercher par title + category_id
    $existing = DB::table('documents')
        ->where('title', $title)
        ->where('category_id', $category->id)
        ->whereNull('deleted_at')
        ->first();

    if ($existing) {
        log_msg("  [SKIP] Déjà en base : {$nomFichier}");
        $skipped++;
        continue;
    }

    $destPath       = $STORAGE_PATH . '/' . $nomFichier;
    $storageRelPath = 'spcr-import/A1/' . $nomFichier;

    if (!$DRY_RUN) {
        // Copie pour le test (on ne déplace pas les fichiers de test)
        copy($sourcePath, $destPath);

        // Insérer dans documents
        $docId = DB::table('documents')->insertGetId([
            'uid'        => Str::uuid()->toString(),
            'title'      => $title,
            'category_id'=> $category->id,
            'status'     => 'approved',
            'entry_type' => 'direct_archive',
            'created_by' => $CREATED_BY,
            'expire_at'  => $expireAt,
            'is_expired' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Insérer dans document_attachments
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
        log_ok("Importé : {$nomFichier} (doc_id={$docId})");
    } else {
        log_msg("[DRY] Serait importé : {$nomFichier}");
        $created++;
    }
}

log_msg("\n=== RÉSULTAT ===");
log_ok("Documents créés  : {$created}");
log_msg("Documents ignorés: {$skipped}");
log_msg("Durée : " . round(microtime(true) - LARAVEL_START, 2) . "s");
