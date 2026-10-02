@extends('layouts.app')

@section('content')
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">{{ __('Profils d’accès') }}</h2>
                <p class="text-muted mb-0">{{ __('Associez dossiers par type d’utilisateur (rôles) et/ou par structure (pôles/services).') }}</p>
            </div>
            @can('create', \App\Models\Profile::class)
                <a href="{{ route('access-profiles.create') }}" class="btn btn-upload">{{ __('Nouveau profil') }}</a>
            @endcan
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive shadow-sm rounded bg-white">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Nom') }}</th>
                        <th class="text-center">{{ __('Dossiers') }}</th>
                        <th class="text-center">{{ __('Utilisateurs') }}</th>
                        <th class="text-center">{{ __('Rôles') }}</th>
                        <th class="text-center">{{ __('Pôles') }}</th>
                        <th class="text-center">{{ __('Services') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $profile)
                        <tr>
                            <td>
                                <strong>{{ $profile->name }}</strong>
                                @if($profile->description)
                                    <div class="small text-muted text-truncate" style="max-width: 360px;">{{ $profile->description }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $profile->categories_count }}</td>
                            <td class="text-center">{{ $profile->users_count }}</td>
                            <td class="text-center">{{ $profile->roles_count }}</td>
                            <td class="text-center">{{ $profile->departments_count }}</td>
                            <td class="text-center">{{ $profile->services_count }}</td>
                            <td class="text-end">
                                @can('view', $profile)
                                    <a href="{{ route('access-profiles.show', $profile) }}" class="btn btn-sm btn-outline-primary me-1" title="{{ __('Voir') }}">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                @endcan
                                @can('update', $profile)
                                    <a href="{{ route('access-profiles.edit', $profile) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Modifier') }}">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                @endcan
                                @can('delete', $profile)
                                    <form method="post" action="{{ route('access-profiles.destroy', $profile) }}" class="d-inline" onsubmit="return confirm(@json(__('Supprimer ce profil ?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">{{ __('Aucun profil pour le moment.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($profiles->hasPages())
            <x-pagination :items="$profiles"/>
        @endif
    </div>
@endsection
