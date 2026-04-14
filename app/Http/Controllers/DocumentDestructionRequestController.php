<?php

namespace App\Http\Controllers;

use App\Enums\DocumentDestructionStatus;
use App\Enums\DocumentStatus;
use App\Exports\DestructionRequestsExport;
use App\Models\AuditLog;
use App\Models\DestructionCertificate;
use App\Models\Document;
use App\Models\DocumentDestructionRequest;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\DestructionCertificateService;
use App\Services\DestructionProofService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DocumentDestructionRequestController extends Controller
{
    // List all expired documents (ready for destruction or postponement)
    public function index()
    {
        $user = auth()->user();
        abort_unless($user && $user->can('access document expiration management'), 403);

        // Show documents that have expired in real-time (expire_at is in the past)
        // Must use withoutGlobalScopes() because Document model has a global scope
        // that hides expired documents from normal queries
        $query = Document::withoutGlobalScopes()
            ->with([
                'latestVersion',
                'createdBy',
                'department',
                'destructionCertificates' => function ($q) {
                    $q->whereNotNull('pdf_path')->latest('id');
                },
            ])
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->whereNull('deleted_at');  // Exclude soft-deleted documents

        $this->applyDocumentExpiryScope($query, $user);

        $expiredDocuments = $query->latest()->paginate(10);

        return view('documents-destructions.index', ['expiredDocuments' => $expiredDocuments]);
    }

    /**
     * Show log of permanently deleted documents (Deletion log).
     */
    public function deletionLogs()
    {
        $user = auth()->user();
        abort_unless($user && $user->can('access document expiration management'), 403);

        $query = AuditLog::with(['user', 'document' => function ($q) {
            $q->withTrashed()->with(['department', 'service.subDepartment']);
        }])
            ->where('action', 'permanently_deleted');

        $this->applyDeletionAuditScope($query, $user);

        $logs = $query->orderByDesc('occurred_at')->paginate(10);

        return view('users.deletion-logs', compact('logs'));
    }

    /**
     * Export deletion logs to Excel
     */
    public function exportDeletionLogs()
    {
        $user = auth()->user();
        abort_unless($user && $user->can('access document expiration management'), 403);

        $query = AuditLog::with(['user', 'document' => function ($q) {
            $q->withTrashed()->with(['department', 'service.subDepartment']);
        }])
            ->where('action', 'permanently_deleted');

        $this->applyDeletionAuditScope($query, $user);

        $logs = $query->orderByDesc('occurred_at')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DeletionLogsExport($logs),
            'deletion-logs-' . now()->format('Ymd_His') . '.xlsx'
        );
    }


    // Store a new request
    public function store(Request $request)
    {
        Gate::authorize('create', DocumentDestructionRequest::class);

        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'implementation_id' => 'nullable|exists:document_movements,id'
        ]);

        $data['status'] = 'pending';
        $data['requested_by'] = Auth::id();
        $data['requested_at'] = now();
        $req = DocumentDestructionRequest::create($data);

        // Immediately mark document as destroyed (physical copy removed, keep location unchanged)
        $document = $req->document;
        $document->status = \App\Enums\DocumentStatus::Destroyed;
        $document->save();
        $document->logAction('destroyed');
        $action = 'destruction requested';

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new GeneralNotification(
                'info',
                "Document $action",
                "A new destruction request has been submitted for the document \"{$document->title}\" by " . auth()->user()->name . ".",
                $document->id,
                $document->latestVersion->id,
                $action
            ));
        }


        return redirect()->back()->with('success', 'Destruction Request created.');
    }


    // Update request
    public function update(Request $request, DocumentDestructionRequest $destructionRequest)
    {

        Gate::authorize('update', $destructionRequest);


        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'status' => 'required|string',
            'implementation_id' => 'nullable|exists:document_movements,id',
            'implemented_at' => 'nullable|date',
        ]);

        $destructionRequest->update($data);

        return redirect()->route('destruction-requests.index')->with('success', 'Request updated.');
    }

    // Delete request
    public function destroy(DocumentDestructionRequest $destructionRequest)
    {
        Gate::authorize('delete', $destructionRequest);

        $destructionRequest->delete();

        return redirect()->route('destruction-requests.index')->with('success', ui_t('pages.destructions.request_deleted'));
    }

    public function approve($id)
    {
        Gate::authorize('approve', DocumentDestructionRequest::class);

        $destruction = DocumentDestructionRequest::findOrFail($id);

        $destruction->document->status = DocumentStatus::Destroyed;
        $destruction->document->save();

        $destruction->status = DocumentDestructionStatus::Accepted;
        $destruction->save();

        $destruction->document->logAction('destroyed');

        try {
            app(DestructionCertificateService::class)->issueForApproval($destruction, auth()->user());
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Document Destruction approved.');
    }

    public function decline($id)
    {
        Gate::authorize('decline', DocumentDestructionRequest::class);

        $destruction = DocumentDestructionRequest::findOrFail($id);
        $destruction->status = DocumentDestructionStatus::Rejected;
        $destruction->save();

        return back()->with('success', 'Document Destruction declined.');
    }

    /**
     * Postpone document expiration by adding time
     */
    public function postpone($id, Request $request)
    {
        Gate::authorize('postpone', DocumentDestructionRequest::class);

        $destruction = DocumentDestructionRequest::findOrFail($id);
        $document = $destruction->document;

        if (! $document) {
            return back()->with('error', 'Document not found.');
        }

        if (! $document->expire_at) {
            return back()->with('error', 'This document does not have an expiry date set.');
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:1000',
            'unit' => 'required|in:minutes,hours,days,weeks,months,years'
        ]);

        $amount = (int) $validated['amount'];
        $unit = $validated['unit'];

        // Store original expiry
        $originalExpiry = $document->expire_at->copy();

        // Add time based on unit
        switch ($unit) {
            case 'minutes':
                $document->expire_at = $document->expire_at->addMinutes($amount);
                break;
            case 'hours':
                $document->expire_at = $document->expire_at->addHours($amount);
                break;
            case 'days':
                $document->expire_at = $document->expire_at->addDays($amount);
                break;
            case 'weeks':
                $document->expire_at = $document->expire_at->addWeeks($amount);
                break;
            case 'months':
                $document->expire_at = $document->expire_at->addMonths($amount);
                break;
            case 'years':
                $document->expire_at = $document->expire_at->addYears($amount);
                break;
        }

        // Change status back to approved when coming from destroyed
        if ($document->status === DocumentStatus::Destroyed->value) {
            $document->status = DocumentStatus::Approved->value;
        }
        
        // Clear expired flag to make document visible again
        $document->is_expired = false;

        $document->save();

        // Log the action (let Document::logAction resolve the version id itself)
        $document->logAction('expiration_postponed');

        // Mark destruction request as postponed
        $destruction->status = DocumentDestructionStatus::Postponed;
        $destruction->save();

        $translatedUnit = ui_t("pages.destructions.postpone.{$unit}");

        return back()->with('success', ui_t('pages.destructions.postpone.success', [
            'amount' => $amount,
            'unit' => $translatedUnit,
            'date' => $document->expire_at->format('Y-m-d H:i:s')
        ]));
    }

    /**
     * Postpone document expiration directly (without destruction request)
     */
    public function postponeDocument($documentId, Request $request)
    {
        Gate::authorize('postpone', DocumentDestructionRequest::class);

        // Use withoutGlobalScopes to allow postponing expired documents from destructions page
        $document = Document::withoutGlobalScopes()->findOrFail($documentId);

        if (! $document->expire_at) {
            return back()->with('error', 'This document does not have an expiry date set.');
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:1000',
            'unit' => 'required|in:minutes,hours,days,weeks,months,years'
        ]);

        $amount = (int) $validated['amount'];
        $unit = $validated['unit'];

        // Add time based on unit
        switch ($unit) {
            case 'minutes':
                $document->expire_at = $document->expire_at->addMinutes($amount);
                break;
            case 'hours':
                $document->expire_at = $document->expire_at->addHours($amount);
                break;
            case 'days':
                $document->expire_at = $document->expire_at->addDays($amount);
                break;
            case 'weeks':
                $document->expire_at = $document->expire_at->addWeeks($amount);
                break;
            case 'months':
                $document->expire_at = $document->expire_at->addMonths($amount);
                break;
            case 'years':
                $document->expire_at = $document->expire_at->addYears($amount);
                break;
        }

        // Change status back to approved if it was destroyed
        if ($document->status === DocumentStatus::Destroyed->value) {
            $document->status = DocumentStatus::Approved->value;
        }
        
        // Clear expired flag to make document visible again in normal pages
        $document->is_expired = false;

        $document->save();

        // Log the action
        $document->logAction('expiration_postponed');

        $translatedUnit = ui_t("pages.destructions.postpone.{$unit}");

        return back()->with('success', ui_t('pages.destructions.postpone.success_active', [
            'amount' => $amount,
            'unit' => $translatedUnit,
            'date' => $document->expire_at->format('Y-m-d H:i:s')
        ]));
    }

    public function export()
    {
        Gate::authorize('viewAny', DocumentDestructionRequest::class);
        return Excel::download(new DestructionRequestsExport(), 'destruction_requests_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function downloadDestructionCertificate(DestructionCertificate $certificate)
    {
        Gate::authorize('view', $certificate);

        if (! $certificate->pdf_path || ! Storage::disk('private')->exists($certificate->pdf_path)) {
            abort(404);
        }

        return Storage::disk('private')->download(
            $certificate->pdf_path,
            'pv-destruction-'.$certificate->public_id.'.pdf'
        );
    }

    public function verifyDestructionProof(DestructionCertificate $certificate)
    {
        Gate::authorize('view', $certificate);

        $verification = app(DestructionProofService::class)->verifyProof($certificate);

        return view('destruction-certificates.verify', [
            'certificate' => $certificate,
            'verification' => $verification,
        ]);
    }

    public function downloadDestructionProofPackage(DestructionCertificate $certificate)
    {
        Gate::authorize('view', $certificate);

        if (! $certificate->proof_package_path || ! Storage::disk('private')->exists($certificate->proof_package_path)) {
            abort(404);
        }

        return Storage::disk('private')->download(
            $certificate->proof_package_path,
            'preuve-destruction-'.$certificate->public_id.'.zip'
        );
    }

    /**
     * @param  Builder<Document>  $query
     */
    private function applyDocumentExpiryScope(Builder $query, User $user): void
    {
        if ($user->can('view any document destruction request')) {
            return;
        }
        if ($user->can('view department document destruction request')) {
            $deptIds = $user->departments?->pluck('id') ?? collect();
            $query->whereIn('documents.department_id', $deptIds->all());

            return;
        }
        if ($user->can('view service document')) {
            $serviceIds = $this->collectUserServiceIds($user);
            if ($serviceIds->isNotEmpty()) {
                $query->whereIn('documents.service_id', $serviceIds->all());
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function applyDeletionAuditScope(Builder $query, User $user): void
    {
        if ($user->can('view any document destruction request')) {
            return;
        }
        if ($user->can('view department document destruction request')) {
            $deptIds = $user->departments?->pluck('id') ?? collect();
            $query->whereHas('document', function ($q) use ($deptIds) {
                $q->withTrashed()->whereIn('documents.department_id', $deptIds);
            });

            return;
        }
        if ($user->can('view service document')) {
            $serviceIds = $this->collectUserServiceIds($user);
            if ($serviceIds->isNotEmpty()) {
                $query->whereHas('document', function ($q) use ($serviceIds) {
                    $q->withTrashed()->whereIn('documents.service_id', $serviceIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function collectUserServiceIds(User $user): Collection
    {
        $serviceIds = collect();
        if ($user->service_id) {
            $serviceIds->push($user->service_id);
        }
        if ($user->relationLoaded('services') || method_exists($user, 'services')) {
            $serviceIds = $serviceIds->merge($user->services->pluck('id'));
        }

        return $serviceIds->unique()->filter();
    }
}
