<?php

namespace App\Livewire;

use App\Models\Document;
use Livewire\Component;

class MyActiveDocumentsTable extends Component
{
    public function render()
    {
        $myReviews = Document::with(['createdBy', 'reviewers'])
            ->whereHas('reviewers', function ($q) {
                $q->where('reviewer_id', auth()->id())
                    ->where('status', 'pending');
            })
            ->where('status', 'en_relecture')
            ->latest()
            ->get()
            ->map(fn ($doc) => ['document' => $doc, 'role' => 'reviewer']);

        $myDocuments = Document::with(['createdBy', 'reviewers.reviewer'])
            ->where('created_by', auth()->id())
            ->whereIn('status', ['brouillon', 'en_relecture', 'valide'])
            ->latest()
            ->get()
            ->map(fn ($doc) => ['document' => $doc, 'role' => 'author']);

        $combined = $myReviews->concat($myDocuments);

        return view('livewire.my-active-documents-table', ['rows' => $combined]);
    }
}
