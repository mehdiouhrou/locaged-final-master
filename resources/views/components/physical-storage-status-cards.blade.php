<div class="card border-0 categories-section p-4 h-100">
    <div class="mb-3 d-flex justify-content-between align-items-start">
        <div>
            <p class="text-muted mb-1 fw-semibold">{{ __('pages.dashboard.physical_storage.section_kicker') }}</p>
            <h5 class="fw-bold">{{ __('pages.dashboard.physical_storage.section_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('pages.dashboard.physical_storage.section_hint') }}</p>
        </div>
        @can('viewAny', \App\Models\PhysicalLocation::class)
        <a href="{{ route('physical-locations.index', ['view_only' => 1]) }}" class="btn btn-sm btn-outline-secondary ms-3 text-nowrap">
            {{ __('pages.dashboard.physical_storage.see_locations') }} <i class="fa-solid fa-angle-right ms-1"></i>
        </a>
        @endcan
    </div>

    @if(isset($topBoxes) && $topBoxes->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Boîte') }}</th>
                        <th>{{ __('Emplacement') }}</th>
                        <th class="text-end">{{ __('Documents') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topBoxes as $box)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('documents.all', ['box_id' => $box->id]) }}'" style="cursor:pointer;">
                        <td class="fw-semibold">{{ $box->name }}</td>
                        <td class="text-muted small">{{ (string) $box }}</td>
                        <td class="text-end">
                            <span class="badge bg-primary rounded-pill">{{ $box->documents_count }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted small mt-3">{{ __('Aucune boîte avec des documents.') }}</p>
    @endif
</div>
