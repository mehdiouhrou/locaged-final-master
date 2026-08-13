<?php

namespace App\Livewire;

use App\Models\Box;
use App\Models\Category;
use App\Models\Document;
use App\Models\Room;
use App\Models\Row;
use App\Models\Shelf;
use App\Models\Subcategory;
use App\Services\CollaborativeDocumentService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssignCategoryForm extends Component
{
    public Document $document;
    public ?int $categoryId = null;
    public ?int $subcategoryId = null;
    public ?string $keywords = '';

    public $selectedRoomId = null;
    public $selectedRowId = null;
    public $selectedShelfId = null;
    public $selectedBoxId = null;
    public $selectedBoxFolderId = null;

    protected function rules(): array
    {
        return [
            'categoryId' => ['required', 'exists:categories,id'],
            'subcategoryId' => ['nullable', 'exists:subcategories,id'],
            'selectedBoxId' => ['nullable', 'exists:boxes,id'],
            'keywords' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function updatedCategoryId(): void
    {
        $this->subcategoryId = null;
    }

    public function updatedSelectedRoomId(): void
    {
        $this->selectedRowId = null;
        $this->selectedShelfId = null;
        $this->selectedBoxId = null;
        $this->selectedBoxFolderId = null;
    }

    public function updatedSelectedRowId(): void
    {
        $this->selectedShelfId = null;
        $this->selectedBoxId = null;
        $this->selectedBoxFolderId = null;
    }

    public function updatedSelectedShelfId(): void
    {
        $this->selectedBoxId = null;
        $this->selectedBoxFolderId = null;
    }

    protected function currentServiceId(): ?int
    {
        return (int) (DB::table('service_user')->where('user_id', $this->document->created_by)->orderBy('id')->value('service_id') ?: 0) ?: null;
    }

    public function getCategoriesProperty()
    {
        return Category::orderBy('name')->get();
    }

    public function getSubcategoriesProperty()
    {
        if (! $this->categoryId) {
            return collect();
        }

        return Subcategory::where('category_id', $this->categoryId)->orderBy('name')->get();
    }

    public function getRoomsProperty()
    {
        $user = auth()->user();
        $serviceId = $this->currentServiceId();

        return Room::whereHas('rows.shelves.boxes', function ($query) use ($user, $serviceId) {
            Box::applyPhysicalAccessFilter($query, $user, $serviceId);
        })->orderBy('name')->get();
    }

    public function getRowsProperty()
    {
        if (! $this->selectedRoomId) {
            return collect();
        }

        $user = auth()->user();
        $serviceId = $this->currentServiceId();

        return Row::where('room_id', $this->selectedRoomId)
            ->whereHas('shelves.boxes', function ($query) use ($user, $serviceId) {
                Box::applyPhysicalAccessFilter($query, $user, $serviceId);
            })->orderBy('name')->get();
    }

    public function getShelvesProperty()
    {
        if (! $this->selectedRowId) {
            return collect();
        }

        $user = auth()->user();
        $serviceId = $this->currentServiceId();

        return Shelf::where('row_id', $this->selectedRowId)
            ->whereHas('boxes', function ($query) use ($user, $serviceId) {
                Box::applyPhysicalAccessFilter($query, $user, $serviceId);
            })->orderBy('name')->get();
    }

    public function getBoxesProperty()
    {
        if (! $this->selectedShelfId) {
            return collect();
        }

        $query = Box::where('shelf_id', $this->selectedShelfId)->forUser(auth()->user());

        $serviceId = $this->currentServiceId();
        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        return $query->get();
    }

    public function getBoxFoldersProperty()
    {
        if (! $this->selectedBoxId) {
            return collect();
        }

        return \App\Models\BoxFolder::where('box_id', $this->selectedBoxId)->orderBy('name')->get();
    }

    public function submit(CollaborativeDocumentService $service)
    {
        if ($this->document->status !== 'valide') {
            session()->flash('error', 'La catégorie ne peut être assignée que sur un document clôturé.');
            return redirect()->route('documents.active');
        }

        $this->validate();

        $serviceId = $this->currentServiceId();

        $this->document->category_id = $this->categoryId;
        $this->document->subcategory_id = $this->subcategoryId;
        if ($serviceId) {
            $this->document->service_id = $serviceId;
        }
        $this->document->box_id = $this->selectedBoxId;
        $this->document->box_folder_id = $this->selectedBoxFolderId;

        $category = Category::find($this->categoryId);
        if ($category && $category->expiry_value && $category->expiry_unit && $this->document->created_at) {
            try {
                $base = $this->document->created_at->copy();
                $expire = match ($category->expiry_unit) {
                    'days' => $base->addDays($category->expiry_value),
                    'months' => $base->addMonthsNoOverflow($category->expiry_value),
                    'years' => $base->addYearsNoOverflow($category->expiry_value),
                    default => null,
                };
                if ($expire) {
                    $this->document->expire_at = $expire;
                }
            } catch (\Throwable $e) {
                // ignore parse errors
            }
        }

        $meta = is_array($this->document->metadata) ? $this->document->metadata : (array) ($this->document->metadata ?? []);
        $keywords = trim((string) $this->keywords);
        if ($keywords !== '') {
            $meta['keywords'] = $keywords;
        } else {
            unset($meta['keywords']);
        }
        $this->document->metadata = $meta;

        $this->document->status = 'attente_archivage';
        $this->document->save();

        $service->purgeIntermediateVersions($this->document);

        session()->flash('success', 'Catégorie assignée. Vous pouvez maintenant confirmer l\'archivage une fois le document rangé physiquement.');

        return redirect()->route('documents.active');
    }

    public function render()
    {
        return view('livewire.assign-category-form');
    }
}
