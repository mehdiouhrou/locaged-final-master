<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Document;
use App\Models\DocumentDestructionRequest;
use App\Models\DocumentMovement;
use App\Models\LoanRequest;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LoanRequestController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LoanRequest::class);

        $user = Auth::user();

        $query = LoanRequest::query()->with(['document', 'box', 'requestedBy', 'reviewedBy']);

        if (! $user->can('view any loan request')) {
            $query->where(function ($q) use ($user) {
                $q->where('requested_by', $user->id);

                if ($user->can('view department loan request')) {
                    $departmentIds = $user->departments->pluck('id');
                    if ($departmentIds->isNotEmpty()) {
                        $q->orWhereHas('document', function ($dq) use ($departmentIds) {
                            $dq->whereIn('department_id', $departmentIds);
                        });
                    }
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $loanRequests = $query->latest()->paginate(20)->withQueryString();

        $pendingDestructions = DocumentDestructionRequest::query()
            ->with(['document.box.shelf.row.room', 'requestedBy'])
            ->where('status', 'accepted')
            ->latest()
            ->get();

        return view('loan-requests.index', compact('loanRequests', 'pendingDestructions'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', LoanRequest::class);

        $data = $request->validate([
            'document_id'             => 'nullable|exists:documents,id',
            'box_id'                  => 'nullable|exists:boxes,id',
            'reason'                  => 'required|string|max:2000',
            'requested_duration_days' => 'nullable|integer|min:1|max:365',
        ]);

        $hasDocument = ! empty($data['document_id']);
        $hasBox      = ! empty($data['box_id']);

        if ($hasDocument === $hasBox) {
            return back()->with('error', __('Veuillez selectionner soit un document, soit une boite, pas les deux.'));
        }

        if ($hasDocument) {
            $document = Document::findOrFail($data['document_id']);
            Gate::authorize('view', $document);

            $openLoan = LoanRequest::query()
                ->where('document_id', $document->id)
                ->whereIn('status', ['requested', 'approved', 'picked_up'])
                ->exists();

            if ($openLoan) {
                return back()->with('error', __('Une demande d\'emprunt est deja en cours pour ce document.'));
            }
        } else {
            $box = Box::findOrFail($data['box_id']);

            $accessibleServiceIds = Box::getAccessibleServiceIds(Auth::user());
            $canAccessBox = $accessibleServiceIds === 'all'
                || is_null($box->service_id)
                || (method_exists($accessibleServiceIds, 'contains') && $accessibleServiceIds->contains($box->service_id));

            if (! $canAccessBox) {
                abort(403);
            }

            $openLoan = LoanRequest::query()
                ->where('box_id', $box->id)
                ->whereIn('status', ['requested', 'approved', 'picked_up'])
                ->exists();

            if ($openLoan) {
                return back()->with('error', __('Une demande d\'emprunt est deja en cours pour cette boite.'));
            }
        }

        $loanRequest = LoanRequest::create([
            'document_id'             => $data['document_id'] ?? null,
            'box_id'                  => $data['box_id'] ?? null,
            'requested_by'            => Auth::id(),
            'reason'                  => $data['reason'],
            'requested_duration_days' => $data['requested_duration_days'] ?? null,
            'status'                  => 'requested',
        ]);

        // Notifier les responsables physiques
        $target = $hasDocument
            ? ($document->title ?? 'Document')
            : ($box->name ?? 'Boite');

        $documentId              = $hasDocument ? ($data['document_id'] ?? null) : null;
        $documentLatestVersionId = $hasDocument && isset($document)
            ? optional($document->latestVersion)->id
            : null;

        $responsables = User::role(['Chargé de dépôt', 'Chargée de dépôt'])->get();
        foreach ($responsables as $responsable) {
            $responsable->notify(new GeneralNotification(
                'info',
                'Nouvelle demande d\'emprunt',
                'Une demande d\'emprunt a ete soumise pour "' . $target . '" par ' . (Auth::user()->full_name ?? Auth::user()->name) . '.',
                'loan_requested',
                $documentId,
                $documentLatestVersionId,
                'fa-hand'
            ));
        }

        return back()->with('success', __('Demande d\'emprunt envoyee avec succes.'));
    }

    public function approve(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('approve', $loanRequest);

        if ($loanRequest->status !== 'requested') {
            return back()->with('error', __('Cette demande a deja ete traitee.'));
        }

        $dueAt = $loanRequest->requested_duration_days
            ? now()->addDays($loanRequest->requested_duration_days)
            : null;

        $loanRequest->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'due_at'      => $dueAt,
        ]);

        // Notifier le demandeur
        $target = $loanRequest->isForDocument()
            ? ($loanRequest->document->title ?? 'Document')
            : ($loanRequest->box->name ?? 'Boite');

        $documentId              = $loanRequest->document_id;
        $documentLatestVersionId = $loanRequest->document
            ? optional($loanRequest->document->latestVersion)->id
            : null;

        if ($loanRequest->requestedBy) {
            $loanRequest->requestedBy->notify(new GeneralNotification(
                'success',
                'Demande d\'emprunt approuvee',
                'Votre demande d\'emprunt pour "' . $target . '" a ete approuvee. Vous pouvez proceder au retrait physique.',
                'loan_approved',
                $documentId,
                $documentLatestVersionId,
                'fa-check'
            ));
        }

        return back()->with('success', __('Demande d\'emprunt approuvee.'));
    }

    public function reject(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('decline', $loanRequest);

        if ($loanRequest->status !== 'requested') {
            return back()->with('error', __('Cette demande a deja ete traitee.'));
        }

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $loanRequest->update([
            'status'           => 'rejected',
            'reviewed_by'      => Auth::id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        // Notifier le demandeur du refus
        $target = $loanRequest->isForDocument()
            ? ($loanRequest->document->title ?? 'Document')
            : ($loanRequest->box->name ?? 'Boite');

        $documentId              = $loanRequest->document_id;
        $documentLatestVersionId = $loanRequest->document
            ? optional($loanRequest->document->latestVersion)->id
            : null;

        if ($loanRequest->requestedBy) {
            $loanRequest->requestedBy->notify(new GeneralNotification(
                'danger',
                'Demande d\'emprunt refusee',
                'Votre demande d\'emprunt pour "' . $target . '" a ete refusee. Motif : ' . $data['rejection_reason'],
                'loan_rejected',
                $documentId,
                $documentLatestVersionId,
                'fa-xmark'
            ));
        }

        return back()->with('success', __('Demande d\'emprunt refusee.'));
    }

    public function pickUp(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('process', $loanRequest);

        if ($loanRequest->status !== 'approved') {
            return back()->with('error', __('Cette demande doit etre approuvee avant le retrait.'));
        }

        $movement = DB::transaction(function () use ($loanRequest) {
            $movementData = [
                'movement_type'       => 'retrieval',
                'moved_by'            => Auth::id(),
                'borrowed_by_user_id' => $loanRequest->requested_by,
                'borrower_name'       => $loanRequest->requestedBy?->full_name,
                'due_at'              => $loanRequest->due_at,
                'moved_at'            => now(),
            ];

            if ($loanRequest->isForDocument()) {
                $document = $loanRequest->document;
                $movementData['document_id']      = $document->id;
                $movementData['moved_from_box_id'] = $document->box_id;
            } else {
                $movementData['box_id']            = $loanRequest->box_id;
                $movementData['moved_from_box_id'] = $loanRequest->box_id;
            }

            $movement = DocumentMovement::create($movementData);

            $loanRequest->update([
                'status'       => 'picked_up',
                'movement_id'  => $movement->id,
                'picked_up_at' => now(),
            ]);

            return $movement;
        });

        return back()->with('success', __('Retrait physique enregistre.'));
    }

    public function returnLoan(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('process', $loanRequest);

        if ($loanRequest->status !== 'picked_up') {
            return back()->with('error', __('Cette demande n\'est pas en cours d\'emprunt.'));
        }

        $data = $request->validate([
            'return_note' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($loanRequest, $data) {
            if ($loanRequest->movement_id) {
                DocumentMovement::where('id', $loanRequest->movement_id)->update([
                    'returned_at'          => now(),
                    'returned_by_user_id'  => Auth::id(),
                    'return_note'          => $data['return_note'] ?? null,
                ]);
            }

            $loanRequest->update([
                'status'      => 'returned',
                'returned_at' => now(),
                'return_note' => $data['return_note'] ?? null,
            ]);
        });

        return back()->with('success', __('Emprunt marque comme retourne.'));
    }

    public function exportPdf(Request $request)
    {
        Gate::authorize('viewAny', LoanRequest::class);

        $user = Auth::user();

        $query = LoanRequest::query()->with(['document', 'box', 'requestedBy', 'reviewedBy']);

        if (!$user->can('view any loan request')) {
            $query->where(function ($q) use ($user) {
                $q->where('requested_by', $user->id);

                if ($user->can('view department loan request')) {
                    $departmentIds = DB::table('department_user')
                        ->where('user_id', $user->id)
                        ->pluck('department_id');

                    $q->orWhereHas('requestedBy', function ($uq) use ($departmentIds) {
                        $uq->whereHas('departments', function ($dq) use ($departmentIds) {
                            $dq->whereIn('departments.id', $departmentIds);
                        });
                    });
                }
            });
        }

        $status = $request->get('status');
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $loanRequests = $query->latest()->get();

        $headings = ['Cible', 'Demandeur', 'Motif', 'Date demande', 'Statut', 'Echeance', 'Date retour'];

        $rows = $loanRequests->map(function ($lr) {
            if ($lr->document) {
                $cible = $lr->document->title ?? 'N/A';
            } elseif ($lr->box) {
                $cible = $lr->box->reference ?? 'N/A';
            } else {
                $cible = 'N/A';
            }

            return [
                $cible,
                $lr->requestedBy->full_name ?? 'N/A',
                $lr->reason,
                $lr->created_at->format('d/m/Y'),
                $lr->status,
                $lr->due_at ? $lr->due_at->format('d/m/Y') : 'N/A',
                $lr->returned_at ? $lr->returned_at->format('d/m/Y') : 'N/A',
            ];
        })->toArray();

        $footer = ['Genere le ' . now()->format('d/m/Y H:i')];
        $filename = 'emprunts_' . now()->format('Ymd_His') . '.pdf';

        return (new \App\Services\PdfExportService)->download(
            'Registre des Emprunts',
            $headings,
            $rows,
            $filename,
            $footer
        );
    }

}
