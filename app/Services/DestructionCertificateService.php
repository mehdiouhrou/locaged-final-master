<?php

namespace App\Services;

use App\Models\DestructionCertificate;
use App\Models\DocumentDestructionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class DestructionCertificateService
{
    /**
     * After a destruction request is approved, persist a PV (PDF) and manifest for archival (30-year trace).
     */
    public function issueForApproval(DocumentDestructionRequest $request, User $approvedBy): ?DestructionCertificate
    {
        $document = $request->document;
        if (! $document) {
            return null;
        }

        $document->loadMissing(['department', 'service', 'latestVersion']);

        $publicId = (string) Str::uuid();
        $manifest = [
            'document_id' => $document->id,
            'document_uid' => $document->uid,
            'title' => $document->title,
            'file_hash' => $document->file_hash,
            'department' => $document->department?->name,
            'service' => $document->service?->name,
            'destroyed_at' => now()->toIso8601String(),
            'approved_by' => $approvedBy->full_name ?? $approvedBy->name,
            'retention_note' => 'Conserver ce procès-verbal et les métadonnées associées conformément à la politique de conservation (réf. cycle de vie / archivage).',
        ];

        $certificate = DestructionCertificate::create([
            'public_id' => $publicId,
            'document_id' => $document->id,
            'document_destruction_request_id' => $request->id,
            'approved_by' => $approvedBy->id,
            'manifest' => $manifest,
        ]);

        $relativePath = 'destruction-certificates/'.$publicId.'.pdf';

        try {
            $html = view('pdf.destruction-certificate', [
                'certificate' => $certificate,
                'document' => $document,
                'manifest' => $manifest,
            ])->render();

            $tmpDir = storage_path('app/tmp/mpdf');
            if (! is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'tempDir' => $tmpDir,
                'margin_left' => 14,
                'margin_right' => 14,
                'margin_top' => 16,
                'margin_bottom' => 16,
            ]);
            $mpdf->SetTitle('Procès-verbal de destruction');
            $mpdf->WriteHTML($html);
            $binary = $mpdf->Output('', 'S');
            Storage::disk('private')->put($relativePath, $binary);
            $certificate->update(['pdf_path' => $relativePath]);

            // Build signed legal proof package and push immutable archive (WORM) when enabled.
            app(DestructionProofService::class)->buildSignedProofPackage($certificate->fresh(), $approvedBy);
        } catch (MpdfException|\Throwable $e) {
            report($e);
        }

        return $certificate->fresh();
    }
}
