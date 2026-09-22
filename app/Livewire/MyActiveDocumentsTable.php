<?php

namespace App\Livewire;

use App\Models\Document;
use Livewire\Component;

class MyActiveDocumentsTable extends Component
{
    /** reviews | mine */
    public string $activeTab = 'reviews';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $myReviews = Document::with(['createdBy', 'reviewers'])
            ->whereHas('reviewers', function ($q) {
                $q->where('reviewer_id', auth()->id())
                    ->where('status', 'pending');
            })
            ->where('status', 'en_relecture')
            ->latest()
            ->get();

        $myDocuments = Document::with(['createdBy', 'reviewers.reviewer'])
            ->where('created_by', auth()->id())
            ->whereIn('status', ['brouillon', 'en_relecture', 'valide'])
            ->latest()
            ->get();

        // Default to the first non-empty tab.
        if ($this->activeTab === 'reviews' && $myReviews->isEmpty() && $myDocuments->isNotEmpty()) {
            $this->activeTab = 'mine';
        }

        return view('livewire.my-active-documents-table', [
            'myReviews' => $myReviews,
            'myDocuments' => $myDocuments,
        ]);
    }
}
