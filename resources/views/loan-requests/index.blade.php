@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h3 class="mb-0">{{ __('Gestion des emprunts') }}</h3>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            @php
                $statusFilters = [
                    '' => __('Toutes'),
                    'requested' => __('Demandees'),
                    'approved' => __('Approuvees'),
                    'picked_up' => __('En cours'),
                    'returned' => __('Retournees'),
                    'rejected' => __('Refusees'),
                ];
                $currentStatus = request('status', '');
            @endphp
            @foreach($statusFilters as $value => $label)
                <a href="{{ route('loan-requests.index', $value !== '' ? ['status' => $value] : []) }}"
                   class="btn btn-sm {{ $currentStatus === $value ? 'btn-dark' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 220px;">{{ __('Cible') }}</th>
                                <th style="min-width: 320px;">{{ __('Emplacement physique') }}</th>
                                <th style="min-width: 140px;">{{ __('Demandeur') }}</th>
                                <th style="min-width: 200px;">{{ __('Motif') }}</th>
                                <th style="min-width: 130px;">{{ __('Date de demande') }}</th>
                                <th style="min-width: 100px;">{{ __('Statut') }}</th>
                                <th style="min-width: 110px;">{{ __('Echeance') }}</th>
                                <th style="min-width: 220px;" class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loanRequests as $loanRequest)
                                <tr>
                                    <td>
                                        @if($loanRequest->isForDocument())
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file text-secondary me-2"></i>
                                                @if($loanRequest->document && $loanRequest->document->latestVersion)
                                                    <a href="{{ route('document-versions.preview', ['id' => $loanRequest->document->latestVersion->id]) }}"
                                                       class="text-truncate" style="max-width: 180px;" title="{{ $loanRequest->document->title }}">
                                                        {{ $loanRequest->document->title }}
                                                    </a>
                                                @else
                                                    <span class="text-truncate" style="max-width: 180px;">
                                                        {{ $loanRequest->document?->title ?? __('Document supprime') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box text-secondary me-2"></i>
                                                <div class="text-truncate" style="max-width: 180px;" title="{{ (string) $loanRequest->box }}">
                                                    {{ $loanRequest->box?->name ?? __('Boite supprimee') }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($loanRequest->isForDocument() && $loanRequest->document?->box)
                                            <span>{{ (string) $loanRequest->document->box }}</span>
                                        @elseif($loanRequest->isForBox() && $loanRequest->box)
                                            <span>{{ (string) $loanRequest->box }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $loanRequest->requestedBy?->full_name ?? '-' }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $loanRequest->reason }}">
                                            {{ $loanRequest->reason }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $loanRequest->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td>
                                        @php
                                            $statusLabels = [
                                                'requested' => ['label' => __('Demande'), 'class' => 'bg-warning text-dark'],
                                                'approved' => ['label' => __('Approuvee'), 'class' => 'bg-info text-dark'],
                                                'rejected' => ['label' => __('Refusee'), 'class' => 'bg-danger'],
                                                'picked_up' => ['label' => __('En cours'), 'class' => 'bg-primary'],
                                                'returned' => ['label' => __('Retournee'), 'class' => 'bg-success'],
                                                'overdue' => ['label' => __('En retard'), 'class' => 'bg-danger'],
                                            ];
                                            $statusInfo = $statusLabels[$loanRequest->status] ?? ['label' => $loanRequest->status, 'class' => 'bg-secondary'];
                                        @endphp
                                        <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                    </td>
                                    <td>
                                        @if($loanRequest->due_at)
                                            <span class="{{ $loanRequest->due_at->isPast() && $loanRequest->status === 'picked_up' ? 'text-danger fw-bold' : '' }}">
                                                {{ $loanRequest->due_at->format('Y-m-d') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                                            @can('approve', $loanRequest)
                                                @if($loanRequest->status === 'requested')
                                                <button
                                                    data-id="{{ $loanRequest->id }}"
                                                    data-url="{{ route('loan-requests.approve', $loanRequest) }}"
                                                    class="btn btn-sm btn-success trigger-action"
                                                    data-method="PUT"
                                                    data-button-text="{{ __('Approuver') }}"
                                                    data-title="{{ __('Approuver la demande') }}"
                                                    data-body="{{ __('Confirmer l\'approbation de cette demande d\'emprunt ?') }}"
                                                    data-button-class="btn-success">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                @endif
                                            @endcan

                                            @can('decline', $loanRequest)
                                                @if($loanRequest->status === 'requested')
                                                <button
                                                    data-id="{{ $loanRequest->id }}"
                                                    data-url="{{ route('loan-requests.reject', $loanRequest) }}"
                                                    class="btn btn-sm btn-danger trigger-action"
                                                    data-method="PUT"
                                                    data-title="{{ __('Refuser la demande') }}"
                                                    data-body="{{ __('Veuillez indiquer le motif du refus.') }}"
                                                    data-button-text="{{ __('Refuser') }}"
                                                    data-extra-fields='[
                                                        {"type":"textarea","name":"rejection_reason","label":"{{ __('Motif du refus') }}","required":true}
                                                    ]'
                                                    data-button-class="btn-danger">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                                @endif
                                            @endcan

                                            @can('process', $loanRequest)
                                                @if($loanRequest->status === 'approved')
                                                    <button
                                                        data-id="{{ $loanRequest->id }}"
                                                        data-url="{{ route('loan-requests.pick-up', $loanRequest) }}"
                                                        class="btn btn-sm btn-primary trigger-action"
                                                        data-method="PUT"
                                                        data-button-text="{{ __('Marquer retire') }}"
                                                        data-title="{{ __('Retrait physique') }}"
                                                        data-body="{{ __('Confirmer que le document/la boite a ete remis(e) physiquement ?') }}"
                                                        data-button-class="btn-primary">
                                                        <i class="fa-solid fa-hand"></i>
                                                    </button>
                                                @endif

                                                @if($loanRequest->status === 'picked_up')
                                                    <button
                                                        data-id="{{ $loanRequest->id }}"
                                                        data-url="{{ route('loan-requests.return', $loanRequest) }}"
                                                        class="btn btn-sm btn-outline-success trigger-action"
                                                        data-method="PUT"
                                                        data-button-text="{{ __('Marquer retourne') }}"
                                                        data-title="{{ __('Retour physique') }}"
                                                        data-body="{{ __('Confirmer le retour physique.') }}"
                                                        data-extra-fields='[
                                                            {"type":"textarea","name":"return_note","label":"{{ __('Note de retour (optionnel)') }}","required":false}
                                                        ]'
                                                        data-button-class="btn-outline-success">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                    </button>
                                                @endif
                                            @endcan

                                            @if(in_array($loanRequest->status, ['rejected', 'returned']))
                                                <span class="text-muted small align-self-center">{{ __('Terminee') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        {{ __('Aucune demande d\'emprunt.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    <x-pagination :items="$loanRequests" />
                </div>
            </div>
        </div>
    </div>

    @include('components.modals.confirm-modal')
@endsection
