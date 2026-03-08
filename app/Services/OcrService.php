<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    // Whitelist supported extensions (images, PDFs, and common Office/text formats)
    const SUPPORTED_EXTENSIONS = [
        'pdf',
        'jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'webp',
        'docx',
        'xls', 'xlsx', 'csv',
        'txt',
    ];

    public function extractText(string $absoluteFilePath): string
    {
        $extension = strtolower(pathinfo($absoluteFilePath, PATHINFO_EXTENSION));

        try {
            // Check if extension is supported
            if (!in_array($extension, self::SUPPORTED_EXTENSIONS)) {
                Log::info("Skipping OCR for unsupported file type: {$extension} ({$absoluteFilePath})");
                return ''; // Return empty string for non-OCR files
            }

            if ($extension === 'pdf') {
                // Convert all PDF pages to images and extract text from each page
                return $this->extractTextFromPdf($absoluteFilePath);
            }

            // Image formats → Tesseract OCR with optimization flags
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'webp'], true)) {
                return (new TesseractOCR($absoluteFilePath))
                    ->executable('C:\\Program Files\\Tesseract-OCR\\tesseract.exe')
                    ->lang('fra','ara','eng')
                    ->psm(6)
                    ->oem(1)
                    ->run();
}
            // Docx → structured text extraction (no image OCR needed)
            if ($extension === 'docx') {
                return $this->extractTextFromDocx($absoluteFilePath);
            }

            // Plain text / CSV → direct text read
            if (in_array($extension, ['txt', 'csv'], true)) {
                return $this->extractTextFromTextFile($absoluteFilePath);
            }

            // Excel → read first sheet into a flattened text representation
            if (in_array($extension, ['xls', 'xlsx'], true)) {
                return $this->extractTextFromExcel($absoluteFilePath);
            }

            // Fallback for any other "supported" type
            return '';
        } catch (Exception $e) {
            Log::error("OCR extraction failed for file {$absoluteFilePath}: " . $e->getMessage());
            throw $e; // rethrow so caller knows
        }
    }

    private function extractTextFromPdf(string $pdfPath): string
    {
        if (!file_exists($pdfPath)) {
            $msg = "File does not exist: {$pdfPath}";
            Log::error($msg);
            throw new Exception($msg);
        }

        if (!is_readable($pdfPath)) {
            $msg = "File is not readable: {$pdfPath}";
            Log::error($msg);
            throw new Exception($msg);
        }

        try {
            // First, determine page count and setup resolution
            $tempImagickForPages = new \Imagick();
            $tempImagickForPages->readImage($pdfPath);
            $totalPages = $tempImagickForPages->getNumberImages();
            $tempImagickForPages->clear();
            $tempImagickForPages->destroy();

            // 300 DPI optimal pour scans de documents administratifs imprimés
            $resolution = 300; 

        } catch (Exception $e) {
            Log::error("Failed to load PDF {$pdfPath} or determine page count. File might be corrupted or encrypted: " . $e->getMessage());
            // If we can't even read the PDF (e.g. encrypted), we can't OCR it.
            // We return empty string instead of throwing to avoid crashing the queue repeatedly for a bad file.
            return ''; 
        }

        $fullText = array_fill(0, $totalPages, '');
        $batchSize = 2; // 2 parallel processes - balance speed vs memory
        $tmpDir = storage_path('app/tmp/');

        for ($batchStart = 0; $batchStart < $totalPages; $batchStart += $batchSize) {
            $batchEnd = min($batchStart + $batchSize, $totalPages);
            $processes = [];

            // Step 1: Convert pages to images + launch Tesseract processes in parallel
            for ($pageIndex = $batchStart; $pageIndex < $batchEnd; $pageIndex++) {
                $uuid = \Str::uuid();
                $imagePath = $tmpDir . $uuid . "_page{$pageIndex}.png";
                $ocrOutputBase = $tmpDir . $uuid . "_ocr{$pageIndex}";
                $ocrOutputFile = $ocrOutputBase . '.txt';
                $pageImagick = null;

                try {
                    if (!file_exists($tmpDir)) {
                        mkdir($tmpDir, 0755, true);
                    }

                    $pageImagick = new \Imagick();
                    $pageImagick->setResolution($resolution, $resolution);
                    $pageImagick->readImage($pdfPath . '[' . $pageIndex . ']');
                    $pageImagick->setImageFormat('png');

                    try {
                        $currentColorspace = $pageImagick->getImageColorspace();
                        if ($currentColorspace !== \Imagick::COLORSPACE_GRAY) {
                            if ($currentColorspace === \Imagick::COLORSPACE_CMYK) {
                                $pageImagick->transformImageColorspace(\Imagick::COLORSPACE_RGB);
                            }
                            $pageImagick->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                        }
                    } catch (\ImagickException $e) {
                        Log::warning("Grayscale failed page {$pageIndex}: " . $e->getMessage());
                    }

                    try { $pageImagick->normalizeImage(); } catch (\ImagickException $e) {}
                    try { $pageImagick->deskewImage(0.4 * \Imagick::getQuantum()); } catch (\ImagickException $e) {}
                    try { $pageImagick->sharpenImage(0, 1.0); } catch (\ImagickException $e) {}

                    $pageImagick->writeImage($imagePath);

                    // Launch Tesseract asynchronously
                    $tesseract = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
                    $cmd = "\"{$tesseract}\" \"{$imagePath}\" \"{$ocrOutputBase}\" -l fra+ara+eng --psm 6 --oem 1";
                    $process = proc_open($cmd, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);

                    $processes[$pageIndex] = [
                        'process'       => $process,
                        'pipes'         => $pipes,
                        'imagePath'     => $imagePath,
                        'ocrOutputFile' => $ocrOutputFile,
                    ];

                } catch (Exception $e) {
                    Log::error("Failed to prepare page {$pageIndex}: " . $e->getMessage());
                } finally {
                    if ($pageImagick) { $pageImagick->clear(); $pageImagick->destroy(); }
                }
            }

            // Step 2: Wait for all processes in batch and collect results
            foreach ($processes as $pageIndex => $info) {
                try {
                    proc_close($info['process']);
                    if (file_exists($info['ocrOutputFile'])) {
                        $fullText[$pageIndex] = file_get_contents($info['ocrOutputFile']);
                        unlink($info['ocrOutputFile']);
                    }
                } catch (Exception $e) {
                    Log::error("Failed to collect OCR result page {$pageIndex}: " . $e->getMessage());
                } finally {
                    if (file_exists($info['imagePath'])) unlink($info['imagePath']);
                }
            }

            if ($totalPages > 10) {
                Log::info("OCR Progress: page " . $batchEnd . " of {$totalPages}");
            }
        }

        return trim(implode("\n", $fullText));
    }

    private function extractTextFromDocx(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            Log::error("Docx file not accessible for OCR: {$path}");
            return '';
        }

        $lower = strtolower($path);
        if (!str_ends_with($lower, '.docx') || !class_exists('ZipArchive')) {
            Log::info("Skipping non-docx Office file for text extraction: {$path}");
            return '';
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) {
                return '';
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                return '';
            }

            // Convert paragraph and tab markers into simple text breaks
            $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
            $xml = preg_replace('/<w:tab[^>]*>/', "\t", $xml);
            $text = strip_tags($xml);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

            return trim($text);
        } catch (Exception $e) {
            Log::error("Failed to extract text from DOCX for OCR: {$path} - " . $e->getMessage());
            return '';
        }
    }

    private function extractTextFromTextFile(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            Log::error("Text file not accessible for OCR: {$path}");
            return '';
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        return trim($contents);
    }

    private function extractTextFromExcel(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            Log::error("Excel file not accessible for OCR: {$path}");
            return '';
        }

        try {
            // Lazy-load dependency to avoid hard failure if PhpSpreadsheet is missing
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                Log::warning('PhpSpreadsheet not available; skipping Excel OCR extraction.');
                return '';
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            // Read a reasonable area (e.g. A1:Z100) to avoid huge memory usage
            $rows = $sheet->rangeToArray('A1:Z100', null, true, true, true);
            $lines = [];
            foreach ($rows as $row) {
                $cells = array_filter(array_map('trim', array_values($row)), fn($v) => $v !== null && $v !== '');
                if (!empty($cells)) {
                    $lines[] = implode(' ', $cells);
                }
            }

            return trim(implode("\n", $lines));
        } catch (Exception $e) {
            Log::error("Failed to extract text from Excel for OCR: {$path} - " . $e->getMessage());
            return '';
        }
    }
}
