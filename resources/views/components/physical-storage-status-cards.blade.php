<div class="card border-0 categories-section p-4 h-100">
    <div class="mb-3">
        <p class="text-muted mb-1 fw-semibold">{{ __('pages.dashboard.physical_storage.section_kicker') }}</p>
        <h5 class="fw-bold">{{ __('pages.dashboard.physical_storage.section_title') }}</h5>
        <p class="text-muted small mb-0">{{ __('pages.dashboard.physical_storage.section_hint') }}</p>
    </div>

    @php
        $cardColors = [
            ['bg' => '#fff7da', 'bar' => '#f0d672'],
            ['bg' => '#e5f7f0', 'bar' => '#47a778'],
            ['bg' => '#ffe5e7', 'bar' => '#e63946'],
            ['bg' => '#e5f0ff', 'bar' => '#68a0fd'],
        ];
        $iconFor = fn (string $key) => match ($key) {
            'total' => "\u{1F4E6}",
            'approved' => "\u{2705}",
            'pending' => "\u{23F3}",
            'declined' => "\u{274C}",
            'borrowed' => "\u{1F4E4}",
            'expired' => "\u{23F0}",
            default => "\u{1F4C4}",
        };
    @endphp

    <div class="row g-3">
        @foreach($physicalStorageCards as $index => $card)
            @php $color = $cardColors[$index % count($cardColors)]; @endphp
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ $card['url'] }}" class="text-decoration-none">
                    <div class="border rounded-3 p-3 h-100 position-relative" style="background-color: {{ $color['bg'] }};">
                        <div class="position-absolute top-0 start-0 end-0" style="height: 4px; border-radius: 12px 12px 0 0; background-color: {{ $color['bar'] }};"></div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="fs-4" aria-hidden="true">{{ $iconFor($card['key']) }}</span>
                            <div class="fw-semibold text-dark text-truncate" title="{{ $card['label'] }}">
                                {{ $card['label'] }}
                            </div>
                        </div>
                        <div class="text-muted small">{{ $card['count'] }} {{ __('pages.dashboard.physical_storage.docs') }}</div>
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
