@php
    $accessibleCategories = isset($categories) && $categories
        ? collect($categories->items())
        : collect();
@endphp

<div class="categories-section h-100">
    <div class="section-header">
        <h3>{{ __('Dossiers accessibles') }}</h3>
        <a href="{{ route('categories.index') }}" class="view-all">
            {{ ui_t('pages.view_all') }} <i class="fa-solid fa-angle-right"></i>
        </a>
    </div>

    <div class="row archive gy-3">
        @forelse($accessibleCategories->take(6) as $i => $category)
            @php
                $colors = [
                    ['bar' => '#f0d672', 'dot' => 'yellow-pending', 'icon' => 'assets/Group 634.svg'],
                    ['bar' => '#e63946', 'dot' => 'red-pending', 'icon' => 'assets/Group 6.svg'],
                    ['bar' => '#47a778', 'dot' => 'green-pending', 'icon' => 'assets/Group 8.svg'],
                    ['bar' => '#68a0fd', 'dot' => 'blue-pending', 'icon' => 'assets/Clip path group.svg'],
                ];
                $color = $colors[$i % count($colors)];
            @endphp
            <div class="col-md-6">
                <a href="{{ route('documents.by-category', ['categoryId' => $category->id]) }}" class="text-decoration-none text-reset">
                    <div class="border p-4 rounded-2 category-item-hover">
                        <div class="d-flex justify-content-between">
                            <img src="{{ asset($color['icon']) }}" alt="category" />
                            <div class="d-flex">
                                <h5 class="d-flex mt-1">
                                    <div class="color-pending {{ $color['dot'] }} me-2 mt-1"></div>
                                    {{ ui_t('pages.stats.pending') }}
                                </h5>
                                <p class="ms-2">{{ $category->pending_count ?? 0 }}</p>
                            </div>
                        </div>

                        <h3 class="mt-3">{{ $category->name }}</h3>
                        <div class="d-flex align-items-center mt-2">
                            <div class="progress" style="width: 100%; height: 8px; background-color: #eee; border-radius: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: 100%; border-radius: 10px; background-color: {{ $color['bar'] }};" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ms-2">{{ $category->total_count ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-4">
                    <p class="text-muted mb-0">{{ __('Aucune dossier accessible pour le moment.') }}</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
