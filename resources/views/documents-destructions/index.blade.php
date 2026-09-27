@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h3 class="mb-0">{{ ui_t('pages.destructions.title') }}</h3>
            <div class="d-flex flex-wrap gap-2">
                @can('viewAny', \App\Models\DestructionCertificate::class)
                    <a href="{{ route('destruction-certificates.index') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-shield me-1"></i>{{ __('pages.destruction_certificates.registry_link') }}
                    </a>
                @endcan
                @can('viewAny', \App\Models\DocumentDestructionRequest::class)
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-arrow-up-from-bracket me-1"></i> {{ ui_t('pages.destructions.export') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('documents-destructions.export') }}"><i class="fas fa-file-excel me-2 text-success"></i>Excel (.xlsx)</a></li>
                            <li><a class="dropdown-item" href="{{ route('documents-destructions.export-pdf') }}"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF</a></li>
                        </ul>
                    </div>
                @endcan
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 220px;">{{ ui_t('pages.destructions.document_name') }}</th>
                                <th style="min-width: 120px;">{{ ui_t('pages.destructions.created_by') }}</th>
                                <th style="min-width: 110px;">{{ ui_t('pages.destructions.creation_date') }}</th>
                                <th style="min-width: 110px;">{{ ui_t('pages.destructions.expiration_date') }}</th>
                                <th style="min-width: 80px;">{{ ui_t('pages.destructions.status') }}</th>
                                <th style="min-width: 160px;" class="text-center">PV / Preuve</th>
                                <th style="min-width: 200px;" class="text-center">{{ ui_t('pages.destructions.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiredDocuments as $doc)
                                <tr>
                                    {{-- Document Name --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $iconClass = 'fas fa-file text-secondary';
                                                $ext = optional($doc->latestVersion)->file_path 
                                                    ? strtolower(pathinfo($doc->latestVersion->file_path, PATHINFO_EXTENSION)) 
                                                    : null;
                                                if ($ext) { $iconClass = getFileIcon($ext); }
                                            @endphp
                                            <i class="{{ $iconClass }} me-2" style="font-size: 20px;"></i>
                                            <div class="text-truncate" style="max-width: 180px;" title="{{ $doc->title }}">
                                                {{ $doc->title }}
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Created By --}}
                                    <td>
                                        <div class="text-truncate" style="max-width: 120px;" title="{{ $doc->createdBy?->full_name ?? '' }}">
                                            {{ $doc->createdBy?->full_name ?? '—' }}
                                        </div>
                                    </td>
                                    {{-- Creation Date --}}
                                    <td>
                                        {{ $doc->created_at?->format('Y-m-d') ?? '—' }}
                                    </td>
                                    {{-- Expiration Date --}}
                                    <td>
                                        <span class="text-danger fw-bold">
                                            {{ $doc->expire_at?->format('Y-m-d') ?? '—' }}
                                        </span>
                                    </td>
                                    {{-- Status --}}
                                    <td>
                                        <span class="badge bg-danger">
                                            {{ ui_t('pages.destructions.status_values.expired') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $pv = $doc->destructionCertificates->first();
                                        @endphp
                                        @if($pv && $pv->pdf_path)
                                            @can('view', $pv)
                                                <div class="d-inline-flex gap-1">
                                                    <a href="{{ route('destruction-certificates.download', $pv) }}"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="PDF">
                                                        <i class="fa-solid fa-file-pdf"></i>
                                                    </a>
                                                    <a href="{{ route('destruction-certificates.proof.verify', $pv) }}"
                                                       class="btn btn-sm btn-outline-dark"
                                                       title="{{ __('Vérifier la preuve') }}">
                                                        <i class="fa-solid fa-shield-check"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    {{-- Actions --}}
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                                            @can('view', $doc)
                                                @if($doc->latestVersion)
                                                    <a href="{{ route('document-versions.preview', ['id' => $doc->latestVersion->id, 'destruction' => 1]) }}"
                                                       class="btn btn-sm btn-outline-secondary"
                                                       title="{{ ui_t('pages.destructions.preview') }}">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                @endif
                                            @endcan

                                            @can('permanentDelete', $doc)
                                                <button
                                                    data-id="{{ $doc->id }}"
                                                    data-name="{{ $doc->title }}"
                                                    data-url="{{ route('documents.permanent-delete', ['id' => $doc->id]) }}"
                                                    class="btn btn-sm btn-danger trigger-action"
                                                    data-method="DELETE"
                                                    data-button-text="{{ ui_t('pages.actions.delete_permanently') }}"
                                                    data-title="{{ ui_t('pages.documents.permanent_delete_title') }}"
                                                    data-body="{{ ui_t('pages.documents.permanent_delete_body') }}"
                                                    data-button-class="btn-danger">
                                                    <i class="fa-solid fa-trash"></i> {{ ui_t('pages.destructions.delete_button') }}
                                                </button>
                                            @endcan

                                            @can('postpone', \App\Models\DocumentDestructionRequest::class)
                                                <button
                                                    data-id="{{ $doc->id }}"
                                                    data-name="{{ $doc->title }}"
                                                    data-url="{{ route('documents.postpone-expiration', ['documentId' => $doc->id]) }}"
                                                    class="btn btn-sm btn-warning trigger-action"
                                                    data-method="PUT"
                                                    data-button-text="{{ ui_t('pages.destructions.postpone.confirm_button') }}"
                                                    data-title="{{ ui_t('pages.destructions.postpone.title') }}"
                                                    data-body="{{ ui_t('pages.destructions.postpone.description') }}"
                                                    data-extra-fields='[
                                                        {
                                                            "type":"select",
                                                            "name":"unit",
                                                            "label":"{{ ui_t('pages.destructions.postpone.time_unit') }}",
                                                            "options":[
                                                                {"value":"days","text":"{{ ui_t('pages.destructions.postpone.days') }}"},
                                                                {"value":"weeks","text":"{{ ui_t('pages.destructions.postpone.weeks') }}"},
                                                                {"value":"months","text":"{{ ui_t('pages.destructions.postpone.months') }}"},
                                                                {"value":"years","text":"{{ ui_t('pages.destructions.postpone.years') }}"}
                                                            ],
                                                            "value":"days",
                                                            "required":true
                                                        },
                                                        {
                                                            "type":"number",
                                                            "name":"amount",
                                                            "placeholder":"{{ ui_t('pages.destructions.postpone.amount_placeholder') }}",
                                                            "label":"{{ ui_t('pages.destructions.postpone.amount_label') }}",
                                                            "value":"7",
                                                            "min":1,
                                                            "max":1000,
                                                            "required":true
                                                        }
                                                    ]'
                                                    data-button-class="btn-warning">
                                                    <i class="fas fa-clock"></i> {{ ui_t('pages.destructions.postpone_button') }}
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        {{ ui_t('pages.destructions.no_expired_documents') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    <x-pagination :items="$expiredDocuments" />
                </div>
            </div>
        </div>
    </div>

    @include('components.modals.confirm-modal')
@endsection
