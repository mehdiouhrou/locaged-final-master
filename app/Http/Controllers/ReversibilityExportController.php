<?php

namespace App\Http\Controllers;

use App\Exports\DocumentsReportExport;
use App\Models\Document;
use App\Services\ReversibilityExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReversibilityExportController extends Controller
{
    public function export(Request $request, ReversibilityExportService $service)
    {
        Gate::authorize('viewAny', Document::class);

        $reportExport = new DocumentsReportExport($request);
        $documents = $reportExport->baseQuery()
            ->with(['category', 'subcategory', 'department', 'service.subDepartment.department', 'box', 'boxFolder', 'createdBy', 'latestVersion', 'tags'])
            ->get();

        if ($documents->isEmpty()) {
            return back()->withErrors(['error' => 'Aucun document ne correspond à ces critères.']);
        }

        \App\Services\AuditService::logSubject(
            action: 'export_requested',
            subjectType: 'reversibility_export',
            metadata: [
                'document_count' => $documents->count(),
                'scope' => 'filtered',
            ],
        );

        $zipPath = $service->generate($documents);

        \App\Services\AuditService::logSubject(
            action: 'export_downloaded',
            subjectType: 'reversibility_export',
            metadata: [
                'document_count' => $documents->count(),
                'scope' => 'filtered',
            ],
        );

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function exportSelected(Request $request, ReversibilityExportService $service)
    {
        Gate::authorize('viewAny', Document::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->withErrors(['error' => 'Aucun document sélectionné.']);
        }

        $documents = Document::whereIn('id', $ids)
            ->with(['category', 'subcategory', 'department', 'service.subDepartment.department', 'box', 'boxFolder', 'createdBy', 'latestVersion', 'tags'])
            ->get();

        \App\Services\AuditService::logSubject(
            action: 'export_requested',
            subjectType: 'reversibility_export',
            metadata: [
                'document_count' => $documents->count(),
                'document_ids' => $ids,
                'scope' => 'selected',
            ],
        );

        $zipPath = $service->generate($documents);

        \App\Services\AuditService::logSubject(
            action: 'export_downloaded',
            subjectType: 'reversibility_export',
            metadata: [
                'document_count' => $documents->count(),
                'document_ids' => $ids,
                'scope' => 'selected',
            ],
        );

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
