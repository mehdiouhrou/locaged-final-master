@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $ocrJobs */
@endphp
<h2 class="h4 fw-bold mb-3">{{ ui_t('nav.ocr') }}</h2>
<p class="text-muted small mb-3">{{ ui_t('tables.recent_jobs') }}</p>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ ui_t('tables.file_name') }}</th>
                <th>{{ ui_t('tables.file_created_date') }}</th>
                <th>{{ ui_t('tables.status') }}</th>
                <th class="text-center">{{ ui_t('tables.results') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ocrJobs as $job)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($job->documentVersion?->file_path)
                                @php
                                    $extension = strtolower(pathinfo($job->documentVersion->file_path, PATHINFO_EXTENSION));
                                    $iconClass = getFileIcon($extension);
                                @endphp
                                <i class="{{ $iconClass }}" style="font-size: 1.25rem;"></i>
                            @else
                                <i class="fas fa-file text-secondary"></i>
                            @endif
                            <span class="text-truncate" style="max-width: 220px;" title="{{ $job->documentVersion?->document?->title }}">
                                {{ $job->documentVersion?->document?->title ?? '—' }}
                            </span>
                        </div>
                    </td>
                    <td class="small">{{ $job->documentVersion?->created_at }}</td>
                    <td>
                        <span class="badge bg-secondary-subtle text-dark">{{ ui_t('pages.ocr_jobs.status.' . $job->status) ?? ucfirst($job->status) }}</span>
                    </td>
                    <td class="text-center">
                        @can('view', $job->documentVersion?->document)
                            @canany(['view any ocr job', 'view department ocr job', 'view own ocr job'])
                                <a href="{{ route('document-versions.ocr', ['id' => $job->documentVersion->id]) }}" class="btn btn-sm btn-outline-primary">
                                    {{ ui_t('tables.view_results') }}
                                </a>
                            @endcanany
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">{{ ui_t('pages.chart.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-center mt-3 master-console-pagination">
    {{ $ocrJobs->links('pagination::bootstrap-5') }}
</div>
