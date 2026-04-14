<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EventFeed extends Component
{
    /** all | approval | ocr | movement | status */
    public string $filterType = 'all';
    public string $categoryId = 'all';
    public ?string $fromDate = null;
    public ?string $toDate = null;

    protected $queryString = [
        'filterType' => ['except' => 'all', 'as' => 'type'],
        'categoryId' => ['except' => 'all', 'as' => 'category'],
        'fromDate' => ['except' => null, 'as' => 'from'],
        'toDate' => ['except' => null, 'as' => 'to'],
    ];

    public function render(): View
    {
        $user = auth()->user();
        $visibleDocumentIds = Document::query()->select('documents.id');

        $categories = Category::query()
            ->whereIn('id', Document::query()->select('category_id')->whereNotNull('category_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $auditQuery = AuditLog::query()
            ->with(['document:id,title,category_id', 'document.category:id,name'])
            ->whereNotNull('document_id')
            ->whereIn('document_id', $visibleDocumentIds)
            ->latest('occurred_at');

        if ($this->categoryId !== 'all') {
            $categoryId = (int) $this->categoryId;
            $auditQuery->whereHas('document', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            });
        }

        if (! empty($this->fromDate)) {
            $auditQuery->whereDate('occurred_at', '>=', $this->fromDate);
        }

        if (! empty($this->toDate)) {
            $auditQuery->whereDate('occurred_at', '<=', $this->toDate);
        }

        $items = $auditQuery
            ->limit(150)
            ->get()
            ->map(function (AuditLog $log) {
                $action = (string) $log->action;
                $normalized = strtolower($action);

                $type = match (true) {
                    str_contains($normalized, 'approve') || str_contains($normalized, 'declin') => 'approval',
                    str_contains($normalized, 'ocr') => 'ocr',
                    str_contains($normalized, 'borrow') || str_contains($normalized, 'return') || str_contains($normalized, 'move') || str_contains($normalized, 'transfer') => 'movement',
                    default => 'status',
                };

                return [
                    'source' => 'audit',
                    'type' => $type,
                    'at' => $log->occurred_at ?? $log->created_at ?? now(),
                    'title' => $action,
                    'body' => trim(
                        ($log->document?->title ?? __('Document introuvable'))
                        .' · '
                        .($log->document?->category?->name ?? __('Sans catégorie'))
                        .' · '
                        .($log->user_name ?? ($log->user?->full_name ?? __('Utilisateur inconnu')))
                    ),
                    'url' => $log->document_id ? route('documents.show', $log->document_id) : null,
                    'id' => 'a-'.$log->id,
                ];
            });

        if ($this->filterType !== 'all') {
            $items = $items->where('type', $this->filterType)->values();
        }

        return view('livewire.event-feed', [
            'items' => $items->take(100),
            'categories' => $categories,
            'canSeeFeed' => (bool) $user,
        ]);
    }

    public function updatedFromDate(): void
    {
        if ($this->toDate !== null && $this->fromDate !== null && $this->toDate < $this->fromDate) {
            $this->toDate = $this->fromDate;
        }
    }

    public function updatedToDate(): void
    {
        if ($this->toDate !== null && $this->fromDate !== null && $this->toDate < $this->fromDate) {
            $this->fromDate = $this->toDate;
        }
    }

    public function resetFilters(): void
    {
        $this->filterType = 'all';
        $this->categoryId = 'all';
        $this->fromDate = null;
        $this->toDate = null;
    }
}
