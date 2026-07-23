<div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="recent-files-section">
        @if ($rows->isEmpty())
            <p class="text-muted mb-0">Aucun document actif pour le moment.</p>
        @else
            <div class="files-table-container">
                <table class="files-table table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Motif du rejet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $document = $row['document'];
                                $rejectComment = null;
                                if ($document->status === 'brouillon') {
                                    $lastRejection = $document->reviewers->sortByDesc('responded_at')->firstWhere('status', 'rejected');
                                    $rejectComment = $lastRejection->comment ?? null;
                                }
                                $statusBadge = match ($document->status) {
                                    'brouillon' => 'bg-secondary-subtle text-secondary-emphasis',
                                    'en_relecture' => 'bg-info-subtle text-info-emphasis',
                                    'valide' => 'bg-primary-subtle text-primary-emphasis',
                                    default => 'bg-secondary-subtle text-secondary-emphasis',
                                };
                            @endphp
                            <tr>
                                <td>
                                    @if ($document->latestVersion)
                                        <a href="{{ route('document-versions.preview', ['id' => $document->latestVersion->id]) }}"
                                           target="_blank" rel="noopener" class="file-name fw-semibold text-decoration-none">
                                            {{ $document->title }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $document->title }}</span>
                                    @endif
                                </td>
                                <td>{{ $row['role'] === 'reviewer' ? 'Relecteur' : 'Auteur' }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $statusBadge }}">
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
    </div>
</div>
