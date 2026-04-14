@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">{{ __('Fil d’activité documentaire') }}</h2>
        </div>
        <p class="text-muted">{{ __('Événements des documents auxquels vous avez accès, avec filtres par type, catégorie et période.') }}</p>
        <livewire:event-feed />
    </div>
@endsection
