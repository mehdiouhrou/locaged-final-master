<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ReversibilityExportService
{
    /**
     * Generate a reversibility ZIP (files/ + metadonnees.csv) for the given documents.
     * Returns the absolute path to the generated ZIP file.
     *
     * @param  Collection<int, Document>  $documents
     */
    public function generate(Collection $documents): string
    {
        $zipFileName = 'export-reversibilite-' . now()->format('Ymd_His') . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;
        $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. Fichiers
        foreach ($documents as $doc) {
            $version = $doc->latestVersion;
            if ($version && Storage::exists($version->file_path)) {
                $zip->addFile(
                    Storage::path($version->file_path),
                    'fichiers/' . $doc->id . '_' . basename($version->file_path)
                );
            }
        }

        // 2. CSV métadonnées
        $csvPath = storage_path('app/public/metadonnees-' . now()->timestamp . '.csv');
        $handle = fopen($csvPath, 'w');

        // BOM UTF-8 pour compatibilité Excel
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'ID', 'UID', 'Titre', 'Statut', 'Categorie', 'Sous-categorie',
            'Date de creation', 'Date d\'expiration',
            'Cree par', 'Empreinte SHA-256', 'Type de fichier', 'Taille (octets)',
            'Numero de boite', 'Nom de boite', 'Emplacement physique', 'Tags',
        ], ';', '"', '\\');

        foreach ($documents as $doc) {
            $version = $doc->latestVersion;
            $fileSize = null;
            $fileType = null;
            if ($version && Storage::exists($version->file_path)) {
                $fileSize = Storage::size($version->file_path);
                $fileType = pathinfo($version->file_path, PATHINFO_EXTENSION);
            }

            $tags = $doc->tags?->pluck('name')->filter()->implode(', ') ?? '';

            $boxPath = '';
            if ($doc->box) {
                $doc->box->loadMissing('shelf.row.room');
                $boxPath = $doc->box->__toString();
            }

            fputcsv($handle, [
                $doc->id,
                $doc->uid,
                $doc->title,
                $doc->status,
                $doc->category?->name ?? '',
                $doc->subcategory?->name ?? '',
                optional($doc->created_at)->format('d/m/Y H:i'),
                optional($doc->expire_at)->format('d/m/Y'),
                $doc->createdBy?->full_name ?? '',
                $doc->file_hash ?? '',
                $fileType ?? '',
                $fileSize ?? '',
                $doc->box?->box_number ?? '',
                $doc->boxFolder?->name ?? '',
                $boxPath,
                $tags,
            ], ';', '"', '\\');
        }

        fclose($handle);

        $zip->addFile($csvPath, 'metadonnees.csv');
        $zip->close();

        // Nettoyage du CSV temporaire (déjà copié dans le ZIP)
        @unlink($csvPath);

        return $zipFilePath;
    }
}
