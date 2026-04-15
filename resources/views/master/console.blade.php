@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-md-4 mt-4 mb-5">
               <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">{{ __('pages.master_console.title') }}</h1>
            <p class="text-muted mb-0">{{ __('pages.master_console.subtitle') }}</p>
        </div>

        {{-- Aperçu : tout voir d’un coup sur la même page --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 position-relative">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">{{ __('pages.master_console.overview_users') }}</div>
                        <div class="h4 mb-0 fw-bold">
                            @if($maxUsers > 0)
                                {{ number_format($userCount) }} <span class="fs-6 text-muted fw-normal">/ {{ number_format($maxUsers) }}</span>
                            @else
                                {{ number_format($userCount) }} <span class="fs-6 text-muted fw-normal">{{ __('pages.master_console.overview_users_unlimited') }}</span>
                            @endif
                        </div>
                        @can('viewAny', \App\Models\User::class)
                            <a href="{{ route('users.index') }}" class="small stretched-link text-decoration-none">{{ __('pages.master_console.overview_manage_users') }}</a>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 position-relative">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">{{ __('pages.master_console.overview_org') }}</div>
                        <div class="fw-semibold text-truncate" title="{{ $orgRootName }}">{{ $orgRootName }}</div>
                        <div class="text-muted small mt-2 mb-0">
                            <span class="text-uppercase fw-semibold">{{ __('pages.master_console.overview_timezone') }}</span>
                            <span class="font-monospace">{{ $appTimezone }}</span>
                        </div>
                        <a href="#master-localization" class="small stretched-link text-decoration-none">{{ __('pages.master_console.overview_edit_below') }}</a>
                    </div>
                </div>
            </div>
            @if($ocrOverview)
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 position-relative">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">{{ __('pages.master_console.overview_ocr') }}</div>
                        <div class="d-flex gap-3">
                            <div>
                                <div class="h4 mb-0 fw-bold">{{ number_format($ocrOverview['active']) }}</div>
                                <div class="small text-muted">{{ __('pages.master_console.overview_ocr_active') }}</div>
                            </div>
                            <div>
                                <div class="h4 mb-0 fw-bold text-danger">{{ number_format($ocrOverview['failed']) }}</div>
                                <div class="small text-muted">{{ __('pages.master_console.overview_ocr_failed') }}</div>
                            </div>
                        </div>
                        <a href="#master-ocr" class="small stretched-link text-decoration-none">{{ __('pages.master_console.overview_ocr_table') }}</a>
                    </div>
                </div>
            </div>
            @endif
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-2">{{ __('pages.master_console.overview_branding') }}</div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            @php
                                $hdr = \App\Support\Branding::headerLogoUrl();
                                $login = \App\Support\Branding::resolveLoginCoverUrl();
                            @endphp
                            <img src="{{ $hdr }}" alt="" class="rounded border bg-white" style="height: 36px; width: auto; max-width: 72px; object-fit: contain;" />
                            <img src="{{ $login }}" alt="" class="rounded border bg-light" style="height: 36px; width: 48px; object-fit: cover;" />
                        </div>
                        <a href="#master-localization" class="small text-decoration-none">{{ __('pages.master_console.overview_branding_hint') }}</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Liens rapides (hors onglets scroll) --}}
        <div class="row g-3 mb-4">
            @if(auth()->user()?->can('view organization wide reports') || auth()->user()?->can('view any role'))
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('storage.overview') }}" class="card border-0 shadow-sm text-decoration-none text-dark h-100 p-3 hover-shadow">
                        <div class="fw-semibold small"><i class="fa-solid fa-hard-drive me-2 text-primary"></i>{{ __('pages.master_console.link_storage') }}</div>
                    </a>
                </div>
            @endif
            @if(auth()->user()?->can('viewHorizon'))
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ url('/horizon') }}" target="_blank" rel="noopener noreferrer" class="card border-0 shadow-sm text-decoration-none text-dark h-100 p-3">
                        <div class="fw-semibold small"><i class="fa-solid fa-gauge-high me-2 text-primary"></i>Horizon</div>
                    </a>
                </div>
            @endif
            @can('viewAny', \App\Models\DestructionCertificate::class)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('destruction-certificates.index') }}" class="card border-0 shadow-sm text-decoration-none text-dark h-100 p-3">
                        <div class="fw-semibold small"><i class="fa-solid fa-file-shield me-2 text-primary"></i>{{ __('pages.destruction_certificates.registry_link') }}</div>
                    </a>
                </div>
            @endcan
        </div>

        <nav class="navbar navbar-expand-lg rounded-3 border bg-white px-3 py-2 mb-4 shadow-sm sticky-top" style="top: 0.5rem; z-index: 1020;">
            <span class="navbar-text small fw-semibold text-muted me-3 d-none d-md-inline">{{ __('pages.master_console.jump') }}</span>
            <ul class="navbar-nav flex-row flex-wrap gap-1 gap-md-2">
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 rounded-2 small" href="#master-roles">{{ ui_t('nav.roles') }}</a>
                </li>
                @if($ocrJobs)
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 rounded-2 small" href="#master-ocr">{{ ui_t('nav.ocr') }}</a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 rounded-2 small" href="#master-localization">{{ ui_t('nav.localization') }}</a>
                </li>
            </ul>
        </nav>

        <section id="master-roles" class="card border-0 shadow-sm mb-5 scroll-margin-top">
            <div class="card-body p-4">
                @include('master.partials.roles', ['roles' => $roles])
            </div>
        </section>

        @if($ocrJobs)
        <section id="master-ocr" class="card border-0 shadow-sm mb-5 scroll-margin-top">
            <div class="card-body p-4">
                @include('master.partials.ocr', ['ocrJobs' => $ocrJobs])
            </div>
        </section>
        @endif

        <section id="master-localization" class="card border-0 shadow-sm mb-5 scroll-margin-top">
            <div class="card-body p-4">
                @include('master.partials.localization')
            </div>
        </section>
    </div>

    @include('components.modals.confirm-modal')

    <style>
        .scroll-margin-top { scroll-margin-top: 6rem; }
        a.card.hover-shadow:hover { box-shadow: 0 .35rem 1rem rgba(0,0,0,.08) !important; }
    </style>
    <script>
 document.addEventListener('DOMContentLoaded', function () {
            var p = new URLSearchParams(window.location.search);
            var id = p.has('roles_page') ? 'master-roles' : (p.has('ocr_page') ? 'master-ocr' : null);
            if (id) document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
@endsection
