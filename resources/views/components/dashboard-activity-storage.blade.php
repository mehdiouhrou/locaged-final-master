{{-- Dashboard tasks + recent activity --}}
@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|null $documents */
    $recent = isset($documents) && $documents ? collect($documents->items()) : collect();
    $tasks = isset($pendingApprovalTasks) ? collect($pendingApprovalTasks) : collect();
@endphp

<div class="row g-4 mt-1 mb-4">
    <div class="col-lg-8 col-12">
        <div class="card border-0 shadow-sm lgv2-dashboard-card">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0 fw-semibold text-dark">{{ __('En attente de mon approbation') }}</h3>
                <a href="{{ route('documents.status') }}" class="small text-decoration-none">{{ __('Tout voir') }} →</a>
            </div>
            <div class="card-body pt-0">
                @if($tasks->isEmpty())
                    <p class="text-muted small mb-0">{{ __('Aucune tâche en attente.') }}</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($tasks->take(6) as $doc)
                            @php
                                $path = strtolower((string) optional($doc->latestVersion)->file_path);
                                $ext = pathinfo($path, PATHINFO_EXTENSION);
                                $badge = match ($ext) {
                                    'pdf' => ['label' => 'PDF', 'class' => 'bg-primary-subtle text-primary-emphasis'],
                                    'xls', 'xlsx', 'csv' => ['label' => 'XLS', 'class' => 'bg-success-subtle text-success-emphasis'],
                                    'doc', 'docx' => ['label' => 'DOC', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                    default => ['label' => strtoupper($ext ?: 'FILE'), 'class' => 'bg-light text-muted'],
                                };
                            @endphp
                            <li class="list-group-item px-0 d-flex align-items-center justify-content-between gap-3 border-0 border-bottom">
                                <div class="d-flex align-items-start gap-2 min-w-0 flex-grow-1">
                                    <span class="badge rounded-2 {{ $badge['class'] }} mt-1">{{ $badge['label'] }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ $doc->latestVersion ? route('document-versions.preview', ['id' => $doc->latestVersion->id]) : route('documents.show', $doc) }}" class="fw-semibold text-dark text-truncate d-inline-block text-decoration-none" style="max-width: 100%;">
                                            {{ $doc->title }}
                                        </a>
                                        <div class="small text-muted">
                                            {{ $doc->createdBy?->full_name ?? $doc->createdBy?->name ?? __('Inconnu') }}
                                            · {{ optional($doc->created_at)->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    @can('approve', \App\Models\Document::class)
                                        <form method="POST" action="{{ route('documents.approve', $doc->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-success-subtle text-success-emphasis border-0 px-2">
                                                {{ __('Approuver') }}
                                            </button>
                                        </form>
                                    @endcan
                                    @can('decline', \App\Models\Document::class)
                                        <button type="button" class="btn btn-sm btn-danger-subtle text-danger-emphasis border-0 px-2 js-dashboard-decline-btn"
                                                data-form-id="decline-dashboard-{{ $doc->id }}">
                                            {{ __('Refuser') }}
                                        </button>
                                        <form id="decline-dashboard-{{ $doc->id }}" method="POST" action="{{ route('documents.decline', $doc->id) }}" class="d-none">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="decline_reason" value="">
                                        </form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="card border-0 shadow-sm lgv2-dashboard-card">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0 fw-semibold text-dark">{{ __('Activité récente') }}</h3>
                <a href="{{ route('activity.feed') }}" class="small text-decoration-none">{{ __('Audit complet') }} →</a>
            </div>
            <div class="card-body pt-0">
                @if($recent->isEmpty())
                    <p class="text-muted small mb-0">{{ __('Aucune activité récente à afficher.') }}</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($recent->take(5) as $doc)
                            @php
                                $dotClass = match ((string) $doc->status) {
                                    'approved' => 'text-success',
                                    'pending' => 'text-warning',
                                    'declined' => 'text-danger',
                                    default => 'text-primary',
                                };
                            @endphp
                            <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2 border-0 border-bottom">
                                <i class="fa-solid fa-circle fa-2xs mt-2 {{ $dotClass }}"></i>
                                <div class="small">
                                    <span class="fw-semibold">{{ $doc->createdBy?->full_name ?? $doc->createdBy?->name ?? __('Utilisateur') }}</span>
                                    {{ __('a') }}
                                    @if((string) $doc->status === 'approved')
                                        {{ __('approuvé') }}
                                    @elseif((string) $doc->status === 'declined')
                                        {{ __('refusé') }}
                                    @else
                                        {{ __('mis à jour') }}
                                    @endif
                                    <span class="fw-semibold">{{ $doc->title }}</span>
                                    <div class="text-muted">{{ optional($doc->updated_at)->diffForHumans() }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-dashboard-decline-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const formId = button.getAttribute('data-form-id');
            const form = document.getElementById(formId);
            if (!form) {
                return;
            }

            const reason = window.prompt("{{ __('Motif du refus (optionnel)') }}", '');
            const trimmed = (reason || '').trim();

            const input = form.querySelector('input[name=\"decline_reason\"]');
            if (input) {
                input.value = trimmed;
            }
            form.submit();
        });
    });
});
</script>
