<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\LoanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LoanRequestController extends Controller
{
    /**
     * List loan requests (management page), scoped by policy.
     */
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

        return view('loan-requests.index', compact('loanRequests'));
    }

    /**
     * Store a new loan request (for a document OR a box).
     */
    public function store(Request $request)
    {
        Gate::authorize('create', LoanRequest::class);

        $data = $request->validate([
            'document_id' => 'nullable|exists:documents,id',
            'box_id' => 'nullable|exists:boxes,id',
            'reason' => 'required|string|max:2000',
            'requested_duration_days' => 'nullable|integer|min:1|max:365',
        ]);

        $hasDocument = ! empty($data['document_id']);
        $hasBox = ! empty($data['box_id']);

        if ($hasDocument === $hasBox) {
            return back()->with('error', __('Veuillez sélectionner soit un document, soit une boîte, pas les deux.'));
        }

        if ($hasDocument) {
            $document = Document::findOrFail($data['document_id']);
            Gate::authorize('view', $document);

            $openLoan = LoanRequest::query()
                ->where('document_id', $document->id)
                ->whereIn('status', ['requested', 'approved', 'picked_up'])
                ->exists();

            if ($openLoan) {
                return back()->with('error', __('Une demande d\'emprunt est déjà en cours pour ce document.'));
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
                return back()->with('error', __('Une demande d\'emprunt est déjà en cours pour cette boîte.'));
            }
        }

        LoanRequest::create([
            'document_id' => $data['document_id'] ?? null,
            'box_id' => $data['box_id'] ?? null,
            'requested_by' => Auth::id(),
            'reason' => $data['reason'],
            'requested_duration_days' => $data['requested_duration_days'] ?? null,
            'status' => 'requested',
        ]);

        return back()->with('success', __('Demande d\'emprunt envoyée avec succès.'));
    }

    /**
     * Approve a pending loan request.
     */
    public function approve(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('approve', $loanRequest);

        if ($loanRequest->status !== 'requested') {
            return back()->with('error', __('Cette demande a déjà été traitée.'));
        }

        $dueAt = $loanRequest->requested_duration_days
            ? now()->addDays($loanRequest->requested_duration_days)
            : null;

        $loanRequest->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'due_at' => $dueAt,
        ]);

        return back()->with('success', __('Demande d\'emprunt approuvée.'));
    }

    /**
     * Decline a pending loan request.
     */
    public function reject(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('decline', $loanRequest);

        if ($loanRequest->status !== 'requested') {
            return back()->with('error', __('Cette demande a déjà été traitée.'));
        }

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $loanRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', __('Demande d\'emprunt refusée.'));
    }

    /**
     * Mark a loan request as physically picked up: creates the underlying
     * DocumentMovement record (retrieval) and links it to the loan request.
     */
    public function pickUp(Request $request, LoanRequest $loanRequest)
    {
        Gate::authorize('process', $loanRequest);

        if ($loanRequest->status !== 'approved') {
            return back()->with('error', __('Cette demande doit être approuvée avant le retrait.'));
        }

        $movement = DB::transaction(function () use ($loanRequest) {
            $movementData = [
                'movement_type' => 'retrieval',
                'moved_by' => Auth::id(),
                'borrowed_by_user_id' => $loanRequest->requested_by,
                'borrower_name' => $loanRequest->requestedBy?->full_name,
                'due_at' => $loanRequest->due_at,
                'moved_at' => now(),
            ];

            if ($loanRequest->isForDocument()) {
                $document = $loanRequest->document;
                $movementData['document_id'] = $document->id;
                $movementData['moved_from_box_id'] = $document->box_id;
            } else {
                $movementData['box_id'] = $loanRequest->box_id;
                $movementData['moved_from_box_id'] = $loanRequest->box_id;
            }

            $movement = DocumentMovement::create($movementData);

            $loanRequest->update([
                'status' => 'picked_up',
                'movement_id' => $movement->id,
                'picked_up_at' => now(),
            ]);

            return $movement;
        });

        return back()->with('success', __('Retrait physique enregistré.'));
    }

    /**
     * Mark a loan request as returned: closes the underlying DocumentMovement.
     */
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
                    'returned_at' => now(),
                    'returned_by_user_id' => Auth::id(),
                    'return_note' => $data['return_note'] ?? null,
                ]);
            }

            $loanRequest->update([
                'status' => 'returned',
                'returned_at' => now(),
                'return_note' => $data['return_note'] ?? null,
            ]);
        });

        return back()->with('success', __('Emprunt marqué comme retourné.'));
    }
}
