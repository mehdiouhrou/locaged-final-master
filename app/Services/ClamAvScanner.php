<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClamAvScanner
{
    /**
     * @throws RuntimeException si infecté ou si le scan échoue alors que ClamAV est activé
     */
    public function assertClean(string $absolutePath): void
    {
        if (! config('clamav.enabled')) {
            return;
        }

        if (! is_readable($absolutePath)) {
            throw new RuntimeException(__('Fichier illisible pour analyse antivirus.'));
        }

        $binary = (string) config('clamav.binary', 'clamscan');
        $cmd = escapeshellcmd($binary).' --no-summary '.escapeshellarg($absolutePath);
        $output = [];
        $code = 0;
        @exec($cmd.' 2>&1', $output, $code);

        if ($code === 1) {
            Log::warning('ClamAV detected infected upload', ['path' => $absolutePath, 'output' => $output]);

            throw new RuntimeException(__('Fichier rejeté : analyse antivirus positive.'));
        }

        if ($code !== 0) {
            Log::warning('ClamAV scan failed', ['path' => $absolutePath, 'exit' => $code, 'output' => $output]);

            throw new RuntimeException(__('Analyse antivirus indisponible ou en erreur. Réessayez plus tard.'));
        }
    }
}
