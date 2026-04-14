<div class="event-feed">
    <div class="row g-2 align-items-end mb-4">
        <div class="col-12 col-md-3">
            <label class="form-label small text-uppercase text-muted fw-bold mb-1">{{ __('Type') }}</label>
            <select class="form-select form-select-sm" wire:model.live="filterType">
                <option value="all">{{ __('Tous') }}</option>
                <option value="approval">{{ __('Approbation') }}</option>
                <option value="ocr">{{ __('OCR') }}</option>
                <option value="movement">{{ __('Mouvement') }}</option>
                <option value="status">{{ __('Statut') }}</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small text-uppercase text-muted fw-bold mb-1">{{ __('Catégorie') }}</label>
            <select class="form-select form-select-sm" wire:model.live="categoryId">
                <option value="all">{{ __('Toutes les catégories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small text-uppercase text-muted fw-bold mb-1">{{ __('Du') }}</label>
            <input type="date" class="form-control form-control-sm" wire:model.live="fromDate">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small text-uppercase text-muted fw-bold mb-1">{{ __('Au') }}</label>
            <input type="date" class="form-control form-control-sm" wire:model.live="toDate">
        </div>
        <div class="col-12 col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilters">
                {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    <ul class="list-group list-group-flush">
        @forelse($items as $item)
            @php
                $badgeClass = match ($item['type']) {
                    'approval' => 'text-bg-primary',
                    'ocr' => 'text-bg-info',
                    'movement' => 'text-bg-warning',
                    default => 'text-bg-secondary',
                };
            @endphp
            <li class="list-group-item px-0 py-3 border-bottom">
                <div class="d-flex flex-wrap align-items-start gap-2 justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <span class="badge {{ $badgeClass }} text-uppercase small me-2">{{ $item['type'] }}</span>
                        @if(!empty($item['url']))
                            <a href="{{ $item['url'] }}" class="fw-semibold text-decoration-none">{{ $item['title'] }}</a>
                        @else
                            <span class="fw-semibold">{{ $item['title'] }}</span>
                        @endif
                        @if(!empty($item['body']))
                            <div class="text-muted small mt-1">{{ $item['body'] }}</div>
                        @endif
                        <div class="text-muted small mt-1">
                            {{ __('Journal documents') }}
                            · {{ $item['at']?->timezone(config('app.timezone'))?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted py-4">{{ __('Aucun événement pour ce filtre.') }}</li>
        @endforelse
    </ul>
</div>
