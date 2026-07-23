<div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="recent-files-section">
        @if ($documents->isEmpty())
            <p class="text-muted mb-0">Aucune relecture en attente.</p>
        @else
            <div class="files-table-container">
                <table class="files-table table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Soumis le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
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
                                <td>{{ $document->createdBy->full_name ?? '—' }}</td>
                                <td>{{ $document->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if ($rejectingDocumentId === $document->id)
                                        <div class="d-flex flex-column gap-2" style="max-width: 400px;">
                                            <textarea class="form-control form-control-sm"
                                                      placeholder="Motif du rejet..."
                                                      wire:model="rejectComment"></textarea>
                                            @error('rejectComment') <div class="text-danger small">{{ $message }}</div> @enderror
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-danger" wire:click="confirmReject">
                                                    Confirmer le rejet
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary" wire:click="cancelReject">
                                                    Annuler
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="validateDocument({{ $document->id }})"
                                                    wire:confirm="Confirmer la validation de ce document ?">
                                                Valider
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    wire:click="openRejectForm({{ $document->id }})">
                                                Rejeter
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
