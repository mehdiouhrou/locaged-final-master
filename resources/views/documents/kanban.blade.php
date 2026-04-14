@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">{{ __('Kanban documents') }}</h2>
            <a href="{{ route('documents.status') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">{{ ui_t('nav.approvals') }}</a>
        </div>
        @can('viewAny', \App\Models\Document::class)
            <livewire:documents-kanban />
        @else
            <p class="text-muted">{{ __('Vous n’avez pas l’accès à la liste des documents.') }}</p>
        @endcan
    </div>
@endsection
