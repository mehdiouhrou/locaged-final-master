@extends('layouts.app')

@section('content')
    @php
        $document = $document ?? ($doc->document ?? null);
        $isDestructionContext = request()->boolean('destruction');
        $status = (string) ($document->status ?? '');
        $isBorrowed = isset($currentLoan) && $currentLoan;
        $statusBadgeClass = match ($status) {
            'approved' => 'bg-success-subtle text-success-emphasis',
            'archived' => 'bg-success-subtle text-success-emphasis',
            'pending' => 'bg-warning-subtle text-warning-emphasis',
            'attente_archivage' => 'bg-warning-subtle text-warning-emphasis',
            'declined' => 'bg-danger-subtle text-danger-emphasis',
            'expired' => 'bg-danger-subtle text-danger-emphasis',
            'brouillon' => 'bg-secondary-subtle text-secondary-emphasis',
            'en_relecture' => 'bg-info-subtle text-info-emphasis',
            'valide' => 'bg-primary-subtle text-primary-emphasis',
            'destroyed' => 'bg-dark-subtle text-dark-emphasis',
            default => 'bg-light text-dark',
        };
        $isCollaborativePreArchive = ($document->entry_type ?? null) === 'collaborative'
            && in_array($status, ['brouillon', 'en_relecture', 'valide'], true);
    @endphp

    <div class="container-fluid px-3 px-md-4 py-3">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h4 class="fw-bold mb-1 text-uppercase">{{ $document->title }}</h4>
                    <p class="mb-0 text-muted small">{{ ui_t('pages.documents.status.' . $status) }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('document-versions.fullscreen', ['id' => $doc->id, 'return_url' => url()->full()]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-expand me-1"></i>{{ __('Aperçu plein écran') }}
                    </a>
                    <a href="{{ route('documents.download', ['id' => $document->id]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-download me-1"></i>{{ __('Document') }}
                    </a>
                    @if($isDestructionContext)
                        <a href="{{ route('documents-destructions.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i>{{ __('Fermer') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Aperçu') }}</h6>
                    </div>
                    <div class="card-body p-2" style="min-height: 70vh;">
                        @if($fileType === 'image')
                            <img src="{{ $fileUrl }}" class="w-100 h-100" style="object-fit: contain; min-height: 65vh;" alt="{{ ui_t('pages.upload.image_preview') }}" />
                        @elseif($fileType === 'pdf')
                            <iframe src="{{ $fileUrl }}" class="w-100 border-0" style="min-height: 68vh;"></iframe>
                        @elseif(in_array($fileType, officeFileTypesForPdfConversion(), true))
                            @if(!empty($pdfUrl))
                                <iframe src="{{ $pdfUrl }}" class="w-100 border-0" style="min-height: 68vh;"></iframe>
                            @else
                                <div class="h-100 d-flex align-items-center justify-content-center text-center text-muted p-4">
                                    <div>
                                        <i class="fa-solid fa-file-lines fa-3x mb-3"></i>
                                        <p class="mb-2">{{ __('Aperçu intégré non disponible pour ce type de fichier.') }}</p>
                                        <a href="{{ $fileUrl }}" class="btn btn-sm btn-outline-secondary">{{ __('Télécharger') }}</a>
                                    </div>
                                </div>
                            @endif
                        @elseif($fileType === 'video')
                            <video controls class="w-100" style="min-height: 68vh;">
                                <source src="{{ $fileUrl }}" type="video/mp4">
                                {{ ui_t('pages.versions.video_not_supported') }}
                            </video>
                        @elseif($fileType === 'audio')
                            <div class="h-100 d-flex align-items-center justify-content-center text-center p-4">
                                <div>
                                    <audio controls class="mb-3">
                                        <source src="{{ $fileUrl }}" type="audio/mpeg">
                                        {{ ui_t('pages.versions.audio_not_supported') }}
                                    </audio>
                                    <div>
                                        <a href="{{ $fileUrl }}" class="btn btn-sm btn-outline-secondary">{{ __('Télécharger') }}</a>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="h-100 d-flex align-items-center justify-content-center text-center text-muted p-4">
                                <div>
                                    <i class="fa-solid fa-file fa-3x mb-3"></i>
                                    <p class="mb-2">{{ __('Aperçu non disponible.') }}</p>
                                    <a href="{{ $fileUrl }}" class="btn btn-sm btn-outline-secondary">{{ __('Télécharger') }}</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @unless($isDestructionContext)
                <div class="col-12 col-xl-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Métadonnées') }}</h6>
                        </div>
                        <div class="card-body small">
                            <dl class="row mb-0 gy-2">
                                <dt class="col-6 text-muted">{{ __('Statut') }}</dt>
                                <dd class="col-6 mb-0">
                                    <span class="badge rounded-pill {{ $statusBadgeClass }}">{{ ui_t('pages.documents.status.' . $status) }}</span>
                                </dd>

                                <dt class="col-6 text-muted">{{ __('Date de création') }}</dt>
                                <dd class="col-6 mb-0">{{ $document->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Date d’expiration') }}</dt>
                                <dd class="col-6 mb-0">{{ $document->expire_at?->format('d/m/Y') ?? '—' }}</dd>

                                @unless($isCollaborativePreArchive)
                                <dt class="col-6 text-muted">{{ __('Dossier') }}</dt>
                                <dd class="col-6 mb-0">{{ $document->category?->name ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Sous-dossier') }}</dt>
                                <dd class="col-6 mb-0">{{ $document->subcategory?->name ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Emplacement physique') }}</dt>
                                <dd class="col-6 mb-0 text-break">@if($document->box){{ $document->box->__toString() }}@elseif($document->isDigitalOnly()){{ __('pages.documents.digital_only_location') }}@else—@endif</dd>

                                @can('move', $document)
                                <dt class="col-6 text-muted">{{ __('Déplacer') }}</dt>
                                <dd class="col-6 mb-0">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#moveDocumentModal-{{ $document->id }}">
                                        <i class="fa-solid fa-arrows-up-down-left-right me-1"></i>{{ __('Changer d\'emplacement') }}
                                    </button>
                                </dd>
                                @endcan

                                @if($document->boxFolder)
                                <dt class="col-6 text-muted">{{ __('Nom de boîte') }}</dt>
                                <dd class="col-6 mb-0 text-break">{{ $document->boxFolder->name }}</dd>
                                @endif

                                <dt class="col-6 text-muted">{{ __('Empreinte (SHA-256)') }}</dt>
                                <dd class="col-6 mb-0 text-break">{{ $document->file_hash ?? 'Non calculée' }}</dd>
                                @endunless

                                <dt class="col-6 text-muted">{{ __('Tags') }}</dt>
                                <dd class="col-6 mb-0 text-break">
                                    @php
                                        $tagNames = $document->tags?->pluck('name')->filter()->values() ?? collect();
                                    @endphp
                                    {{ $tagNames->isNotEmpty() ? $tagNames->join(', ') : '—' }}
                                </dd>
                            </dl>
                        </div>
                    </div>

                    @if($document->comments->isNotEmpty())
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Historique des commentaires') }}</h6>
                            </div>
                            <div class="card-body py-2">
                                <ul class="list-group list-group-flush">
                                    @foreach($document->comments as $comment)
                                        @php
                                            $commentTypeLabel = match ($comment->type) {
                                                'reviewer_rejection' => __('Rejet'),
                                                'initial_description' => __('Soumission initiale'),
                                                default => __('Resoumission'),
                                            };
                                            $commentTypeBadge = match ($comment->type) {
                                                'reviewer_rejection' => 'bg-danger-subtle text-danger-emphasis',
                                                'initial_description' => 'bg-secondary-subtle text-secondary-emphasis',
                                                default => 'bg-info-subtle text-info-emphasis',
                                            };
                                        @endphp
                                        <li class="list-group-item px-0 py-2 border-0 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-semibold">{{ $comment->user->full_name ?? '—' }}</span>
                                                <span class="badge rounded-pill {{ $commentTypeBadge }}">{{ $commentTypeLabel }}</span>
                                            </div>
                                            <div class="small">{{ $comment->comment }}</div>
                                            <div class="text-muted small mt-1">{{ $comment->created_at->format('d/m/Y H:i') }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if($document->documentVersions->count() > 1)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Versions') }}</h6>
                            </div>
                            <div class="card-body py-2">
                                <ul class="list-group list-group-flush">
                                    @foreach($document->documentVersions as $version)
                                        <li class="list-group-item px-0 py-2 border-0 border-bottom d-flex justify-content-between align-items-center">
                                            <a href="{{ route('document-versions.preview', ['id' => $version->id]) }}"
                                               class="text-decoration-none {{ $version->id === $doc->id ? 'fw-bold' : '' }}">
                                                V{{ (int) $version->version_number }}
                                                @if($version->id === $doc->id)
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ __('Consultée') }}</span>
                                                @endif
                                            </a>
                                            <span class="text-muted small">{{ $version->uploaded_at?->format('d/m/Y H:i') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @can('approve document')
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Historique des statuts') }}</h6>
                        </div>
                        <div class="card-body py-3">
                            <x-document-approval-timeline :document="$document" />
                        </div>
                    </div>
                    @endcan

                    @if($document->reviewers->isNotEmpty())
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Relecteurs') }}</h6>
                            </div>
                            <div class="card-body py-3">
                                <ul class="list-group list-group-flush">
                                    @foreach($document->reviewers as $reviewer)
                                        @php
                                            $reviewerBadge = match ($reviewer->status) {
                                                'validated' => 'bg-success-subtle text-success-emphasis',
                                                'rejected' => 'bg-danger-subtle text-danger-emphasis',
                                                default => 'bg-secondary-subtle text-secondary-emphasis',
                                            };
                                            $reviewerLabel = match ($reviewer->status) {
                                                'validated' => __('Validé'),
                                                'rejected' => __('Rejeté'),
                                                default => __('En attente'),
                                            };
                                        @endphp
                                        <li class="list-group-item px-0 py-2 border-0 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold">{{ $reviewer->reviewer->full_name ?? '—' }}</span>
                                                <span class="badge rounded-pill {{ $reviewerBadge }}">{{ $reviewerLabel }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if($document->entry_type === 'collaborative')
                        @php
                            $canAddAttachment = in_array($document->status, ['brouillon', 'en_relecture'], true)
                                && ((int) $document->created_by === (int) auth()->id()
                                    || $document->reviewers()->where('reviewer_id', auth()->id())->exists());
                        @endphp
                        <div class="mb-3">
                            @livewire('document-attachment-form', ['document' => $document, 'canAdd' => $canAddAttachment], key('attachments-'.$document->id))
                        </div>
                    @endif

                    @if(($document->status ?? null) === 'brouillon' && $document->created_by === auth()->id())
                        <div class="mb-3">
                            @livewire('resubmit-document-form', ['document' => $document])
                        </div>
                    @endif

                    @if(($document->status ?? null) === 'valide' && $document->created_by === auth()->id())
                        <div class="mb-3">
                            @livewire('assign-category-form', ['document' => $document])
                        </div>
                    @endif

                    @unless($isCollaborativePreArchive)
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Suivi physique') }}</h6>
                        </div>
                        <div class="card-body small">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">{{ __('État du dossier physique') }}</span>
                                @if($isBorrowed)
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">{{ __('Emprunté') }}</span>
                                @else
                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ __('Disponible') }}</span>
                                @endif
                            </div>

                            @if($isBorrowed)
                                <div class="border rounded-2 p-2 mb-3">
                                    <div><strong>{{ __('Emprunteur') }}:</strong> {{ $currentLoan->borrower_name ?? $currentLoan->borrowedBy?->full_name ?? '—' }}</div>
                                    <div><strong>{{ __('Date emprunt') }}:</strong> {{ $currentLoan->moved_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                    <div><strong>{{ __('Retour prévu') }}:</strong> {{ $currentLoan->due_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                </div>
                                <p class="text-muted mb-0 small">{{ __('Le retour physique est enregistré par le responsable des emprunts.') }}</p>
                            @elseif(isset($activeLoanRequest) && $activeLoanRequest)
                                <div class="border rounded-2 p-2 mb-3">
                                    @if($activeLoanRequest->status === 'requested')
                                        <span class="badge bg-warning text-dark mb-2">{{ __('Demande en attente d’approbation') }}</span>
                                    @elseif($activeLoanRequest->status === 'approved')
                                        <span class="badge bg-info text-dark mb-2">{{ __('Demande approuvée — en attente de retrait') }}</span>
                                    @endif
                                    <div class="text-muted small">{{ __('Motif') }}: {{ $activeLoanRequest->reason }}</div>
                                </div>
                            @else
                                @can('create', \App\Models\LoanRequest::class)
                                    <form method="POST" action="{{ route('loan-requests.store') }}" class="mb-2">
                                        @csrf
                                        <input type="hidden" name="document_id" value="{{ $document->id }}">
                                        <label class="form-label small mb-1">{{ __('Motif de la demande') }}</label>
                                        <textarea name="reason" rows="2" class="form-control form-control-sm mb-2" required placeholder="{{ __('Pourquoi souhaitez-vous emprunter ce dossier ?') }}"></textarea>
                                        <label class="form-label small mb-1">{{ __('Durée souhaitée en jours (optionnel)') }}</label>
                                        <input type="number" name="requested_duration_days" min="1" max="365" class="form-control form-control-sm mb-2">
                                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                                            <i class="fa-solid fa-hand me-1"></i>{{ __('Demander l’emprunt') }}
                                        </button>
                                    </form>
                                @else
                                    <p class="text-muted mb-0">{{ __('Vous n’avez pas les droits pour demander un emprunt.') }}</p>
                                @endcan
                            @endif

                            @can('approve document')
                            <hr class="my-3">
                            <h6 class="small fw-bold text-muted text-uppercase mb-2">{{ __('Historique physique') }}</h6>
                            @if(isset($physicalMovements) && $physicalMovements->isNotEmpty())
                                <ul class="list-group list-group-flush">
                                    @foreach($physicalMovements->take(6) as $movement)
                                        <li class="list-group-item px-0 py-2 border-0 border-bottom">
                                            <div class="fw-semibold">
                                                @if($movement->movement_type === 'retrieval' && ($movement->borrowed_by_user_id || $movement->borrower_name))
                                                    {{ __('Emprunt') }}
                                                @elseif($movement->movement_type === 'transfer')
                                                    {{ __('Transfert') }}
                                                @else
                                                    {{ __('Mouvement') }}
                                                @endif
                                            </div>
                                            <div class="text-muted">
                                                {{ $movement->moved_at?->format('d/m/Y H:i') ?? '—' }}
                                                · {{ $movement->movedBy?->full_name ?? __('Inconnu') }}
                                            </div>
                                            @if($movement->borrower_name)
                                                <div class="text-muted">{{ __('Emprunteur') }}: {{ $movement->borrower_name }}</div>
                                            @endif
                                            @if($movement->returned_at)
                                                <div class="text-success">{{ __('Retourné le') }} {{ $movement->returned_at?->format('d/m/Y H:i') }}</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted mb-0">{{ __('Aucun mouvement physique enregistré.') }}</p>
                            @endif
                            @endcan
                        </div>
                    </div>
                    @endunless

                    @if(($document->status ?? null) === 'pending')
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex gap-2 justify-content-end">
                                @can('decline', $document)
                                    <button
                                        class="btn btn-sm btn-outline-danger trigger-action"
                                        data-id="{{ $document->id }}"
                                        data-name="{{ $document->title }}"
                                        data-url="{{ route('documents.decline', $document->id) }}"
                                        data-method="PUT"
                                        data-button-text="{{ ui_t('actions.confirm') }}"
                                        data-title="{{ ui_t('pages.documents.reject_title') }}"
                                        data-body="{{ ui_t('pages.documents.reject_body') }}"
                                        data-extra-fields='{{ json_encode([["type"=>"textarea","name"=>"decline_reason","label"=>__("Motif du refus (optionnel)"),"required"=>false,"rows"=>4,"maxlength"=>5000,"placeholder"=>__("Décrivez la raison du refus (facultatif)")]], JSON_UNESCAPED_UNICODE) }}'
                                    >
                                        {{ __('Refuser') }}
                                    </button>
                                @endcan
                                @can('approve', $document)
                                    <button
                                        class="btn btn-sm btn-outline-success trigger-action"
                                        data-id="{{ $document->id }}"
                                        data-name="{{ $document->title }}"
                                        data-url="{{ route('documents.approve', $document->id) }}"
                                        data-method="PUT"
                                        data-button-text="{{ ui_t('actions.confirm') }}"
                                        data-title="{{ ui_t('pages.documents.approve_title') }}"
                                        data-body="{{ ui_t('pages.documents.approve_body') }}"
                                        data-button-class="btn-success"
                                    >
                                        {{ __('Approuver') }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                    @endif

                    @if(($document->status ?? null) === 'en_relecture' && $document->reviewers()->where('reviewer_id', auth()->id())->where('status', 'pending')->exists())
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex gap-2 justify-content-end">
                                <button
                                    class="btn btn-sm btn-outline-danger trigger-action"
                                    data-id="{{ $document->id }}"
                                    data-name="{{ $document->title }}"
                                    data-url="{{ route('documents.reviewer-reject', $document->id) }}"
                                    data-method="PUT"
                                    data-button-text="{{ ui_t('actions.confirm') }}"
                                    data-title="{{ __('Rejeter le document') }}"
                                    data-body="{{ __('Merci de préciser le motif du rejet.') }}"
                                    data-extra-fields='{{ json_encode([["type"=>"textarea","name"=>"reject_reason","label"=>__("Motif du rejet"),"required"=>true,"rows"=>4,"maxlength"=>5000,"placeholder"=>__("Décrivez la raison du rejet")]], JSON_UNESCAPED_UNICODE) }}'
                                >
                                    {{ __('Rejeter') }}
                                </button>
                                <button
                                    class="btn btn-sm btn-outline-success trigger-action"
                                    data-id="{{ $document->id }}"
                                    data-name="{{ $document->title }}"
                                    data-url="{{ route('documents.reviewer-validate', $document->id) }}"
                                    data-method="PUT"
                                    data-button-text="{{ ui_t('actions.confirm') }}"
                                    data-title="{{ __('Valider le document') }}"
                                    data-body="{{ __('Confirmer la validation de ce document ?') }}"
                                    data-button-class="btn-success"
                                >
                                    {{ __('Valider') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(($document->status ?? null) === 'attente_archivage' && (int) $document->created_by === (int) auth()->id())
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex gap-2 justify-content-end align-items-center">
                                <span class="text-muted small me-auto">{{ __('Une fois le document rangé physiquement, confirmez son archivage.') }}</span>
                                <button
                                    class="btn btn-sm btn-success trigger-action"
                                    data-id="{{ $document->id }}"
                                    data-name="{{ $document->title }}"
                                    data-url="{{ route('documents.confirm-archive', $document->id) }}"
                                    data-method="PUT"
                                    data-button-text="{{ ui_t('actions.confirm') }}"
                                    data-title="{{ __('Confirmer l\'archivage') }}"
                                    data-body="{{ __('Confirmez-vous que ce document a été rangé physiquement en salle d\'archive ?') }}"
                                    data-button-class="btn-success"
                                >
                                    {{ __('Confirmer l\'archivage') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($isApprovalContext && ($prevApprovalUrl || $nextApprovalUrl))
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            @if($prevApprovalUrl)
                                <a href="{{ $prevApprovalUrl }}" class="btn btn-sm btn-outline-secondary" title="{{ $prevApprovalTitle }}">
                                    <i class="fa-solid fa-chevron-left me-1"></i>{{ __('Précédent') }}
                                </a>
                            @else
                                <span></span>
                            @endif
                            @if($nextApprovalUrl)
                                <a href="{{ $nextApprovalUrl }}" class="btn btn-sm btn-outline-secondary" title="{{ $nextApprovalTitle }}">
                                    {{ __('Suivant') }}<i class="fa-solid fa-chevron-right ms-1"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endunless
        </div>
    </div>

    @include('components.modals.move-documents-modal', ['document' => $document, 'doc' => $document, 'rooms' => \App\Models\Room::with('rows.shelves.boxes')->get()])
    @include('components.modals.confirm-modal')
@endsection
