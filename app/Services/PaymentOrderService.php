<?php

namespace App\Services;

use App\Models\Document;
use App\Support\Branding;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class PaymentOrderService
{
    /**
     * Generate a payment order PDF for an approved payment document.
     * Returns the storage path of the generated file.
     */
    public function generate(Document $document): string
    {
        $document->loadMissing(['department', 'service', 'category', 'approvals.approver']);

        $manifest = [
            'document_id'    => $document->id,
            'document_uid'   => $document->uid,
            'title'          => $document->title,
            'amount'         => $document->amount,
            'amount_words'   => $document->amount !== null ? $this->numberToWords((float) $document->amount) : '—',
            'supplier'       => data_get($document->metadata, 'supplier'),
            'account_number' => data_get($document->metadata, 'account_number'),
            'reason'         => data_get($document->metadata, 'reason'),
            'generated_at'   => now()->toIso8601String(),
        ];

        $relativePath = 'payment-orders/' . $document->id . '-' . now()->timestamp . '.pdf';

        $html = view('pdf.payment-order', [
            'document'          => $document,
            'manifest'          => $manifest,
            'clientLogoDataUri' => Branding::clientLogoDataUriForPdf(),
        ])->render();

        $tmpDir = storage_path('app/tmp/mpdf');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        try {
            $mpdf = new Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'tempDir'       => $tmpDir,
                'margin_left'   => 14,
                'margin_right'  => 14,
                'margin_top'    => 16,
                'margin_bottom' => 16,
            ]);
            $mpdf->SetTitle('Ordre de Virement');
            $mpdf->WriteHTML($html);
            $binary = $mpdf->Output('', 'S');
            Storage::disk('private')->put($relativePath, $binary);
        } catch (MpdfException|\Throwable $e) {
            report($e);
        }

        return $relativePath;
    }

    private function numberToWords(float $amount): string
    {
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
                  'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
                  'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens  = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante',
                  'soixante', 'quatre-vingt', 'quatre-vingt'];

        $intPart  = (int) floor($amount);
        $centimes = (int) round(($amount - $intPart) * 100);

        $words = trim($this->convertInteger($intPart, $units, $tens));
        $result = $words . ' dirhams';

        if ($centimes > 0) {
            $centWords = trim($this->convertInteger($centimes, $units, $tens));
            $result .= ' et ' . $centWords . ' centime' . ($centimes > 1 ? 's' : '');
        }

        return $result;
    }

    /** @param array<int,string> $units @param array<int,string> $tens */
    private function convertInteger(int $n, array $units, array $tens): string
    {
        if ($n === 0) {
            return 'zéro';
        }

        $result = '';

        if ($n >= 1_000_000) {
            $millions = (int) floor($n / 1_000_000);
            $result .= ($millions === 1 ? 'un million' : $this->convertInteger($millions, $units, $tens) . ' millions') . ' ';
            $n %= 1_000_000;
        }

        if ($n >= 1_000) {
            $thousands = (int) floor($n / 1_000);
            $result .= ($thousands === 1 ? 'mille' : $this->convertInteger($thousands, $units, $tens) . ' mille') . ' ';
            $n %= 1_000;
        }

        if ($n >= 100) {
            $hundreds = (int) floor($n / 100);
            $result .= ($hundreds === 1 ? 'cent' : $units[$hundreds] . ' cent') . ' ';
            $n %= 100;
        }

        if ($n >= 20) {
            $tenDigit  = (int) floor($n / 10);
            $unitDigit = $n % 10;

            if ($tenDigit === 7 || $tenDigit === 9) {
                $combined = ($tenDigit === 7 ? 60 : 80) + $unitDigit + 10;
                if ($combined === 71 || $combined === 91) {
                    $result .= $tens[$tenDigit] . '-et-' . $units[$combined - ($tenDigit === 7 ? 60 : 80)] . ' ';
                } else {
                    $result .= $tens[$tenDigit] . '-' . $units[$combined - ($tenDigit === 7 ? 60 : 80)] . ' ';
                }
            } elseif ($unitDigit === 1 && $tenDigit !== 8) {
                $result .= $tens[$tenDigit] . ' et un ';
            } elseif ($unitDigit === 0) {
                $result .= $tens[$tenDigit] . ' ';
            } else {
                $result .= $tens[$tenDigit] . '-' . $units[$unitDigit] . ' ';
            }
        } elseif ($n > 0) {
            $result .= $units[$n] . ' ';
        }

        return rtrim($result);
    }
}
