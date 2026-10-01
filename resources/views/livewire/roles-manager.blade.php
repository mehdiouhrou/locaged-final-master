<div>
    {{-- Messages flash --}}
    @if($successMessage)
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-check-circle"></i>
            <div>{{ $successMessage }}</div>
            <button type="button" class="btn-close" wire:click="clearMessage"></button>
        </div>
    @endif
    @if($errorMessage)
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ $errorMessage }}</div>
            <button type="button" class="btn-close" wire:click="clearMessage"></button>
        </div>
    @endif

    {{-- Modale confirmation suppression --}}
    @if($confirmDeleteId)
        <div class="modal-backdrop-custom">
            <div class="confirm-modal">
                <div class="confirm-modal-icon text-danger"><i class="fas fa-trash-alt fa-2x"></i></div>
                <h5 class="fw-bold mt-3">Supprimer le rôle ?</h5>
                <p class="text-muted">Le rôle <strong>{{ $confirmDeleteName }}</strong> sera supprimé définitivement.
                    Les utilisateurs qui l'ont ne perdront pas leurs accès immédiats, mais le rôle disparaîtra.</p>
                <div class="d-flex gap-2 justify-content-center mt-4">
                    <button class="btn btn-outline-secondary" wire:click="cancelDelete">Annuler</button>
                    <button class="btn btn-danger" wire:click="deleteRole">Oui, supprimer</button>
                </div>
            </div>
        </div>
    @endif

    @if($showForm)
        {{-- ===== FORMULAIRE CRÉATION / ÉDITION ===== --}}
        <div class="role-form-card card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-shield-alt me-2 text-primary"></i>
                    {{ $editingRoleId ? 'Modifier le rôle' : 'Nouveau rôle' }}
                </h5>
                <button class="btn btn-sm btn-outline-secondary" wire:click="cancel">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom technique <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('roleName') is-invalid @enderror"
                               wire:model.defer="roleName"
                               placeholder="ex: Chef de Pôle"
                        @error('roleName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Nom utilisé dans le code (doit être unique)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom affiché</label>
                        <input type="text" class="form-control" wire:model.defer="roleDisplayName"
                               placeholder="ex: Chef de Pôle (affiché aux utilisateurs)">
                        <small class="text-muted">Optionnel — affiché dans l'interface</small>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="fas fa-key me-2 text-warning"></i>Permissions
                    <span class="badge bg-primary ms-2">{{ count($selectedPermissions) }} sélectionnées</span>
                </h6>

                @php
                    $isMaster = auth()->user()->hasRole('master');
                @endphp

                <div class="permissions-grid">
                    @foreach($permissionGroups as $group => $perms)
                        <div class="permission-group">
                            <div class="permission-group-title">
                                <i class="fas fa-tag me-1"></i>{{ ucfirst($group) }}
                                <span class="badge bg-light text-dark ms-1 small">{{ count($perms) }}</span>
                            </div>
                            @foreach($perms as $perm)
                                @php
                                    $isMasterOnly = $this->isMasterOnly($perm->name);
                                    $isChecked = in_array($perm->name, $selectedPermissions);
                                    $isDisabled = $isMasterOnly && ### Fichier 2 : Vue LivewireisMaster;
                                @endphp
                                <label class="perm-item {{ $isDisabled ? 'perm-locked' : '' }}"
                                       title="{{ $isMasterOnly ? '🔒 Réservé au master' : '' }}">
                                    <input type="checkbox"
                                           class="perm-checkbox"
                                           wire:click="togglePermission('{{ $perm->name }}')"
                                           {{ $isChecked ? 'checked' : '' }}
                                           {{ $isDisabled ? 'disabled' : '' }}>
                                    <span class="perm-label">
                                        {{ $perm->name }}
                                        @if($isMasterOnly)
                                            <i class="fas fa-lock text-danger ms-1" title="Master uniquement"></i>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4 border-top pt-3">
                    <button class="btn btn-outline-secondary" wire:click="cancel">Annuler</button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas fa-save me-1"></i>
                        {{ $editingRoleId ? 'Enregistrer les modifications' : 'Créer le rôle' }}
                    </button>
                </div>
            </div>
        </div>

    @else
        {{-- ===== LISTE DES RÔLES ===== --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Gestion des rôles</h4>
                <p class="text-muted small mb-0">{{ $roles->count() }} rôles configurés dans le système</p>
            </div>
            @can('manage roles')
                <button class="btn btn-primary" wire:click="openCreate">
                    <i class="fas fa-plus me-2"></i>Nouveau rôle
                </button>
            @endcan
        </div>

        <div class="roles-list">
            @foreach($roles as $role)
                @php
                    $isProtected = in_array($role->name, ['master']);
                    $canEdit = auth()->user()->hasRole('master') || ### Fichier 2 : Vue LivewireisProtected;
                @endphp
                <div class="role-card {{ $isProtected ? 'role-card-protected' : '' }}">
                    <div class="role-card-header">
                        <div class="role-info">
                            <span class="role-name">
                                @if($isProtected)
                                    <i class="fas fa-crown text-warning me-2"></i>
                                @else
                                    <i class="fas fa-user-shield text-primary me-2"></i>
                                @endif
                                {{ $role->name }}
                            </span>
                            @if($role->display_name)
                                <span class="role-display-name text-muted">{{ $role->display_name }}</span>
                            @endif
                        </div>
                        <div class="role-badges">
                            <span class="badge bg-primary">
                                <i class="fas fa-key me-1"></i>{{ $role->permissions_count }} permissions
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fas fa-users me-1"></i>{{ $role->users_count }} utilisateur(s)
                            </span>
                        </div>
                    </div>

                    <div class="role-card-actions">
                        @if($canEdit)
                            <button class="btn btn-sm btn-outline-primary" wire:click="openEdit({{ $role->id }})">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                        @endif
                        @if($canEdit && $role->users_count === 0)
                            <button class="btn btn-sm btn-outline-danger" wire:click="confirmDelete({{ $role->id }})">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        @elseif($role->users_count > 0)
                            <span class="text-muted small"><i class="fas fa-info-circle me-1"></i>Rôle actif ({{ $role->users_count }} users)</span>
                        @endif
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-lock me-1"></i>Protégé
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    max-height: 500px;
    overflow-y: auto;
    padding: 4px;
}
.permission-group {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}
.permission-group-title {
    background: #f8f9fa;
    padding: 8px 12px;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.perm-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 7px 12px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid #f3f4f6;
}
.perm-item:last-child { border-bottom: none; }
.perm-item:hover:not(.perm-locked) { background: #f0f9ff; }
.perm-locked { opacity: 0.5; cursor: not-allowed; background: #fef9f9; }
.perm-checkbox { margin-top: 2px; flex-shrink: 0; }
.perm-label { font-size: 0.85rem; line-height: 1.3; }

.roles-list { display: flex; flex-direction: column; gap: 12px; }
.role-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px 20px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    transition: box-shadow 0.2s;
}
.role-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.role-card-protected { border-color: #fbbf24; background: #fffbeb; }
.role-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.role-name { font-weight: 600; font-size: 1rem; }
.role-display-name { font-size: 0.85rem; }
.role-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.role-card-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.role-form-card { border-radius: 12px; }

.modal-backdrop-custom {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
.confirm-modal {
    background: white;
    border-radius: 16px;
    padding: 32px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
</style>
