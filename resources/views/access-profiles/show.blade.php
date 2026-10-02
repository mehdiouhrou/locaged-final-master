@extends('layouts.app')

@section('content')
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <a href="{{ route('access-profiles.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
                    <i class="fa-solid fa-angle-left me-1" aria-hidden="true"></i>{{ __('Retour à la liste') }}
                </a>
                <h2 class="fw-bold mb-1">{{ $profile->name }}</h2>
                @if($profile->description)
                    <p class="text-muted mb-0">{{ $profile->description }}</p>
                @endif
                @if($profile->creator)
                    <p class="small text-muted mb-0 mt-2">{{ __('Créé par') }} : {{ $profile->creator->full_name ?? $profile->creator->email }}</p>
                @endif
            </div>
            <div class="d-flex gap-2">
                @can('update', $profile)
                    <a href="{{ route('access-profiles.edit', $profile) }}" class="btn btn-upload">{{ __('Modifier') }}</a>
                @endcan
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h6 text-uppercase text-muted">{{ __('Dossiers') }}</h3>
                        <p class="display-6 fw-bold mb-0">{{ $profile->categories_count }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h6 text-uppercase text-muted">{{ __('Utilisateurs') }}</h3>
                        <p class="display-6 fw-bold mb-0">{{ $profile->users_count }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h6 text-uppercase text-muted">{{ __('Rôles') }}</h3>
                        <p class="display-6 fw-bold mb-0">{{ $profile->roles_count }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h6 text-uppercase text-muted">{{ __('Pôles') }}</h3>
                        <p class="display-6 fw-bold mb-0">{{ $profile->departments_count }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ __('Dossiers liées') }}</div>
                    <ul class="list-group list-group-flush small">
                        @forelse($profile->categories as $cat)
                            <li class="list-group-item">{{ $cat->name }}</li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('Aucune') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ __('Utilisateurs liés') }}</div>
                    <ul class="list-group list-group-flush small">
                        @forelse($profile->users as $u)
                            <li class="list-group-item">{{ $u->full_name ?: $u->email }} <span class="text-muted">({{ $u->email }})</span></li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('Aucun') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ __('Rôles liés') }}</div>
                    <ul class="list-group list-group-flush small">
                        @forelse($profile->roles as $role)
                            <li class="list-group-item">{{ $role->name }}</li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('Aucun') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ __('Pôles liés') }}</div>
                    <ul class="list-group list-group-flush small">
                        @forelse($profile->departments as $department)
                            <li class="list-group-item">{{ $department->name }}</li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('Aucun') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ __('Services liés') }}</div>
                    <ul class="list-group list-group-flush small">
                        @forelse($profile->services as $svc)
                            <li class="list-group-item">{{ $svc->name }}</li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('Aucun') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
