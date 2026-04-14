@extends('layouts.app')

@section('content')
    @php
        $signatureStatus = data_get($verification, 'signature_status.status', 'unknown');
        $signatureBadge = match ($signatureStatus) {
            'valid' => 'bg-success-subtle text-success-emphasis',
            'invalid' => 'bg-danger-subtle text-danger-emphasis',
            default => 'bg-warning-subtle text-warning-emphasis',
        };
        $archiveStatus = data_get($verification, 'immutable_archive.status', 'skipped');
        $archiveBadge = match ($archiveStatus) {
            'archived' => 'bg-success-subtle text-success-emphasis',
            'failed' => 'bg-danger-subtle text-danger-emphasis',
            default => 'bg-secondary-subtle text-secondary-emphasis',
        };
    @endphp

    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1">{{ __('Vérifier la preuve légale de destruction') }}</h4>
                <p class="text-muted mb-0">{{ __('PV') }} #{{ $certificate->public_id }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('destruction-certificates.download', $certificate) }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-pdf me-1"></i>{{ __('Télécharger le PV') }}
                </a>
                @if($certificate->proof_package_path)
                    <a href="{{ route('destruction-certificates.proof.download', $certificate) }}" class="btn btn-outline-dark">
                        <i class="fa-solid fa-file-zipper me-1"></i>{{ __('Package signé') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>{{ __('Intégrité des hashes') }}</strong></div>
                    <div class="card-body small">
                        <div class="mb-2">
                            <div class="text-muted">{{ __('Hash PDF (manifest)') }}</div>
                            <code class="text-break">{{ data_get($verification, 'manifest_pdf_hash') ?: '—' }}</code>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted">{{ __('Hash PDF (recalculé)') }}</div>
                            <code class="text-break">{{ data_get($verification, 'current_pdf_hash') ?: '—' }}</code>
                        </div>
                        <div class="mb-2">
                            @if(data_get($verification, 'pdf_hash_matches'))
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ __('Hash PDF conforme') }}</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">{{ __('Hash PDF non conforme') }}</span>
                            @endif
                        </div>
                        <hr>
                        <div class="mb-2">
                            <div class="text-muted">{{ __('Hash package (stocké)') }}</div>
                            <code class="text-break">{{ data_get($verification, 'stored_package_hash') ?: '—' }}</code>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted">{{ __('Hash package (recalculé)') }}</div>
                            <code class="text-break">{{ data_get($verification, 'current_package_hash') ?: '—' }}</code>
                        </div>
                        <div>
                            @if(data_get($verification, 'package_hash_matches'))
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ __('Hash package conforme') }}</span>
                            @else
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">{{ __('Hash package non vérifié / non conforme') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>{{ __('Signature & archivage immuable') }}</strong></div>
                    <div class="card-body small">
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <span class="text-muted">{{ __('Signature') }}:</span>
                            <span class="badge rounded-pill {{ $signatureBadge }}">{{ strtoupper($signatureStatus) }}</span>
                        </div>
                        <div class="mb-3 text-muted">
                            {{ data_get($verification, 'signature_status.message') }}
                        </div>

                        <div class="mb-2 d-flex align-items-center gap-2">
                            <span class="text-muted">{{ __('Archivage WORM') }}:</span>
                            <span class="badge rounded-pill {{ $archiveBadge }}">{{ strtoupper($archiveStatus) }}</span>
                        </div>
                        <div class="mb-1"><span class="text-muted">{{ __('Disk') }}:</span> {{ data_get($verification, 'immutable_archive.disk') ?: '—' }}</div>
                        <div class="mb-1"><span class="text-muted">{{ __('Chemin immuable') }}:</span> <code>{{ data_get($verification, 'immutable_archive.path') ?: '—' }}</code></div>
                        <div class="mb-1"><span class="text-muted">{{ __('Rétention jusqu’au') }}:</span> {{ data_get($verification, 'immutable_archive.retain_until') ?: '—' }}</div>
                        @if(data_get($verification, 'immutable_archive.message'))
                            <div class="mt-2 text-danger">{{ data_get($verification, 'immutable_archive.message') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white"><strong>{{ __('Présence des artefacts') }}</strong></div>
            <div class="card-body small d-flex flex-wrap gap-2">
                <span class="badge rounded-pill {{ data_get($verification, 'pdf_exists') ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }}">
                    {{ data_get($verification, 'pdf_exists') ? __('PV présent') : __('PV manquant') }}
                </span>
                <span class="badge rounded-pill {{ data_get($verification, 'package_exists') ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }}">
                    {{ data_get($verification, 'package_exists') ? __('Package présent') : __('Package manquant') }}
                </span>
                <span class="badge rounded-pill bg-light text-dark border">
                    {{ __('Preuve générée le') }} {{ $certificate->proof_generated_at?->format('d/m/Y H:i') ?? '—' }}
                </span>
            </div>
        </div>
    </div>
@endsection
