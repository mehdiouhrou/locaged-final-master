<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    /**
     * Generate and stream a PDF export.
     *
     * @param string $title      Titre du rapport
     * @param array  $headings   En-têtes des colonnes
     * @param array  $rows       Lignes de données (tableau de tableaux)
     * @param string $filename   Nom du fichier sans extension
     * @param array  $meta       Lignes de meta (ex: filtres, total) affichées sous le titre
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(
        string $title,
        array $headings,
        array $rows,
        string $filename,
        array $meta = []
    ) {
        $logoPath = public_path('assets/template/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $generatedAt = now()->format('d/m/Y à H:i');
        $totalRows   = count($rows);

        $html = $this->buildHtml(
            $title,
            $headings,
            $rows,
            $logoBase64,
            $generatedAt,
            $totalRows,
            $meta
        );

        $tmpDir = storage_path('app/tmp/mpdf');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        try {
            $mpdf = new Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4-L',
                'tempDir'       => $tmpDir,
                'margin_left'   => 12,
                'margin_right'  => 12,
                'margin_top'    => 14,
                'margin_bottom' => 14,
            ]);
            $mpdf->SetTitle($title);
            $mpdf->WriteHTML($html);

            $pdfContent = $mpdf->Output('', 'S');

            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
            ]);
        } catch (MpdfException|\Throwable $e) {
            report($e);
            abort(500, 'Erreur lors de la génération du PDF.');
        }
    }

    private function buildHtml(
        string $title,
        array $headings,
        array $rows,
        string $logoBase64,
        string $generatedAt,
        int $totalRows,
        array $meta
    ): string {
        $colCount  = count($headings);
        $thWidth   = $colCount > 0 ? round(100 / $colCount, 2) : 100;

        $headingsHtml = '';
        foreach ($headings as $h) {
            $headingsHtml .= '<th style="width:' . $thWidth . '%;">' . htmlspecialchars((string) $h) . '</th>';
        }

        $rowsHtml = '';
        foreach ($rows as $i => $row) {
            $bg       = $i % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $rowsHtml .= '<tr style="background:' . $bg . ';">';
            foreach ((array) $row as $cell) {
                $rowsHtml .= '<td>' . htmlspecialchars((string) ($cell ?? '')) . '</td>';
            }
            $rowsHtml .= '</tr>';
        }

        $metaHtml = '';
        foreach ($meta as $line) {
            $metaHtml .= '<p style="margin:2px 0;font-size:10px;color:#555;">' . htmlspecialchars((string) $line) . '</p>';
        }

        $logoHtml = $logoBase64
            ? '<img src="' . $logoBase64 . '" style="height:36px;" alt="Logo">'
            : '<span style="font-weight:700;font-size:16px;color:#C41E3A;">LocaGed</span>';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #C41E3A; padding-bottom: 10px; margin-bottom: 14px; }
    .header-right { text-align: right; font-size: 10px; color: #C41E3A; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    h1 { font-size: 16px; color: #1a1a1a; margin: 0 0 6px 0; }
    .meta { margin-bottom: 12px; }
    .meta-line { font-size: 10px; color: #555; margin: 2px 0; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead tr { background: #C41E3A; color: #ffffff; }
    thead th { padding: 7px 6px; text-align: left; font-weight: 700; border: 1px solid #a01020; }
    tbody td { padding: 6px; border: 1px solid #e0e0e0; vertical-align: top; }
    .footer { margin-top: 14px; font-size: 9px; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 6px; }
</style>
</head>
<body>
    <div class="header">
        <div>{$logoHtml}</div>
        <div class="header-right">Gestion Électronique de Documents</div>
    </div>

    <h1>{$title}</h1>

    <div class="meta">
        <p class="meta-line">Généré le : {$generatedAt} &nbsp;|&nbsp; Total : {$totalRows} enregistrement(s)</p>
        {$metaHtml}
    </div>

    <table>
        <thead>
            <tr>{$headingsHtml}</tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <div class="footer">
        &copy; {$generatedAt} &mdash; Ce document a été généré automatiquement par LocaGed. Ne pas diffuser sans autorisation.
    </div>
</body>
</html>
HTML;
    }
}
