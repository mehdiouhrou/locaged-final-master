<?php

namespace App\Http\Controllers;

use App\Models\DocumentAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentAttachmentDownloadController extends Controller
{
    public function __invoke(int $id)
    {
        $attachment = DocumentAttachment::with('document')->findOrFail($id);
        $document = $attachment->document;

        if (! $document) {
            abort(404, 'Document not found.');
        }

        Gate::authorize('download', $document);

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->label ?: basename($attachment->file_path)
        );
    }
}
