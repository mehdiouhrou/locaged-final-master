<div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="recent-files-section">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button type="button" class="nav-link {{ $activeTab === 'reviews' ? 'active' : '' }}" wire:click="setTab('reviews')">
                {{ __('En relecture') }} <span class="badge rounded-pill bg-info-subtle text-info-emphasis ms-1">{{ $myReviews->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link {{ $activeTab === 'mine' ? 'active' : '' }}" wire:click="setTab('mine')">
                {{ __('Mes documents') }} <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis ms-1">{{ $myDocuments->count() }}</span>
            </button>
        </li>
    </ul>

    @if ($activeTab === 'reviews')
        @if ($myReviews->isEmpty())
            <p class="text-muted mb-0">{{ __('Aucun document en attente de votre relecture.') }}</p>
        @else
            <div class="files-table-container">
                <table class="files-table table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Auteur') }}</th>
                            <th>{{ __('Statut') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($myReviews as $document)
                            <tr>
                                <td>
                                    @if ($document->latestVersion)
                                        <a href="{{ route('document-versions.preview', ['id' => $document->latestVersion->id]) }}"
                                           class="file-name fw-semibold text-decoration-none">
                                            {{ $document->title }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $document->title }}</span>
                                    @endif
                                </td>
                                <td>{{ $document->createdBy?->full_name ?? $document->createdBy?->name ?? __('Inconnu') }}</td>
                                <td>
                                    <span class="status-badge {{ $document->status }}">
                                        {{ ui_t('pages.documents.status.' . $document->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    @if ($activeTab === 'mine')
        @if ($myDocuments->isEmpty())
            <p class="text-muted mb-0">{{ __('Aucun document actif pour le moment.') }}</p>
        @else
            <div class="files-table-container">
                <table class="files-table table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Motif du rejet') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($myDocuments as $document)
                            @php
                                $rejectComment = null;
                                if ($document->status === 'brouillon') {
                                    $lastRejection = $document->reviewers->sortByDesc('responded_at')->firstWhere('status', 'rejected');
                                    $rejectComment = $lastRejection->comment ?? null;
                                }
                            @endphp
                            <tr>
                                <td>
                                    @if ($document->latestVersion)
                                        <a href="{{ route('document-versions.preview', ['id' => $document->latestVersion->id]) }}"
                                           class="file-name fw-semibold text-decoration-none">
                                            {{ $document->title }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $document->title }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge {{ $document->status }}">
                                        {{ ui_t('pages.documents.status.' . $document->status) }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $rejectComment ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-mytasks-decline-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const formId = button.getAttribute('data-form-id');
            const form = document.getElementById(formId);
            if (!form) {
                return;
            }

            const reason = window.prompt("{{ __('Motif du refus (optionnel)') }}", '');
            const trimmed = (reason || '').trim();

            const input = form.querySelector('input[name="decline_reason"]');
            if (input) {
                input.value = trimmed;
            }
            form.submit();
        });
    });
});
</script>
