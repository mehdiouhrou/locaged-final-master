<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PdfConversionService
{
    /** Extensions convertible to PDF for upload preview (Livewire temp path). */
    public const TEMP_UPLOAD_OFFICE_EXTENSIONS = ['doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];

    /**
     * Convert a document (Word/Excel) to PDF using LibreOffice
     * 
     * @param string $filePath Path to the original file in storage
     * @return string|null Path to the converted PDF, or null on failure
     */
    public function convertToPdf($filePath)
    {
        $absolutePath = Storage::disk('local')->path($filePath);
        
        if (!file_exists($absolutePath)) {
            Log::error('File not found for PDF conversion', ['path' => $filePath]);
            return null;
        }

        // Determine output PDF path
        $pathInfo = pathinfo($filePath);
        $pdfFileName = $pathInfo['filename'] . '.pdf';
        $outputDir = Storage::disk('local')->path($pathInfo['dirname']);
        $pdfPath = $pathInfo['dirname'] . '/' . $pdfFileName;
        $absolutePdfPath = $outputDir . '/' . $pdfFileName;

        // Check if PDF already exists AND is not empty/corrupted (a previous
        // failed conversion could have left a 0-byte file on disk).
        if (Storage::disk('local')->exists($pdfPath) && Storage::disk('local')->size($pdfPath) > 0) {
            return $pdfPath;
        }

        // Determine file type
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        try {
            // Try PhpSpreadsheet for Excel files first (doesn't require LibreOffice)
            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
                if ($this->convertExcelToPdfWithPhpSpreadsheet($absolutePath, $absolutePdfPath)) {
                    Log::info('PDF conversion successful (PhpSpreadsheet)', [
                        'original' => $filePath,
                        'pdf' => $pdfPath
                    ]);
                    return $pdfPath;
                }
            }
            
            // Fall back to LibreOffice for Word and Excel if PhpSpreadsheet fails
            if ($this->isLibreOfficeAvailable()) {
                // Generate a unique temporary directory for LibreOffice user profile
                // This prevents permission errors when www-data doesn't have a home directory
                $uniqueId = uniqid('lo_', true);
                $tempUserDir = sys_get_temp_dir() . '/LibreOffice_Conversion_' . $uniqueId;
                
                // LibreOffice command to convert to PDF
                // -env:UserInstallation: use a custom user profile location
                // --headless: run without GUI
                // --convert-to pdf: convert to PDF format
                // --outdir: output directory
                $process = new Process([
                    'soffice',
                    '-env:UserInstallation=file://' . $tempUserDir,
                    '--headless',
                    '--convert-to',
                    'pdf',
                    '--outdir',
                    $outputDir,
                    $absolutePath
                ]);

                $process->setTimeout(120); // 2 minutes timeout
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                // Verify the PDF was created
                if (file_exists($absolutePdfPath)) {
                    Log::info('PDF conversion successful (LibreOffice)', [
                        'original' => $filePath,
                        'pdf' => $pdfPath
                    ]);
                    return $pdfPath;
                } else {
                    Log::error('PDF file not created after conversion', ['expected_path' => $absolutePdfPath]);
                    return null;
                }
            } else {
                Log::warning('LibreOffice not available for PDF conversion', ['file' => $filePath]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('PDF conversion failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Convert Excel file to PDF using PhpSpreadsheet + mPDF
     * 
     * @param string $inputPath Absolute path to Excel file
     * @param string $outputPath Absolute path for PDF output
     * @return bool Success status
     */
    private function convertExcelToPdfWithPhpSpreadsheet($inputPath, $outputPath)
    {
        try {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                return false;
            }
            
            // Load spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputPath);

            // Configure page setup for each sheet: fit all columns on page width,
            // landscape orientation (Excel exports are usually wide with many columns).
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            }

            // Configure PDF writer
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class);
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
            
            // Save to PDF
            $writer->save($outputPath);
            
            return file_exists($outputPath);
            
        } catch (\Exception $e) {
            Log::error('PhpSpreadsheet PDF conversion failed', [
                'input' => $inputPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if LibreOffice is installed and available
     * 
     * @return bool
     */
    public function isLibreOfficeAvailable()
    {
        $process = new Process(['soffice', '--version']);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Convert a readable absolute path (e.g. Livewire temp upload) to a PDF under storage/app/tmp/pdf-preview.
     * Returns absolute path to the PDF, or null. The PDF is cached and reused across requests for the
     * same source file (keyed by a deterministic hash of path+size+mtime); cleanup is handled by the
     * documents:clean-pdf-preview-cache scheduled command, not by the caller.
     */
    public function convertOfficeAbsolutePathToPdf(string $absolutePath, string $extension): ?string
    {
        $extension = strtolower(ltrim($extension, '.'));
        if (! in_array($extension, self::TEMP_UPLOAD_OFFICE_EXTENSIONS, true)) {
            return null;
        }
        if (! is_readable($absolutePath)) {
            return null;
        }

        $tmpBase = storage_path('app/tmp/pdf-preview');
        if (! is_dir($tmpBase) && ! @mkdir($tmpBase, 0775, true)) {
            Log::error('Cannot create pdf-preview temp directory', ['dir' => $tmpBase]);

            return null;
        }

        $stat = @stat($absolutePath);
        $cacheSeed = $absolutePath.'|'.($stat['size'] ?? 0).'|'.($stat['mtime'] ?? 0);
        $id = hash('sha256', $cacheSeed);

        $expectedPdf = $tmpBase.DIRECTORY_SEPARATOR.$id.'.pdf';

        if (file_exists($expectedPdf) && filesize($expectedPdf) > 0) {
            return $expectedPdf;
        }

        $workInput = $tmpBase.DIRECTORY_SEPARATOR.$id.'.'.$extension;
        if (! @copy($absolutePath, $workInput)) {
            return null;
        }

        try {
            if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                if ($this->convertExcelToPdfWithPhpSpreadsheet($workInput, $expectedPdf) && file_exists($expectedPdf) && filesize($expectedPdf) > 0) {
                    @unlink($workInput);

                    return $expectedPdf;
                }
            }

            if (! $this->isLibreOfficeAvailable()) {
                @unlink($workInput);
                Log::warning('Office upload preview: LibreOffice (soffice) not available');

                return null;
            }

            $uniqueId = uniqid('lo_prev_', true);
            $tempUserDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'LibreOffice_Preview_'.$uniqueId;
            $process = new Process([
                'soffice',
                '-env:UserInstallation=file://'.$tempUserDir,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $tmpBase,
                $workInput,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (is_dir($tempUserDir)) {
                try {
                    File::deleteDirectory($tempUserDir);
                } catch (\Throwable) {
                    //
                }
            }

            @unlink($workInput);

            if (! file_exists($expectedPdf) || filesize($expectedPdf) === 0) {
                if (file_exists($expectedPdf)) {
                    @unlink($expectedPdf);
                }
                Log::warning('Office upload preview: LibreOffice did not produce PDF', [
                    'stderr' => $process->getErrorOutput(),
                ]);

                return null;
            }

            return $expectedPdf;
        } catch (\Throwable $e) {
            @unlink($workInput);
            if (file_exists($expectedPdf)) {
                @unlink($expectedPdf);
            }
            Log::error('Office upload preview conversion failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
