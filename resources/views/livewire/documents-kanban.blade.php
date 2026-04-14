<div class="documents-kanban">
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning bg-opacity-25 border-0 fw-bold py-3">
                    {{ ui_t('pages.documents.status.pending') }}
                    <span class="badge bg-secondary ms-1">{{ $pending->count() }}</span>
                </div>
                <ul class="list-group list-group-flush small" style="max-height: 70vh; overflow-y: auto;">
                    @forelse($pending as $doc)
                        <li class="list-group-item d-flex flex-column gap-1">
                            <a href="{{ $doc->latestVersion ? route('document-versions.preview', ['id' => $doc->latestVersion->id]) : route('documents.show', $doc->id) }}" class="fw-semibold text-decoration-none text-dark">
                                {{ \Illuminate\Support\Str::limit($doc->title, 80) }}
                            </a>
                            <span class="text-muted">{{ $doc->created_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('Aucun document en attente.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 fw-bold py-3" style="background: rgba(204, 41, 41, 0.12); color: #b91c1c;">
                    {{ ui_t('pages.documents.status.approved') }}
                    <span class="badge bg-secondary ms-1">{{ $approved->count() }}</span>
                </div>
                <ul class="list-group list-group-flush small" style="max-height: 70vh; overflow-y: auto;">
                    @forelse($approved as $doc)
                        <li class="list-group-item d-flex flex-column gap-1">
                            <a href="{{ $doc->latestVersion ? route('document-versions.preview', ['id' => $doc->latestVersion->id]) : route('documents.show', $doc->id) }}" class="fw-semibold text-decoration-none text-dark">
                                {{ \Illuminate\Support\Str::limit($doc->title, 80) }}
                            </a>
                            <span class="text-muted">{{ $doc->created_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('Aucun document approuvé dans cet extrait.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 fw-bold py-3 text-secondary">
                    {{ ui_t('pages.documents.status.declined') }}
                    <span class="badge bg-secondary ms-1">{{ $declined->count() }}</span>
                </div>
                <ul class="list-group list-group-flush small" style="max-height: 70vh; overflow-y: auto;">
                    @forelse($declined as $doc)
                        <li class="list-group-item d-flex flex-column gap-1">
                            <a href="{{ $doc->latestVersion ? route('document-versions.preview', ['id' => $doc->latestVersion->id]) : route('documents.show', $doc->id) }}" class="fw-semibold text-decoration-none text-dark">
                                {{ \Illuminate\Support\Str::limit($doc->title, 80) }}
                            </a>
                            <span class="text-muted">{{ $doc->created_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('Aucun document refusé dans cet extrait.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    <p class="text-muted small mt-3 mb-0">
        {{ __('Affichage des :count derniers documents par colonne (les plus récents).', ['count' => $limit]) }}
    </p>
</div>
