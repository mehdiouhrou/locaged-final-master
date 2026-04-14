@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">{{ __('pages.destruction_certificates.title') }}</h3>
                <p class="text-muted small mb-0">{{ __('pages.destruction_certificates.subtitle') }}</p>
            </div>
            <a href="{{ route('documents.destructions') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>{{ __('pages.destruction_certificates.back_expired') }}
            </a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pages.destruction_certificates.col_reference') }}</th>
                                <th>{{ __('pages.destruction_certificates.col_document') }}</th>
                                <th>{{ __('pages.destruction_certificates.col_approved_by') }}</th>
                                <th class="text-center">{{ __('pages.destruction_certificates.col_pv') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($certificates as $certificate)
                                @php
                                    $doc = $certificate->document;
                                @endphp
                                <tr>
                                    <td>
                                        <code class="small">{{ $certificate->public_id }}</code>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 280px;" title="{{ $doc?->title ?? '—' }}">
                                            {{ $doc?->title ?? __('pages.destruction_certificates.document_unknown') }}
                                        </div>
                                        @if($doc)
                                            <div class="text-muted small">
                                                UID: {{ $doc->uid ?? '—' }}
                                                @if($doc->trashed())
                                                    <span class="badge bg-secondary ms-1">{{ __('pages.destruction_certificates.soft_deleted') }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $certificate->approvedByUser?->full_name ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @can('view', $certificate)
                                            <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                                <a href="{{ route('destruction-certificates.download', $certificate) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="{{ __('pages.destruction_certificates.download_pdf') }}">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                                <a href="{{ route('destruction-certificates.proof.verify', $certificate) }}"
                                                   class="btn btn-sm btn-outline-dark"
                                                   title="{{ __('pages.destruction_certificates.verify_proof') }}">
                                                    <i class="fa-solid fa-shield-check"></i>
                                                </a>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        {{ __('pages.destruction_certificates.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($certificates->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $certificates->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
