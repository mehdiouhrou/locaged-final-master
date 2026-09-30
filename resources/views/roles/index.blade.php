@extends('layouts.app')

@section('title', ui_t('pages.roles.title') ?? 'Gestion des rôles')

@section('content')
<div class="container-fluid px-4 py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="fas fa-shield-alt me-2 text-primary"></i>
                {{ ui_t('pages.roles.title') ?? 'Gestion des rôles' }}
            </h4>
            <p class="text-muted small mb-0">{{ $roles->count() }} rôles configurés dans le système</p>
        </div>
        @can('create', \Spatie\Permission\Models\Role::class)
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nouveau rôle
            </a>
        @endcan
    </div>

    <div class="roles-list">
        @forelse($roles as $role)
            @php
                $isProtected = $role->name === 'master';
            @endphp
            <div class="role-card {{ $isProtected ? 'role-card-protected' : '' }}">
                <div class="role-card-left">
                    <div class="role-name">
                        @if($isProtected)
                            <i class="fas fa-crown text-warning me-2"></i>
                        @else
                            <i class="fas fa-user-shield text-primary me-2"></i>
                        @endif
                        {{ $role->name }}
                        @if($isProtected)
                            <span class="badge bg-warning text-dark ms-2" style="font-size:0.7rem;">
                                <i class="fas fa-lock me-1"></i>Protégé
                            </span>
                        @endif
                    </div>
                    <div class="role-meta mt-1">
                        <span class="badge bg-primary me-1">
                            <i class="fas fa-key me-1"></i>{{ $role->permissions_count }} permissions
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-users me-1"></i>{{ $role->users_count }} utilisateur(s)
                        </span>
                    </div>
                </div>

                <div class="role-card-actions">
                    @can('update', $role)
                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i>Modifier les permissions
                        </a>
                    @endcan

                    @can('delete', $role)
                        @if($role->users_count == 0)
                            <form method="POST" action="{{ route('roles.destroy', $role->id) }}"
                                  onsubmit="return confirm('Supprimer le rôle « {{ $role->name }} » ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash me-1"></i>Supprimer
                                </button>
                            </form>
                        @else
                            <span class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>{{ $role->users_count }} users actifs
                            </span>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fas fa-shield-alt fa-3x mb-3 opacity-25"></i>
                <p>Aucun rôle configuré.</p>
            </div>
        @endforelse
    </div>

</div>

<style>
.roles-list { display: flex; flex-direction: column; gap: 10px; }
.role-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px 20px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    transition: box-shadow 0.18s;
    flex-wrap: wrap;
}
.role-card:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
.role-card-protected { border-color: #fbbf24; background: #fffdf0; }
.role-card-left { display: flex; flex-direction: column; gap: 4px; }
.role-name { font-weight: 600; font-size: 1rem; display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
.role-card-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
</style>
@endsection
