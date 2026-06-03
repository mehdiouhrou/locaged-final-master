@extends('layouts.app')

@section('content')
    @php
        $document = $document ?? ($doc->document ?? null);
        $isDestructionContext = request()->boolean('destruction');
        $status = (string) ($document->status ?? '');
        $isBorrowed = isset($currentLoan) && $currentLoan;
        $statusBadgeClass = match ($status) {
            'approved' => 'bg-success-subtle text-success-emphasis',
            'pending' => 'bg-warning-subtle text-warning-emphasis',
            'declined' => 'bg-danger-subtle text-danger-emphasis',
            'expired' => 'bg-secondary-subtle text-secondary-emphasis',
            default => 'bg-light text-dark',
        };
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
                    <x-approval-progress :document="$document" />
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

                                <dt class="col-6 text-muted">{{ __('Catégorie') }}</dt>
                                <dd class="col-6 mb-0">{{ $document->category?->name ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Sous-catégorie') }}</dt>
                                <dd class="col-6 mb-0">{{ $document->subcategory?->name ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Emplacement physique') }}</dt>
                                <dd class="col-6 mb-0 text-break">@if($document->box){{ $document->box->__toString() }}@elseif($document->isDigitalOnly()){{ __('pages.documents.digital_only_location') }}@else—@endif</dd>

                                <dt class="col-6 text-muted">{{ __('Empreinte (SHA-256)') }}</dt>
                                <dd class="col-6 mb-0 text-break">{{ $document->file_hash ?? 'Non calculée' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Tags') }}</dt>
                                <dd class="col-6 mb-0 text-break">
                                    @php
                                        $tagNames = $document->tags?->pluck('name')->filter()->values() ?? collect();
                                    @endphp
                                    {{ $tagNames->isNotEmpty() ? $tagNames->join(', ') : '—' }}
                                </dd>

                                <dt class="col-6 text-muted">{{ __('Mots-clés') }}</dt>
                                <dd class="col-6 mb-0 text-break">
                                    @php
                                        $rawKeywords = data_get($document->metadata, 'keywords');
                                        if (is_array($rawKeywords)) {
                                            $keywordsText = collect($rawKeywords)->filter()->implode(', ');
                                        } elseif (is_string($rawKeywords)) {
                                            $keywordsText = trim($rawKeywords);
                                        } else {
                                            $keywordsText = '';
                                        }
                                    @endphp
                                    {{ $keywordsText !== '' ? $keywordsText : '—' }}
                                </dd>
                            </dl>
                        </div>
                    </div>

                    @php
                        $meta = is_array($document->metadata)
                            ? $document->metadata
                            : json_decode($document->metadata, true);
                        $meta = $meta ?? [];
                        $isPayment = !empty($meta['type']) && $meta['type'] === 'payment';

                        // Checklist déclarée par l'uploadeur
                        $declaredChecklist = isset($meta['checklist']) && is_array($meta['checklist'])
                            ? $meta['checklist']
                            : [];

                        // Pièces attendues selon la catégorie
                        $catNamePreview = strtolower($document->category?->name ?? '');
                        $isFournisseurPrev = str_contains($catNamePreview, 'fournisseur') || str_contains($catNamePreview, 'prestataire');
                        $isCaissePrev      = str_contains($catNamePreview, 'caisse');
                        $isPayePrev        = str_contains($catNamePreview, 'paie');
                        if ($isFournisseurPrev) {
                            $expectedPieces = ["Demande d'achat", "Bon de commande", "Bon de livraison", "État de réception système", "Facture", "Attestation RIB", "Attestation fiscale", "Ordre de paiement"];
                        } elseif ($isCaissePrev) {
                            $expectedPieces = ["Bulletin de caisse", "État récapitulatif encaissements", "Ordre de virement"];
                        } elseif ($isPayePrev) {
                            $expectedPieces = ["Livre de paie", "Bulletin de paie", "État CNSS/AMO", "État de pointage", "Ordre de virement", "Décision RH"];
                        } else {
                            $expectedPieces = [];
                        }
                        $declaredCount = count($declaredChecklist);
                        $totalExpected = count($expectedPieces);
                    @endphp
                    @if($isPayment)
                    <div class="card border-0 shadow-sm mb-3 border-start border-4 border-primary">
                        <div class="card-header bg-primary bg-opacity-10 py-2">
                            <h6 class="mb-0 text-uppercase text-primary small fw-bold">
                                <i class="fa-solid fa-money-bill-wave me-1"></i>{{ __('Informations de paiement') }}
                            </h6>
                        </div>
                        <div class="card-body small">
                            <dl class="row mb-0 gy-2">
                                <dt class="col-6 text-muted">{{ __('Fournisseur') }}</dt>
                                <dd class="col-6 mb-0">{{ $meta['supplier'] ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('N° de compte') }}</dt>
                                <dd class="col-6 mb-0 text-break">{{ $meta['account_number'] ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Montant') }}</dt>
                                <dd class="col-6 mb-0 fw-semibold">
                                    @if($document->amount !== null)
                                        {{ number_format((float) $document->amount, 2, ',', ' ') }} DH
                                    @else
                                        —
                                    @endif
                                </dd>

                                <dt class="col-6 text-muted">{{ __('Période') }}</dt>
                                <dd class="col-6 mb-0">{{ $meta['period'] ?? '—' }}</dd>

                                <dt class="col-6 text-muted">{{ __('Motif') }}</dt>
                                <dd class="col-6 mb-0 text-break">{{ $meta['reason'] ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    @if(!empty($expectedPieces))
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-uppercase text-muted small fw-bold">
                                <i class="fa-solid fa-list-check me-1"></i>{{ __('Pièces déclarées') }}
                            </h6>
                            <span class="badge {{ $declaredCount >= $totalExpected ? 'bg-success' : ($declaredCount > 0 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                {{ $declaredCount }} / {{ $totalExpected }} déclarées
                            </span>
                        </div>
                        <div class="card-body py-2 small">
                            <ul class="list-unstyled mb-0">
                                @foreach($expectedPieces as $piece)
                                    @php $checked = in_array($piece, $declaredChecklist); @endphp
                                    <li class="py-1 border-bottom d-flex align-items-center gap-2">
                                        @if($checked)
                                            <i class="fa-solid fa-square-check text-success"></i>
                                        @else
                                            <i class="fa-regular fa-square text-muted"></i>
                                        @endif
                                        <span class="{{ $checked ? 'fw-semibold' : 'text-muted' }}">{{ $piece }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                    @endif

                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 text-uppercase text-muted small fw-bold">{{ __('Historique des statuts') }}</h6>
                        </div>
                        <div class="card-body py-3">
                            <x-document-approval-timeline :document="$document" />
                        </div>
                    </div>

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

                                @can('create', \App\Models\DocumentMovement::class)
                                    <form method="POST" action="{{ route('documents.return', $document) }}" class="mb-2">
                                        @csrf
                                        <label class="form-label small mb-1">{{ __('Note de retour (optionnel)') }}</label>
                                        <textarea name="return_note" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('État du dossier au retour') }}"></textarea>
                                        <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                            <i class="fa-solid fa-box-open me-1"></i>{{ __('Marquer comme retourné') }}
                                        </button>
                                    </form>
                                @endcan
                            @else
                                @can('create', \App\Models\DocumentMovement::class)
                                    <form method="POST" action="{{ route('documents.borrow', $document) }}" class="mb-2">
                                        @csrf
                                        <label class="form-label small mb-1">{{ __('Nom de l’emprunteur') }}</label>
                                        <input type="text" name="borrower_name" class="form-control form-control-sm mb-2" required placeholder="{{ __('Ex: Nom / Service externe') }}">
                                        <label class="form-label small mb-1">{{ __('Date prévue de retour (optionnel)') }}</label>
                                        <input type="datetime-local" name="due_at" class="form-control form-control-sm mb-2">
                                        <label class="form-label small mb-1">{{ __('Motif (optionnel)') }}</label>
                                        <textarea name="movement_note" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('Pourquoi ce dossier est emprunté ?') }}"></textarea>
                                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                                            <i class="fa-solid fa-hand me-1"></i>{{ __('Enregistrer un emprunt') }}
                                        </button>
                                    </form>
                                @else
                                    <p class="text-muted mb-0">{{ __('Vous n’avez pas les droits pour enregistrer un emprunt.') }}</p>
                                @endcan
                            @endif

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
                        </div>
                    </div>

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

    @include('components.modals.confirm-modal')
@endsection
