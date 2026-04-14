<?php

namespace App\Services;

use App\Models\DestructionCertificate;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DestructionProofService
{
    /**
     * @return array<string, mixed>
     */
    public function buildSignedProofPackage(DestructionCertificate $certificate, User $actor): array
    {
        if (! $certificate->pdf_path || ! Storage::disk('private')->exists($certificate->pdf_path)) {
            throw new \RuntimeException('Destruction certificate PDF not found.');
        }

        $pdfBinary = Storage::disk('private')->get($certificate->pdf_path);
        $pdfSha256 = hash('sha256', $pdfBinary);

        $certificate->loadMissing(['document', 'destructionRequest', 'approvedByUser']);

        $manifest = [
            'spec_version' => 'destruction-proof-v1',
            'generated_at' => now()->toIso8601String(),
            'certificate' => [
                'public_id' => $certificate->public_id,
                'id' => $certificate->id,
            ],
            'generated_by' => [
                'id' => $actor->id,
                'name' => $actor->full_name,
                'email' => $actor->email,
            ],
            'document' => [
                'id' => $certificate->document_id,
                'uid' => $certificate->document?->uid,
                'title' => $certificate->document?->title,
            ],
            'destruction_request' => [
                'id' => $certificate->document_destruction_request_id,
                'status' => $certificate->destructionRequest?->status,
            ],
            'pdf' => [
                'path' => $certificate->pdf_path,
                'sha256' => $pdfSha256,
                'size_bytes' => strlen($pdfBinary),
            ],
            'records_hash_sha256' => hash('sha256', json_encode([
                'certificate_manifest' => $certificate->manifest,
                'pdf_sha256' => $pdfSha256,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'app' => [
                'name' => (string) config('app.name'),
                'env' => (string) config('app.env'),
                'url' => (string) config('app.url'),
            ],
        ];

        $signature = $this->signManifest($manifest);

        $archiveName = 'destruction-proof-' . $certificate->public_id . '-' . now()->format('Ymd_His') . '-' . Str::random(6) . '.zip';
        $localRelativePath = 'destruction-evidence/' . $archiveName;
        $localAbsolutePath = storage_path('app/private/' . $localRelativePath);
        $this->ensureDirectory(dirname($localAbsolutePath));

        $this->createZipPackage(
            $localAbsolutePath,
            $manifest,
            $signature,
            $pdfBinary
        );

        $packageSha256 = hash_file('sha256', $localAbsolutePath) ?: null;
        $archiveResult = $this->archiveImmutably($localRelativePath, $archiveName, $manifest, $signature);

        $certificate->update([
            'proof_package_path' => $localRelativePath,
            'proof_pdf_sha256' => $pdfSha256,
            'proof_package_sha256' => $packageSha256,
            'proof_manifest' => $manifest,
            'proof_signature' => $signature,
            'proof_archive' => $archiveResult,
            'proof_generated_at' => now(),
        ]);

        return [
            'certificate_id' => $certificate->id,
            'package_path' => $localRelativePath,
            'package_sha256' => $packageSha256,
            'pdf_sha256' => $pdfSha256,
            'signature' => $signature,
            'archive' => $archiveResult,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyProof(DestructionCertificate $certificate): array
    {
        $manifest = is_array($certificate->proof_manifest) ? $certificate->proof_manifest : [];
        $signature = is_array($certificate->proof_signature) ? $certificate->proof_signature : [];
        $archive = is_array($certificate->proof_archive) ? $certificate->proof_archive : [];

        $pdfExists = (bool) ($certificate->pdf_path && Storage::disk('private')->exists($certificate->pdf_path));
        $packageExists = (bool) ($certificate->proof_package_path && Storage::disk('private')->exists($certificate->proof_package_path));

        $currentPdfHash = $pdfExists ? hash('sha256', Storage::disk('private')->get($certificate->pdf_path)) : null;
        $manifestPdfHash = data_get($manifest, 'pdf.sha256');
        $pdfHashMatches = $currentPdfHash && $manifestPdfHash ? hash_equals((string) $manifestPdfHash, (string) $currentPdfHash) : false;

        $currentPackageHash = $packageExists
            ? hash_file('sha256', storage_path('app/private/' . $certificate->proof_package_path))
            : null;
        $storedPackageHash = $certificate->proof_package_sha256;
        $packageHashMatches = $currentPackageHash && $storedPackageHash
            ? hash_equals((string) $storedPackageHash, (string) $currentPackageHash)
            : false;

        $signatureStatus = $this->verifyManifestSignature($manifest, $signature);

        return [
            'pdf_exists' => $pdfExists,
            'package_exists' => $packageExists,
            'current_pdf_hash' => $currentPdfHash,
            'manifest_pdf_hash' => $manifestPdfHash,
            'pdf_hash_matches' => $pdfHashMatches,
            'current_package_hash' => $currentPackageHash,
            'stored_package_hash' => $storedPackageHash,
            'package_hash_matches' => $packageHashMatches,
            'signature_status' => $signatureStatus,
            'immutable_archive' => $archive,
        ];
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, string|null>
     */
    private function signManifest(array $manifest): array
    {
        $payload = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $privateKey = (string) config('audit.evidence.private_key', '');
        if ($privateKey !== '') {
            $signature = null;
            $ok = openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            if ($ok && is_string($signature)) {
                return [
                    'algorithm' => 'rsa-sha256',
                    'encoding' => 'base64',
                    'value' => base64_encode($signature),
                    'public_key_fingerprint' => hash('sha256', (string) config('audit.evidence.public_key', '')),
                ];
            }
        }

        $secret = (string) config('audit.hmac_key', '');
        if ($secret === '') {
            $secret = (string) config('app.key', '');
        }

        return [
            'algorithm' => 'hmac-sha256',
            'encoding' => 'hex',
            'value' => hash_hmac('sha256', $payload, $secret),
            'public_key_fingerprint' => null,
        ];
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, string|null> $signature
     */
    private function createZipPackage(
        string $zipPath,
        array $manifest,
        array $signature,
        string $pdfBinary
    ): void {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString(
            'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
        $zip->addFromString(
            'signature.json',
            json_encode($signature, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
        $zip->addFromString('certificate.pdf', $pdfBinary);
        $zip->addFromString('README.txt', $this->buildReadme());

        $zip->close();
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, string|null> $signature
     * @return array<string, mixed>
     */
    private function archiveImmutably(
        string $localRelativePath,
        string $archiveName,
        array $manifest,
        array $signature
    ): array {
        $enabled = (bool) config('audit.evidence.worm_enabled', false);
        if (! $enabled) {
            return [
                'enabled' => false,
                'disk' => null,
                'path' => null,
                'status' => 'skipped',
                'message' => 'WORM archive disabled by configuration.',
            ];
        }

        $disk = (string) config('audit.evidence.worm_disk', 's3');
        $years = max((int) config('audit.evidence.worm_retention_years', 10), 1);
        $mode = strtoupper((string) config('audit.evidence.worm_mode', 'COMPLIANCE'));
        $remotePath = trim((string) config('audit.evidence.worm_prefix', 'audit-evidence/'), '/') . '/destruction/' . $archiveName;

        $content = Storage::disk('private')->get($localRelativePath);
        $retainUntil = now()->addYears($years)->toIso8601String();

        try {
            Storage::disk($disk)->put($remotePath, $content, [
                'visibility' => 'private',
                'Metadata' => [
                    'evidence-spec' => 'destruction-proof-v1',
                    'records-hash' => (string) ($manifest['records_hash_sha256'] ?? ''),
                    'signature-algo' => (string) ($signature['algorithm'] ?? ''),
                ],
                'ObjectLockMode' => $mode,
                'ObjectLockRetainUntilDate' => $retainUntil,
            ]);

            return [
                'enabled' => true,
                'disk' => $disk,
                'path' => $remotePath,
                'status' => 'archived',
                'mode' => $mode,
                'retain_until' => $retainUntil,
            ];
        } catch (\Throwable $e) {
            return [
                'enabled' => true,
                'disk' => $disk,
                'path' => $remotePath,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function buildReadme(): string
    {
        return <<<TXT
Destruction Legal Proof Package (v1)

Files:
- manifest.json: certificate and PDF hashes
- signature.json: signature over manifest.json
- certificate.pdf: destruction report (PV)

Verification:
1) Verify signature.json against manifest.json.
2) Compare SHA-256 of certificate.pdf with manifest.pdf.sha256.
3) Confirm immutable archive status and retention date.
TXT;
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $signature
     * @return array<string, mixed>
     */
    private function verifyManifestSignature(array $manifest, array $signature): array
    {
        $payload = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $algorithm = (string) ($signature['algorithm'] ?? '');
        $value = (string) ($signature['value'] ?? '');

        if ($algorithm === 'rsa-sha256') {
            $publicKey = (string) config('audit.evidence.public_key', '');
            if ($publicKey === '') {
                return ['status' => 'unknown', 'message' => 'Missing public key for RSA verification.'];
            }

            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                return ['status' => 'invalid', 'message' => 'Invalid RSA signature encoding.'];
            }

            $ok = openssl_verify($payload, $decoded, $publicKey, OPENSSL_ALGO_SHA256);
            if ($ok === 1) {
                return ['status' => 'valid', 'message' => 'RSA signature verified.'];
            }

            return ['status' => 'invalid', 'message' => 'RSA signature verification failed.'];
        }

        if ($algorithm === 'hmac-sha256') {
            $secret = (string) config('audit.hmac_key', '');
            if ($secret === '') {
                $secret = (string) config('app.key', '');
            }

            $expected = hash_hmac('sha256', $payload, $secret);
            $valid = hash_equals($expected, $value);

            return [
                'status' => $valid ? 'valid' : 'invalid',
                'message' => $valid ? 'HMAC signature verified.' : 'HMAC signature mismatch.',
            ];
        }

        return ['status' => 'unknown', 'message' => 'Unknown signature algorithm.'];
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d\TH:i:s.uP');
        }
        if ($date === null) {
            return null;
        }

        return (string) $date;
    }
}
