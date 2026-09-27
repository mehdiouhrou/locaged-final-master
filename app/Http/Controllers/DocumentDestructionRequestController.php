<?php

namespace App\Http\Controllers;

use App\Enums\DocumentDestructionStatus;
use App\Enums\DocumentStatus;
use App\Exports\DestructionRequestsExport;
use App\Services\PdfExportService;
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
    /**
     * Registre des PV / certificats de destruction (documents supprimés ou archivés y restent traçables).
     */
    public function certificatesIndex()
    {
        Gate::authorize('viewAny', DestructionCertificate::class);

        $certificates = DestructionCertificate::query()
            ->with([
                'approvedByUser:id,full_name',
                'document' => function ($q) {
                    $q->withoutGlobalScopes()->withTrashed()->select(['id', 'title', 'uid', 'deleted_at', 'created_by']);
                },
            ])
            ->whereNotNull('pdf_path')
            ->latest('id')
            ->paginate(25);

        return view('destruction-certificates.index', compact('certificates'));
    }

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
        $previousStatus = $document->status;
        $document->status = \App\Enums\DocumentStatus::Destroyed;
        $document->save();
        $document->logAction('destroyed', null, [
            'destruction_request_id' => $req->id,
            'previous_status' => $previousStatus,
        ]);
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

        $previousStatus = $destruction->document->status;

        $destruction->document->status = DocumentStatus::Destroyed;
        $destruction->document->save();

        $destruction->status = DocumentDestructionStatus::Accepted;
        $destruction->save();

        $destruction->document->logAction('destroyed', null, [
            'destruction_request_id' => $destruction->id,
            'previous_status' => $previousStatus,
            'approved_by' => auth()->id(),
        ]);

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
        $originalStatus = $document->status;

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
        $document->logAction('expiration_postponed', null, [
            'amount' => $amount,
            'unit' => $unit,
            'previous_expire_at' => $originalExpiry->toDateTimeString(),
            'new_expire_at' => $document->expire_at->toDateTimeString(),
            'previous_status' => $originalStatus,
            'new_status' => $document->status,
            'destruction_request_id' => $destruction->id,
        ]);

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

        $originalExpiry = $document->expire_at->copy();
        $originalStatus = $document->status;

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
        $document->logAction('expiration_postponed', null, [
            'amount' => $amount,
            'unit' => $unit,
            'previous_expire_at' => $originalExpiry->toDateTimeString(),
            'new_expire_at' => $document->expire_at->toDateTimeString(),
            'previous_status' => $originalStatus,
            'new_status' => $document->status,
        ]);

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

    public function exportPdf()
    {
        Gate::authorize('viewAny', \App\Models\DocumentDestructionRequest::class);

        $export = new DestructionRequestsExport;
        $rows = $export->query()->get()->map(fn($d) => $export->map($d))->toArray();

        return (new PdfExportService)->download(
            'Rapport Documents \u00e0 D\u00e9truire',
            $export->headings(),
            $rows,
            'destructions-' . now()->format('Ymd_His'),
            ['Export g\u00e9n\u00e9r\u00e9 le ' . now()->format('d/m/Y \u00e0 H:i')]
        );
    }

    public function exportDeletionLogsPdf(Request $request)
    {
        abort_unless(auth()->user()?->can('access document expiration management'), 403);

        $search = $request->get('search', '');
        $creationDate = $request->get('creationDate', '');
        $expirationDate = $request->get('expirationDate', '');
        $deletedAt = $request->get('deletedAt', '');
        $deletedBy = $request->get('deletedBy', '');
        $departmentId = $request->get('departmentId', '');
        $documentId = $request->get('document_id');

        $current = auth()->user();
        $hasOrgReport = $current && $current->can('view organization wide reports');
        $isMaster = $current && $current->can('view any role');
        $isSuperAdminNotMaster = $hasOrgReport && !$isMaster;
        $deptDeletionScope = $current && (
            $current->can('filter audit logs by assigned departments')
            || $current->can('filter audit logs by assigned subdepartments')
        );
        $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

        $query = \App\Models\AuditLog::with(['user.roles', 'document' => function ($q) {
                $q->withoutGlobalScopes()->withTrashed()->with([
                    'department',
                    'service.subDepartment',
                    'destructionCertificates' => function ($cq) {
                        $cq->whereNotNull('pdf_path')->latest('id');
                    },
                ]);
            }])
            ->where('action', 'permanently_deleted')
            ->when($isSuperAdminNotMaster, function($q) {
                $q->whereDoesntHave('user.roles', function($r) {
                    $r->whereRaw('LOWER(name) = ?', ['master']);
                });
            })
            ->when($deptDeletionScope && !$hasOrgReport, function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                $q->where(function($subQuery) use ($deptIds) {
                    $subQuery->whereHas('document', function($q2) use ($deptIds) {
                        $q2->withoutGlobalScopes()->withTrashed()->whereIn('department_id', $deptIds);
                    });
                })->whereDoesntHave('user.roles', function($r) {
                    $r->whereIn(\DB::raw('LOWER(name)'), ['master', 'super administrator', 'super_admin', 'admin']);
                });
            })
            ->when($isServiceAuditor && !$deptDeletionScope && !$hasOrgReport, function($q) use ($current) {
                $serviceIds = collect();
                if ($current->service_id) $serviceIds->push($current->service_id);
                if (method_exists($current, 'services')) {
                    $serviceIds = $serviceIds->merge($current->services->pluck('id'));
                }
                $serviceIds = $serviceIds->unique()->filter();
                if ($serviceIds->isNotEmpty()) {
                    $q->whereHas('document', function($q2) use ($serviceIds) {
                        $q2->withoutGlobalScopes()->withTrashed()->whereIn('service_id', $serviceIds);
                    })->whereDoesntHave('user.roles', function($r) {
                        $r->whereIn(\DB::raw('LOWER(name)'), ['master', 'super administrator', 'super_admin', 'admin', 'admin de pole', 'department administrator']);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($search, function($q) use ($search) {
                $q->whereHas('document', function($q2) use ($search) {
                    $q2->withTrashed()->where('title', 'like', '%'.$search.'%');
                });
            })
            ->when($creationDate, function($q) use ($creationDate) {
                $q->whereHas('document', function($q2) use ($creationDate) {
                    $q2->withTrashed()->whereDate('created_at', $creationDate);
                });
            })
            ->when($expirationDate, function($q) use ($expirationDate) {
                $q->whereHas('document', function($q2) use ($expirationDate) {
                    $q2->withTrashed()->whereDate('expire_at', $expirationDate);
                });
            })
            ->when($deletedAt, fn($q) => $q->whereDate('occurred_at', $deletedAt))
            ->when($deletedBy, fn($q) => $q->where('user_id', $deletedBy))
            ->when($departmentId, function($q) use ($departmentId) {
                $q->whereHas('document', function($q2) use ($departmentId) {
                    $q2->withTrashed()->where('department_id', $departmentId);
                });
            })
            ->when($documentId, fn($q) => $q->where('document_id', $documentId))
            ->orderByDesc('occurred_at');

        $logs = $query->get();
        $export = new \App\Exports\DeletionLogsExport($logs);
        $rows = $logs->map(fn($log) => $export->map($log))->toArray();

        return (new \App\Services\PdfExportService)->download(
            'Journal de Suppressions',
            $export->headings(),
            $rows,
            'deletion-logs-' . now()->format('Ymd_His'),
            ['Export genere le ' . now()->format('d/m/Y H:i')]
        );
    }


}
