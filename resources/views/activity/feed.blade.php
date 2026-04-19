@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-md-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h2 class="fw-bold mb-0">{{ __('Activité documentaire') }}</h2>
            @can('view system activity log')
                <a href="{{ route('users.logs') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="fa-solid fa-shield-halved me-1 opacity-75" aria-hidden="true"></i>{{ __('Journal d’audit système') }}
                </a>
            @endcan
        </div>
        <p class="text-muted mb-0">{{ __('Dépôts, attentes d’approbation et décisions sur les documents visibles dans votre périmètre. Le journal d’audit technique reste sur la page dédiée.') }}</p>
        <livewire:event-feed />
    </div>
@endsection
