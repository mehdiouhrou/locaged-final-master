<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DocumentMovementController extends Controller
{


    // Store new document movement
    public function store(Request $request)
    {
        Gate::authorize('create', DocumentMovement::class);

        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'movement_type' => 'required|string|in:storage,retrieval,transfer',
            'moved_from_box_id' => 'nullable|exists:boxes,id',
            'moved_to_box_id' => 'required|exists:boxes,id',
        ]);

        $document = Document::findOrFail($data['document_id']);
        $data['moved_from_box_id'] = $data['moved_from_box_id'] ?? $document->box_id;

        if (!$data['moved_from_box_id']) {
            return back()->with('error','Moved from location is empty!');
        }

        if ($data['moved_from_box_id'] === $data['moved_to_box_id']) {
            return back()->with('error','Cannot move to same location');
        }
        
        // Also update document's box_id
        $document->box_id = $data['moved_to_box_id'];
        $document->save();

        $data['moved_by'] = Auth::id();
        $data['moved_at'] = now();
        DocumentMovement::create($data);

        $document->logAction('moved');


        return redirect()->back()->with('success', 'Document moved successfully.');
    }

    public function borrow(Request $request, Document $document)
    {
        Gate::authorize('create', DocumentMovement::class);
        Gate::authorize('view', $document);

        if (! $document->box_id) {
            return back()->with('error', __('Ce document n’a pas d’emplacement physique.'));
        }

        $openLoan = DocumentMovement::query()
            ->where('document_id', $document->id)
            ->openLoan()
            ->latest('moved_at')
            ->first();

        if ($openLoan) {
            return back()->with('error', __('Ce document est déjà emprunté.'));
        }

        $data = $request->validate([
            'borrowed_by_user_id' => 'nullable|exists:users,id',
            'borrower_name' => 'nullable|string|max:255',
            'due_at' => 'nullable|date|after_or_equal:today',
            'movement_note' => 'nullable|string|max:2000',
        ]);

        $borrowerName = trim((string) ($data['borrower_name'] ?? ''));
        if (! empty($data['borrowed_by_user_id'])) {
            $borrower = \App\Models\User::find($data['borrowed_by_user_id']);
            if ($borrower) {
                $borrowerName = $borrower->full_name;
            }
        }

        if ($borrowerName === '') {
            return back()->with('error', __('Veuillez renseigner le nom de l’emprunteur.'));
        }

        $movement = DocumentMovement::create([
            'document_id' => $document->id,
            'movement_type' => 'retrieval',
            'moved_from_box_id' => $document->box_id,
            'moved_to_box_id' => null,
            'moved_by' => Auth::id(),
            'borrowed_by_user_id' => $data['borrowed_by_user_id'] ?? null,
            'borrower_name' => $borrowerName,
            'due_at' => $data['due_at'] ?? null,
            'movement_note' => $data['movement_note'] ?? null,
            'moved_at' => now(),
        ]);

        $document->logAction('borrowed', $document->latestVersion?->id, [
            'movement_id' => $movement->id,
            'borrower_name' => $borrowerName,
            'due_at' => $movement->due_at?->toDateTimeString(),
        ]);

        return back()->with('success', __('Document physique emprunté avec succès.'));
    }

    public function returnBorrowed(Request $request, Document $document)
    {
        Gate::authorize('create', DocumentMovement::class);
        Gate::authorize('view', $document);

        $data = $request->validate([
            'return_note' => 'nullable|string|max:2000',
        ]);

        $returnPayload = [
            'returned_at' => now(),
            'returned_by_user_id' => Auth::id(),
            'return_note' => $data['return_note'] ?? null,
        ];

        // Clôturer tous les emprunts encore « ouverts » pour ce document. Sinon, s’il existe
        // plusieurs lignes retrieval sans returned_at (doublon / anciennes données), n’en mettre
        // qu’une à jour laissait un autre emprunt actif : l’aperçu, le physique et le dashboard
        // restaient sur « Emprunté ».
        [$affected, $closedIds] = DB::transaction(function () use ($document, $returnPayload) {
            $ids = DocumentMovement::query()
                ->where('document_id', $document->id)
                ->openLoan()
                ->orderByDesc('moved_at')
                ->orderByDesc('id')
                ->pluck('id');

            if ($ids->isEmpty()) {
                return [0, []];
            }

            $closedIds = $ids->all();
            $n = DocumentMovement::query()
                ->whereIn('id', $closedIds)
                ->update($returnPayload);

            return [$n, $closedIds];
        });

        if ($affected === 0) {
            return back()->with('error', __('Aucun emprunt actif pour ce document.'));
        }

        $document->logAction('returned', $document->latestVersion?->id, [
            'movement_ids' => $closedIds,
            'closed_movements' => count($closedIds),
            'returned_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', __('Document physique marqué comme retourné.'));
    }



    // Update movement
    public function update(Request $request, DocumentMovement $documentMovement)
    {
        Gate::authorize('update', $documentMovement);

        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'movement_type' => 'required|string|max:255',
            'moved_from_box_id' => 'nullable|exists:boxes,id',
            'moved_to_box_id' => 'required|exists:boxes,id',
            'moved_at' => 'nullable|date',
        ]);

        $documentMovement->update($data);

        return redirect()->route('document-movements.index')->with('success', 'Document movement updated.');
    }

    // Delete movement
    public function destroy(DocumentMovement $documentMovement)
    {
        Gate::authorize('delete', DocumentMovement::class);

        $documentMovement->delete();

        return redirect()->route('document-movements.index')->with('success', 'Document movement deleted.');
    }
}
