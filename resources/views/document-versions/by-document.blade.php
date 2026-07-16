@extends('layouts.app')

@section('content')
    @php
        $latest = $document->latestVersion;
        $fileUrl = $latest ? route('documents.versions.file', ['id' => $latest->id]) : null;
        $st = is_string($document->status) ? $document->status : ($document->status?->value ?? '');
        $heroSub = collect([
            $document->department?->name,
            $document->subcategory?->name,
            ui_t('pages.documents.status.' . $st),
        ])->filter()->implode(' · ');
    @endphp

    <div class="container-fluid px-3 px-md-4 pb-5 lgv2-document-detail">
        <x-page-hero class="mt-3" :title="$document->title" :subtitle="$heroSub">
            <x-slot:actions>
                @if($latest)
                    <a href="{{ route('document-versions.preview', $latest->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-up-right-and-down-left-from-center me-1" aria-hidden="true"></i>{{ ui_t('pages.document_detail.full_preview') }}
                    </a>
                @endif
                <a href="{{ route('documents.all') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-list me-1" aria-hidden="true"></i>{{ ui_t('nav.documents') }}
                </a>
            </x-slot:actions>
        </x-page-hero>

        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="card border shadow-sm h-100 lgv2-document-preview-card">
                    <div class="card-header bg-white py-2 border-bottom">
                        <span class="lgv2-label-caps d-block">{{ ui_t('pages.document_detail.preview') }}</span>
                    </div>
                    <div class="card-body p-0 lgv2-document-preview-frame">
                        @if($latest && $fileUrl)
                            @if($latest->file_type === 'pdf')
                                <iframe
                                    src="{{ $fileUrl }}"
                                    title="{{ ui_t('pages.versions.pdf_preview') }}"
                                    class="w-100 border-0 d-block"
                                    style="min-height: min(72vh, 720px);"
                                ></iframe>
                            @elseif($latest->file_type === 'image')
                                <div class="p-2 text-center bg-light">
                                    <img src="{{ $fileUrl }}" alt="{{ $document->title }}" class="img-fluid rounded" style="max-height: min(72vh, 720px);" />
                                </div>
                            @else
                                <div class="p-5 text-center text-muted">
                                    <p class="mb-3">{{ ui_t('pages.document_detail.embedded_unavailable') }}</p>
                                    <a href="{{ route('document-versions.preview', $latest->id) }}" class="btn btn-upload btn-sm">{{ ui_t('pages.document_detail.full_preview') }}</a>
                                </div>
                            @endif
                        @else
                            <div class="p-5 text-center text-muted">{{ ui_t('pages.document_detail.no_version') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border shadow-sm mb-3 lgv2-document-side">
                    <div class="card-header bg-white py-2 border-bottom">
                        <span class="lgv2-label-caps d-block">{{ ui_t('pages.document_detail.metadata') }}</span>
                    </div>
                    <div class="card-body small">
                        <dl class="row mb-0 gy-2">
                            <dt class="col-sm-4 text-muted">{{ ui_t('tables.status') }}</dt>
                            <dd class="col-sm-8 mb-0">
                                <span class="status-badge {{ $st }} px-2 py-1 rounded-pill small">{{ ui_t('pages.documents.status.' . $st) }}</span>
                            </dd>
                            <dt class="col-sm-4 text-muted">{{ ui_t('pages.versions.creation_date') }}</dt>
                            <dd class="col-sm-8 mb-0">{{ $document->created_at?->format('d/m/Y H:i') }}</dd>
                            @if($document->expire_at)
                                <dt class="col-sm-4 text-muted">{{ ui_t('pages.versions.expire_date') }}</dt>
                                <dd class="col-sm-8 mb-0">{{ $document->expire_at->format('d/m/Y') }}</dd>
                            @endif
                            <dt class="col-sm-4 text-muted">{{ ui_t('pages.document_detail.structure') }}</dt>
                            <dd class="col-sm-8 mb-0">{{ $document->department?->name ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">{{ ui_t('pages.document_detail.service') }}</dt>
                            <dd class="col-sm-8 mb-0">{{ $document->service?->name ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">{{ ui_t('pages.versions.subcategory') }}</dt>
                            <dd class="col-sm-8 mb-0">{{ $document->subcategory?->name ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">{{ ui_t('pages.versions.physical_location') }}</dt>
                            <dd class="col-sm-8 mb-0">
                                @if($document->box)
                                    <span class="text-break">{{ $document->box->__toString() }}</span>
                                @else
                                    —
                                @endif
                            </dd>
                            @if($document->boxFolder)
                            <dt class="col-sm-4 text-muted">{{ __('Nom de boîte') }}</dt>
                            <dd class="col-sm-8 mb-0">
                                <span class="text-break">{{ $document->boxFolder->name }}</span>
                            </dd>
                            @endif
                            <dt class="col-sm-4 text-muted">{{ ui_t('pages.document_detail.hash') }}</dt>
                            <dd class="col-sm-8 mb-0 font-monospace text-break">{{ $document->file_hash ?: ui_t('pages.document_detail.hash_empty') }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card border shadow-sm lgv2-document-side">
                    <div class="card-header bg-white py-2 border-bottom">
                        <span class="lgv2-label-caps d-block">{{ ui_t('pages.document_detail.timeline') }}</span>
                    </div>
                    <div class="card-body py-3">
                        <x-document-approval-timeline :document="$document" />
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <h2 class="h6 text-uppercase text-muted fw-bold letter-spacing mb-3">{{ ui_t('pages.document_detail.versions_section') }}</h2>
            <livewire:documents-version-table :documentId="$document->id" />
        </div>
    </div>
@endsection
