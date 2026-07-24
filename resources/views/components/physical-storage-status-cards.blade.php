<div class="card border-0 categories-section p-4 h-100">
    <div class="mb-3">
        <p class="text-muted mb-1 fw-semibold">{{ __('pages.dashboard.physical_storage.section_kicker') }}</p>
        <h5 class="fw-bold">{{ __('pages.dashboard.physical_storage.section_title') }}</h5>
        <p class="text-muted small mb-0">{{ __('pages.dashboard.physical_storage.section_hint') }}</p>
    </div>

    @php
        $cardColors = [
            'total' => ['bg' => '#e5f0ff', 'bar' => '#68a0fd'],
            'digital_only' => ['bg' => '#e5f7f0', 'bar' => '#47a778'],
        ];
        $iconFor = fn (string $key) => match ($key) {
            'digital_only' => "\u{1F4BB}",
            'total' => "\u{1F4E6}",
            default => "\u{1F4C4}",
        };
    @endphp

    <div class="row g-3">
        @foreach($physicalStorageCards as $card)
            @php $color = $cardColors[$card['key']] ?? ['bg' => '#f5f5f5', 'bar' => '#999999']; @endphp
            <div class="col-md-6">
                <a href="{{ $card['url'] }}" class="text-decoration-none">
                    <div class="border rounded-3 p-4 h-100 position-relative" style="background-color: {{ $color['bg'] }};">
                        <div class="position-absolute top-0 start-0 end-0" style="height: 4px; border-radius: 12px 12px 0 0; background-color: {{ $color['bar'] }};"></div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fs-1" aria-hidden="true">{{ $iconFor($card['key']) }}</span>
                            <div class="fw-semibold text-dark fs-5" title="{{ $card['label'] }}">
                                {{ $card['label'] }}
                            </div>
                        </div>
                        <div class="text-dark">
                            <span class="fw-bold" style="font-size: 2rem;">{{ $card['count'] }}</span>
                            <span class="text-muted ms-1">{{ __('pages.dashboard.physical_storage.docs') }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @can('viewAny', \App\Models\PhysicalLocation::class)
        <div class="mt-3 text-end">
            <a href="{{ route('physical-locations.index', ['view_only' => 1]) }}" class="text-decoration-none small text-primary">
                {{ __('pages.dashboard.physical_storage.see_locations') }}<i class="fa-solid fa-angle-right ms-1" aria-hidden="true"></i>
            </a>
        </div>
    @endcan
</div>
