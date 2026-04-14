<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FilePreviewController extends Controller
{
    /**
     * Stream a temporary uploaded file inline for preview (images, PDFs).
     * The token is an encrypted absolute path to the file on disk.
     */
    public function temp(Request $request, string $token): BinaryFileResponse
    {
        $path = Crypt::decryptString($token);

        if (! file_exists($path)) {
            abort(404, 'File not found.');
        }

        // Whitelist: only allow previewing Livewire temporary uploads.
        // Default disk is often storage/app/private (see config/filesystems.php), so include that path.
        $allowedRoots = array_values(array_unique(array_filter([
            storage_path('framework/livewire-tmp'),
            storage_path('app/livewire-tmp'),
            storage_path('app/private/livewire-tmp'),
            // Resolved root of the disk Livewire uses for temp uploads (disk null => default)
            (function () {
                $diskName = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default', 'local');
                $root = config("filesystems.disks.{$diskName}.root");

                return is_string($root) && $root !== ''
                    ? rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'livewire-tmp'
                    : null;
            })(),
        ])));

        $realPath = realpath($path) ?: $path;
        $isAllowed = false;
        foreach ($allowedRoots as $root) {
            $rootReal = realpath($root);
            if ($rootReal === false) {
                continue;
            }
            if (str_starts_with($realPath, $rootReal)) {
                $isAllowed = true;
                break;
            }
        }
        if (! $isAllowed) {
            abort(403);
        }

        $mime = File::mimeType($realPath) ?: 'application/octet-stream';
        $name = $request->string('name')->toString() ?: basename($realPath);

        // Force inline rendering with correct content-type
        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($name).'"',
        ];

        return response()->file($realPath, $headers);
    }
}
