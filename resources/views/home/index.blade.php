@extends('layouts.app')

@section('content')
    @php
        $dashDate = \Carbon\Carbon::now()->locale(str_replace('_', '-', app()->getLocale()))->isoFormat('dddd D MMMM YYYY');
        $dashUser = auth()->user();
        $dashDept = $dashUser?->departments?->first();
        $dashSubtitle = $dashDept ? $dashDate . ' • ' . $dashDept->name : $dashDate;
    @endphp
    <div class="mt-3 position-relative mb-5 px-3 px-md-0 lgv2-dashboard-home">
        <x-page-hero :title="ui_t('nav.dashboard')" :subtitle="$dashSubtitle" />

        <div class="d-flex flex-column flex-lg-row flex-lg-wrap align-items-start justify-content-lg-between gap-3 mb-4">
            <p class="text-muted small mb-0">{{ ui_t('pages.dashboard.welcome') }}, <span class="text-dark fw-semibold">{{ auth()->user()->full_name }}</span></p>
        </div>

        <!-- KPI -->
        <div class="overview-section mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="mb-0">{{ ui_t('pages.dashboard.overview') }}</h2>
            </div>
            @include('components.stats-cards')
        </div>

        <!-- Dossiers accessibles + Statistiques -->
        <div class="row g-4 align-items-stretch lgv2-dashboard-main-row mt-1">
            <div class="col-lg-6 col-md-12 d-flex">
                <div class="left-column flex-grow-1 w-100">
                    @include('components.dashboard-accessible-categories', ['categories' => $categories])
                </div>
            </div>
            <div class="col-lg-6 col-md-12 d-flex">
                <div class="w-100 d-flex flex-column">
                    @include('components.doc-types-donut')
                </div>
            </div>
        </div>

        <!-- Types + stockage physique (statuts) -->
        <div class="row g-4 align-items-stretch mt-1">
            <div class="col-lg-6 col-md-12 d-flex">
                <div class="w-100">@include('components.documents-chart')</div>
            </div>
            <div class="col-lg-6 col-md-12 d-flex">
                <div class="w-100">@include('components.physical-storage-status-cards')</div>
            </div>
        </div>

    </div>
@endsection
