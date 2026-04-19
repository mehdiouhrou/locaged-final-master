@php
    $feedIcon = function (string $kind): array {
        return match ($kind) {
            'upload' => ['icon' => 'fa-cloud-arrow-up', 'class' => 'bg-primary-subtle text-primary'],
            'pending' => ['icon' => 'fa-hourglass-half', 'class' => 'bg-warning-subtle text-warning-emphasis'],
            'approved' => ['icon' => 'fa-circle-check', 'class' => 'bg-success-subtle text-success-emphasis'],
            'declined' => ['icon' => 'fa-circle-xmark', 'class' => 'bg-danger-subtle text-danger-emphasis'],
            default => ['icon' => 'fa-file-lines', 'class' => 'bg-secondary-subtle text-secondary-emphasis'],
        };
    };
@endphp

<div class="event-feed">
    @if(!$canSeeFeed)
        <p class="text-muted">{{ __('Connectez-vous pour voir votre activité documentaire.') }}</p>
    @else
        <div class="row g-2 align-items-end mb-4">
            <div class="col-12 col-md-3">
                <label class="form-label small text-uppercase text-muted fw-bold mb-1">{{ __('Type') }}</label>
                <select class="form-select form-select-sm" wire:model.live="filterType">
                    <option value="all">{{ __('Tous') }}</option>
                    <option value="upload">{{ __('Mes dépôts') }}</option>
                    <option value="pending">{{ __('En attente d’approbation') }}</option>
                    <option value="decision">{{ __('Approuvés / refusés') }}</option>
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
            @forelse($items as $row)
                @php $ic = $feedIcon($row['kind'] ?? ''); @endphp
                <li class="list-group-item px-0 py-3 border-bottom">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center lgv2-activity-feed-icon {{ $ic['class'] }}" style="width:2.5rem;height:2.5rem;font-size:0.95rem;" aria-hidden="true">
                            <i class="fa-solid {{ $ic['icon'] }}"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            @if(!empty($row['url']))
                                <a href="{{ $row['url'] }}" class="fw-semibold text-dark text-decoration-none d-block">{{ $row['title'] }}</a>
                            @else
                                <span class="fw-semibold d-block">{{ $row['title'] }}</span>
                            @endif
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                <span class="badge rounded-pill bg-light text-dark border">{{ $row['status_label'] ?? '' }}</span>
                                <span class="text-muted small">{{ $row['at']?->diffForHumans() }}</span>
                                <span class="text-muted small">· {{ $row['at']?->timezone(config('app.timezone'))?->format('d/m/Y H:i') }}</span>
                            </div>
                            @if(!empty($row['secondary_line']))
                                <div class="small text-muted mt-1">{{ $row['secondary_line'] }}</div>
                            @endif
                        </div>
                        @if(!empty($row['actor_avatar']))
                            <img src="{{ $row['actor_avatar'] }}" alt="" class="rounded-circle flex-shrink-0 object-fit-cover" width="40" height="40" loading="lazy" />
                        @endif
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted py-4">{{ __('Aucune activité pour ce filtre.') }}</li>
            @endforelse
        </ul>
    @endif
</div>
