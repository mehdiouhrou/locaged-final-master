<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="text-uppercase text-muted small fw-bold mb-3">Assigner une catégorie</h6>

        <form wire:submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">Catégorie</label>
                <select class="form-select" wire:model.live="categoryId">
                    <option value="">-- Sélectionner --</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('categoryId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            @if ($categoryId)
                <div class="mb-3">
                    <label class="form-label">Sous-catégorie (optionnel)</label>
                    <select class="form-select" wire:model="subcategoryId">
                        <option value="">-- Aucune --</option>
                        @foreach ($this->subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                        @endforeach
                    </select>
                    @error('subcategoryId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            @endif

            <hr class="my-3">
            <label class="form-label fw-semibold">Emplacement physique (optionnel)</label>

            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="selectedRoomId">
                        <option value="">-- Salle --</option>
                        @foreach ($this->rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="selectedRowId" @if(!$selectedRoomId) disabled @endif>
                        <option value="">-- Rangée --</option>
                        @foreach ($this->rows as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="selectedShelfId" @if(!$selectedRowId) disabled @endif>
                        <option value="">-- Étagère --</option>
                        @foreach ($this->shelves as $shelf)
                            <option value="{{ $shelf->id }}">{{ $shelf->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="selectedBoxId" @if(!$selectedShelfId) disabled @endif>
                        <option value="">-- Boîte --</option>
                        @foreach ($this->boxes as $box)
                            <option value="{{ $box->id }}">{{ $box->box_number ?? $box->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($selectedBoxId && $this->boxFolders->isNotEmpty())
                <div class="mb-3">
                    <label class="form-label">Nom de boîte (optionnel)</label>
                    <select class="form-select form-select-sm" wire:model="selectedBoxFolderId">
                        <option value="">-- Aucun --</option>
                        @foreach ($this->boxFolders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Mots-clés (optionnel)</label>
                <input type="text" class="form-control" wire:model="keywords" placeholder="Séparés par des virgules">
                @error('keywords') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-dark">
                <span wire:loading.remove wire:target="submit">Envoyer en attente d'approbation</span>
                <span wire:loading wire:target="submit">Envoi en cours...</span>
            </button>
        </form>
    </div>
</div>
