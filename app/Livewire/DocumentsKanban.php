<?php

namespace App\Livewire;

use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DocumentsKanban extends Component
{
    public int $limit = 40;

    public function mount(): void
    {
        Gate::authorize('viewAny', Document::class);
    }

    public function render(): View
    {
        $base = Document::query()
            ->with(['latestVersion'])
            ->where(function ($q) {
                $q->where('is_expired', false)
                    ->orWhereNull('is_expired');
            })
            ->latest();

        return view('livewire.documents-kanban', [
            'pending' => (clone $base)->where('status', 'pending')->limit($this->limit)->get(),
            'approved' => (clone $base)->where('status', 'approved')->limit($this->limit)->get(),
            'declined' => (clone $base)->where('status', 'declined')->limit($this->limit)->get(),
        ]);
    }
}
