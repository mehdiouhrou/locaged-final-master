<?php

namespace App\Livewire;

use App\Models\Document;
use App\Services\CollaborativeDocumentService;
use Livewire\Component;
use Livewire\WithPagination;

class MyReviewsTable extends Component
{
    use WithPagination;

    public ?int $rejectingDocumentId = null;
    public string $rejectComment = '';

    public function validateDocument(int $documentId, CollaborativeDocumentService $service): void
    {
        $document = Document::findOrFail($documentId);

        $service->reviewerValidate($document, auth()->user());

        session()->flash('success', 'Document validé.');
    }

    public function openRejectForm(int $documentId): void
    {
        $this->rejectingDocumentId = $documentId;
        $this->rejectComment = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingDocumentId = null;
        $this->rejectComment = '';
    }

    public function confirmReject(CollaborativeDocumentService $service): void
    {
        $this->validate([
            'rejectComment' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rejectComment.required' => 'Merci de préciser le motif du rejet.',
        ]);

        $document = Document::findOrFail($this->rejectingDocumentId);

        $service->reviewerReject($document, auth()->user(), $this->rejectComment);

        $this->rejectingDocumentId = null;
        $this->rejectComment = '';

        session()->flash('success', 'Document rejeté.');
    }

    public function render()
    {
        $documents = Document::with(['createdBy', 'reviewers'])
            ->whereHas('reviewers', function ($q) {
                $q->where('reviewer_id', auth()->id())
                    ->where('status', 'pending');
            })
            ->where('status', 'en_relecture')
            ->latest()
            ->paginate(10);

        return view('livewire.my-reviews-table', compact('documents'));
    }
}
