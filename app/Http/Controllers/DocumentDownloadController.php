<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Gate;

class DocumentDownloadController extends Controller
{
    public function __invoke(int $id)
    {
        $document = Document::with('latestVersion')->findOrFail($id);
        Gate::authorize('download', $document);

        if (! $document->latestVersion) {
            return back()->with('error', 'No document version found.');
        }

        return DocumentVersionController::downloadFile($document->latestVersion->id);
    }
}
