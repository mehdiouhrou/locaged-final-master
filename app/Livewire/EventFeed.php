<?php

namespace App\Livewire;

use App\Http\Controllers\HomeController;
use App\Models\Category;
use App\Models\Document;
use App\Services\DashboardActivityFeedService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EventFeed extends Component
{
    /** all | upload | pending | decision */
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

        $categories = Category::query()
            ->whereIn('id', Document::query()->select('category_id')->whereNotNull('category_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        if (! $user) {
            return view('livewire.event-feed', [
                'items' => collect(),
                'categories' => $categories,
                'canSeeFeed' => false,
            ]);
        }

        $visible = app(HomeController::class)->getVisibleDocumentsQuery();
        $items = app(DashboardActivityFeedService::class)->feed($visible, $user, 250);

        if ($this->filterType === 'upload') {
            $items = $items->where('kind', 'upload')->values();
        } elseif ($this->filterType === 'pending') {
            $items = $items->where('kind', 'pending')->values();
        } elseif ($this->filterType === 'decision') {
            $items = $items->whereIn('kind', ['approved', 'declined'])->values();
        }

        if ($this->categoryId !== 'all') {
            $categoryId = (int) $this->categoryId;
            $items = $items->filter(fn (array $i) => (int) ($i['category_id'] ?? 0) === $categoryId)->values();
        }

        if (! empty($this->fromDate)) {
            $from = Carbon::parse($this->fromDate)->startOfDay();
            $items = $items->filter(fn (array $i) => $i['at'] && $i['at']->gte($from))->values();
        }

        if (! empty($this->toDate)) {
            $to = Carbon::parse($this->toDate)->endOfDay();
            $items = $items->filter(fn (array $i) => $i['at'] && $i['at']->lte($to))->values();
        }

        $items = $items->sortByDesc(fn (array $i) => $i['at']->getTimestamp())->values();

        return view('livewire.event-feed', [
            'items' => $items,
            'categories' => $categories,
            'canSeeFeed' => true,
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
