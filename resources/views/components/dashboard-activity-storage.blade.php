{{-- Dashboard tasks + activité documentaire (périmètre utilisateur, pas journal d’audit) --}}
@php
    $feed = isset($dashboardActivityFeed) ? collect($dashboardActivityFeed) : collect();
    $tasks = isset($pendingApprovalTasks) ? collect($pendingApprovalTasks) : collect();

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
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="h6 mb-0 fw-semibold text-dark">{{ __('Activité récente') }}</h3>
                <div class="d-flex align-items-center gap-2 small">
                    <a href="{{ route('notifications') }}" class="text-decoration-none">{{ __('Tout voir') }} →</a>
                    @can('view system activity log')
                        <span class="text-muted" aria-hidden="true">·</span>
                        <a href="{{ route('users.logs') }}" class="text-decoration-none text-muted">{{ __('Journal système') }}</a>
                    @endcan
                </div>
            </div>
            <div class="card-body pt-0">
                @if($feed->isEmpty())
                    <p class="text-muted small mb-0">{{ __('Aucune activité récente dans votre périmètre.') }}</p>
                @else
                    <ul class="list-group list-group-flush lgv2-activity-feed-list">
                        @foreach($feed->take(6) as $row)
                            @php
                                $ic = $feedIcon($row['kind'] ?? '');
                            @endphp
                            <li class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center lgv2-activity-feed-icon {{ $ic['class'] }}" aria-hidden="true">
                                        <i class="fa-solid {{ $ic['icon'] }}"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow-1">
                                        @if(!empty($row['url']))
                                            <a href="{{ $row['url'] }}" class="fw-semibold text-dark text-decoration-none d-block text-truncate" title="{{ $row['title'] }}">{{ $row['title'] }}</a>
                                        @else
                                            <span class="fw-semibold d-block text-truncate">{{ $row['title'] }}</span>
                                        @endif
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                            <span class="badge rounded-pill bg-light text-dark border">{{ $row['status_label'] ?? '' }}</span>
                                            <span class="text-muted small">{{ $row['at']?->diffForHumans() }}</span>
                                        </div>
                                        @if(!empty($row['secondary_line']))
                                            <div class="small text-muted mt-1">{{ $row['secondary_line'] }}</div>
                                        @endif
                                    </div>
                                    @if(!empty($row['actor_avatar']))
                                        <img src="{{ $row['actor_avatar'] }}" alt="" class="rounded-circle flex-shrink-0 lgv2-activity-feed-avatar" width="36" height="36" loading="lazy" />
                                    @endif
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
