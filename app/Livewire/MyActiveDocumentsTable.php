<?php

namespace App\Livewire;

use App\Enums\DocumentStatus;
use App\Http\Controllers\HomeController;
use App\Models\Document;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class MyActiveDocumentsTable extends Component
{
    /** to_approve | reviews | mine */
    public string $activeTab = 'to_approve';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $canApprove = Gate::any(['approve', 'decline'], Document::class);

        $toApprove = collect();
        if ($canApprove) {
            $visible = app(HomeController::class)->getVisibleDocumentsQuery();
            $toApprove = (clone $visible)
                ->where('status', DocumentStatus::Pending->value)
                ->with(['createdBy:id,full_name,email', 'latestVersion:id,document_id,file_path'])
                ->latest()
                ->get();
        }

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

        // Default to the first non-empty tab the user actually has access to.
        if ($this->activeTab === 'to_approve' && ! $canApprove) {
            $this->activeTab = $myReviews->isNotEmpty() ? 'reviews' : 'mine';
        }

        return view('livewire.my-active-documents-table', [
            'canApprove' => $canApprove,
            'toApprove' => $toApprove,
            'myReviews' => $myReviews,
            'myDocuments' => $myDocuments,
        ]);
    }
}
