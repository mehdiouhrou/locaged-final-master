@extends('layouts.app')

@section('title', 'Modifier le rôle — ' . $role->name)

@section('content')
<div class="container-fluid px-4 py-4" style="max-width: 860px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
        <div>
            <h4 class="fw-bold mb-0">
                <i class="fas fa-shield-alt me-2 text-primary"></i>
                Modifier le rôle <span class="text-primary">« {{ $role->name }} »</span>
            </h4>
            <p class="text-muted small mb-0">Définissez ce que les utilisateurs avec ce rôle peuvent voir et faire</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('roles.update', $role->id) }}">
        @csrf
        @method('PUT')

        {{-- Nom du rôle --}}
        <div class="card shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-body">
                <label class="form-label fw-semibold">Nom du rôle</label>
                <input type="text"
                       name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       style="max-width:360px;"
                       value="{{ old('name', $role->name) }}"
                       {{ $role->name === 'master' ? 'readonly' : '' }}>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if($role->name === 'master')
                    <small class="text-muted"><i class="fas fa-lock me-1"></i>Le rôle master ne peut pas être renommé.</small>
                @endif
            </div>
        </div>

        {{-- ===== SECTION 1 : PÉRIMÈTRE DE VISIBILITÉ ===== --}}
        <div class="card shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-header py-3" style="border-radius:12px 12px 0 0; background:#f8fafc;">
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-eye me-2 text-info"></i>
                    1 — Quels documents cet utilisateur peut-il voir ?
                </h6>
                <small class="text-muted">Une seule option possible</small>
            </div>
            <div class="card-body">
                <div class="scope-options">

                    <label class="scope-option {{ in_array('view any document', $rolePermissions) ? 'scope-selected' : '' }}">
                        <input type="radio" name="scope" value="any"
                               {{ in_array('view any document', $rolePermissions) ? 'checked' : '' }}>
                        <div class="scope-icon"><i class="fas fa-globe text-danger"></i></div>
                        <div>
                            <div class="scope-title">Toute l'organisation</div>
                            <div class="scope-desc text-muted">Voit tous les documents, tous les pôles confondus</div>
                        </div>
                    </label>

                    <label class="scope-option {{ in_array('view department document', $rolePermissions) && !in_array('view any document', $rolePermissions) ? 'scope-selected' : '' }}">
                        <input type="radio" name="scope" value="department"
                               {{ in_array('view department document', $rolePermissions) && !in_array('view any document', $rolePermissions) ? 'checked' : '' }}>
                        <div class="scope-icon"><i class="fas fa-building text-warning"></i></div>
                        <div>
                            <div class="scope-title">Son Pôle uniquement</div>
                            <div class="scope-desc text-muted">Voit les documents de son pôle (Department)</div>
                        </div>
                    </label>

                    <label class="scope-option {{ in_array('view subdepartment scoped documents', $rolePermissions) ? 'scope-selected' : '' }}">
                        <input type="radio" name="scope" value="subdepartment"
                               {{ in_array('view subdepartment scoped documents', $rolePermissions) ? 'checked' : '' }}>
                        <div class="scope-icon"><i class="fas fa-sitemap text-primary"></i></div>
                        <div>
                            <div class="scope-title">Son Unité uniquement</div>
                            <div class="scope-desc text-muted">Voit les documents de son unité (SubDepartment)</div>
                        </div>
                    </label>

                    <label class="scope-option {{ in_array('view service document', $rolePermissions) && !in_array('view department document', $rolePermissions) && !in_array('view subdepartment scoped documents', $rolePermissions) && !in_array('view any document', $rolePermissions) ? 'scope-selected' : '' }}">
                        <input type="radio" name="scope" value="service"
                               {{ in_array('view service document', $rolePermissions) && !in_array('view department document', $rolePermissions) && !in_array('view subdepartment scoped documents', $rolePermissions) && !in_array('view any document', $rolePermissions) ? 'checked' : '' }}>
                        <div class="scope-icon"><i class="fas fa-users text-success"></i></div>
                        <div>
                            <div class="scope-title">Sa Cellule uniquement</div>
                            <div class="scope-desc text-muted">Voit les documents de sa cellule (Service)</div>
                        </div>
                    </label>

                    <label class="scope-option {{ in_array('view own document', $rolePermissions) && !in_array('view service document', $rolePermissions) && !in_array('view department document', $rolePermissions) && !in_array('view subdepartment scoped documents', $rolePermissions) && !in_array('view any document', $rolePermissions) ? 'scope-selected' : '' }}">
                        <input type="radio" name="scope" value="own"
                               {{ in_array('view own document', $rolePermissions) && !in_array('view service document', $rolePermissions) && !in_array('view department document', $rolePermissions) && !in_array('view subdepartment scoped documents', $rolePermissions) && !in_array('view any document', $rolePermissions) ? 'checked' : '' }}>
                        <div class="scope-icon"><i class="fas fa-user text-secondary"></i></div>
                        <div>
                            <div class="scope-title">Ses propres documents uniquement</div>
                            <div class="scope-desc text-muted">Voit uniquement les documents qu'il a lui-même déposés</div>
                        </div>
                    </label>

                </div>
            </div>
        </div>

        {{-- ===== SECTION 2 : ACTIONS AUTORISÉES ===== --}}
        <div class="card shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-header py-3" style="border-radius:12px 12px 0 0; background:#f8fafc;">
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    2 — Que peut-il faire avec les documents ?
                </h6>
            </div>
            <div class="card-body">
                <div class="actions-grid">

                    @php
                    $actionGroups = [
                        'documents' => [
                            'label' => 'Documents',
                            'icon' => 'fa-file-alt',
                            'color' => 'text-primary',
                            'items' => [
                                ['label' => 'Déposer des documents', 'desc' => 'Uploader de nouveaux fichiers', 'perms' => ['upload document', 'create document']],
                                ['label' => 'Télécharger des documents', 'desc' => 'Exporter les fichiers en local', 'perms' => ['download document']],
                                ['label' => 'Modifier un document', 'desc' => 'Renommer, déplacer, éditer les métadonnées', 'perms' => ['update document', 'edit document']],
                                ['label' => 'Supprimer des documents', 'desc' => 'Documents expirés uniquement', 'perms' => ['delete document', 'destroy expired document']],
                                ['label' => 'Approuver / refuser des documents', 'desc' => 'Valider ou rejeter les documents en attente', 'perms' => ['approve document', 'decline document']],
                                ['label' => 'Voir l\'historique des versions', 'desc' => 'Consulter les révisions d\'un document', 'perms' => ['view document history']],
                            ],
                        ],
                        'users' => [
                            'label' => 'Utilisateurs',
                            'icon' => 'fa-users',
                            'color' => 'text-success',
                            'items' => [
                                ['label' => 'Voir la liste des utilisateurs', 'desc' => 'Consulter les comptes existants', 'perms' => ['view users list', 'view any user']],
                                ['label' => 'Créer et modifier des utilisateurs', 'desc' => 'Gérer les comptes et leurs accès', 'perms' => ['create user', 'update user', 'edit user']],
                                ['label' => 'Désactiver un utilisateur', 'desc' => 'Bloquer l\'accès sans supprimer le compte', 'perms' => ['disable user']],
                            ],
                        ],
                        'structure' => [
                            'label' => 'Structure organisationnelle',
                            'icon' => 'fa-sitemap',
                            'color' => 'text-warning',
                            'items' => [
                                ['label' => 'Gérer les Pôles, Unités et Cellules', 'desc' => 'Créer, modifier la hiérarchie organisationnelle', 'perms' => ['manage structures', 'create department', 'update department', 'create service', 'update service']],
                                ['label' => 'Gérer les catégories', 'desc' => 'Créer et modifier les catégories de documents', 'perms' => ['create category', 'update category', 'manage shared categories']],
                            ],
                        ],
                        'physical' => [
                            'label' => 'Emplacements physiques',
                            'icon' => 'fa-box',
                            'color' => 'text-secondary',
                            'items' => [
                                ['label' => 'Gérer les salles, étagères et boîtes', 'desc' => 'Administrer les emplacements d\'archivage physique', 'perms' => ['create physical location', 'update physical location', 'edit physical location', 'create box', 'edit box', 'view any physical location']],
                            ],
                        ],
                        'reports' => [
                            'label' => 'Rapports et statistiques',
                            'icon' => 'fa-chart-bar',
                            'color' => 'text-info',
                            'items' => [
                                ['label' => 'Voir les rapports généraux', 'desc' => 'Statistiques de l\'organisation', 'perms' => ['view organization wide reports', 'view audit log', 'view system activity log']],
                                ['label' => 'Voir le journal d\'audit', 'desc' => 'Traçabilité des actions utilisateurs', 'perms' => ['view audit', 'view audit log']],
                            ],
                        ],
                        'destructions' => [
                            'label' => 'Destruction & archivage',
                            'icon' => 'fa-fire',
                            'color' => 'text-danger',
                            'items' => [
                                ['label' => 'Demander la destruction de documents', 'desc' => 'Initier une procédure de destruction', 'perms' => ['create document destruction request']],
                                ['label' => 'Approuver les destructions', 'desc' => 'Valider ou refuser les demandes de destruction', 'perms' => ['approve document destruction request', 'decline document destruction request']],
                                ['label' => 'Voir les certificats de destruction', 'desc' => 'Consulter les preuves de destruction', 'perms' => ['view destruction certificates']],
                                ['label' => 'Gérer les dates d\'expiration', 'desc' => 'Modifier ou prolonger les dates d\'expiration', 'perms' => ['manage document global expiry', 'postpone document expiration']],
                            ],
                        ],
                    ];
                    @endphp

                    @foreach($actionGroups as $groupKey => $group)
                        <div class="action-group">
                            <div class="action-group-title">
                                <i class="fas {{ $group['icon'] }} {{ $group['color'] }} me-2"></i>
                                {{ $group['label'] }}
                            </div>
                            @foreach($group['items'] as $item)
                                @php
                                    $isChecked = count(array_intersect($item['perms'], $rolePermissions)) > 0;
                                    $key = $groupKey . '_' . $loop->index;
                                @endphp
                                <label class="action-item {{ $isChecked ? 'action-checked' : '' }}" for="action_{{ $key }}">
                                    <input type="checkbox"
                                           id="action_{{ $key }}"
                                           name="actions[]"
                                           value="{{ implode(',', $item['perms']) }}"
                                           class="action-checkbox"
                                           {{ $isChecked ? 'checked' : '' }}>
                                    <div class="action-text">
                                        <div class="action-label">{{ $item['label'] }}</div>
                                        <div class="action-desc text-muted">{{ $item['desc'] }}</div>
                                    </div>
                                    <div class="action-check-icon">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endforeach

                </div>
            </div>
        </div>

        {{-- ===== SECTION 3 : ADMINISTRATION SYSTÈME (master only) ===== --}}
        @if(auth()->user()->hasRole('master'))
        <div class="card shadow-sm mb-4 border-warning" style="border-radius:12px;">
            <div class="card-header py-3" style="border-radius:12px 12px 0 0; background:#fffdf0;">
                <h6 class="fw-bold mb-0 text-warning">
                    <i class="fas fa-crown me-2"></i>
                    3 — Administration système
                    <span class="badge bg-warning text-dark ms-2" style="font-size:0.7rem;">Master uniquement</span>
                </h6>
            </div>
            <div class="card-body">
                <div class="actions-grid">
                    <div class="action-group">
                        @php
                        $adminItems = [
                            ['label' => 'Gérer les rôles et permissions', 'perms' => ['manage roles', 'view any role', 'create role', 'update role', 'delete role']],
                            ['label' => 'Accéder à la console master', 'perms' => ['access horizon', 'view server', 'access management sidebar']],
                            ['label' => 'Gérer les traductions UI', 'perms' => ['create ui translation', 'update ui translation', 'delete ui translation', 'view any ui translation']],
                            ['label' => 'Gérer les règles de workflow', 'perms' => ['create workflow rule', 'update workflow rule', 'delete workflow rule', 'view any workflow rule']],
                        ];
                        @endphp
                        @foreach($adminItems as $item)
                            @php
                                $isChecked = count(array_intersect($item['perms'], $rolePermissions)) > 0;
                                $key = 'admin_' . $loop->index;
                            @endphp
                            <label class="action-item {{ $isChecked ? 'action-checked' : '' }}" for="action_{{ $key }}">
                                <input type="checkbox"
                                       id="action_{{ $key }}"
                                       name="actions[]"
                                       value="{{ implode(',', $item['perms']) }}"
                                       class="action-checkbox"
                                       {{ $isChecked ? 'checked' : '' }}>
                                <div class="action-text">
                                    <div class="action-label">{{ $item['label'] }}</div>
                                </div>
                                <div class="action-check-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-2"></i>Enregistrer les modifications
            </button>
        </div>

    </form>
</div>

<style>
/* ---- Périmètre de visibilité ---- */
.scope-options { display: flex; flex-direction: column; gap: 8px; }
.scope-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
}
.scope-option:hover { border-color: #93c5fd; background: #f0f9ff; }
.scope-option input[type=radio] { display: none; }
.scope-option.scope-selected,
.scope-option:has(input:checked) {
    border-color: #3b82f6;
    background: #eff6ff;
}
.scope-icon { font-size: 1.4rem; width: 32px; text-align: center; flex-shrink: 0; }
.scope-title { font-weight: 600; font-size: 0.95rem; }
.scope-desc { font-size: 0.82rem; }

/* ---- Grille actions ---- */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
}
.action-group {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}
.action-group-title {
    background: #f8f9fa;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.12s;
    position: relative;
}
.action-item:last-child { border-bottom: none; }
.action-item:hover { background: #f9fafb; }
.action-item.action-checked { background: #f0fdf4; }
.action-checkbox { display: none; }
.action-text { flex: 1; }
.action-label { font-weight: 500; font-size: 0.88rem; }
.action-desc { font-size: 0.78rem; }
.action-check-icon {
    font-size: 1rem;
    opacity: 0;
    transition: opacity 0.15s;
    flex-shrink: 0;
}
.action-item.action-checked .action-check-icon,
.action-item:has(input:checked) .action-check-icon { opacity: 1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Scope options — visuel au clic
    document.querySelectorAll('.scope-option').forEach(function(label) {
        label.addEventListener('click', function() {
            document.querySelectorAll('.scope-option').forEach(l => l.classList.remove('scope-selected'));
            this.classList.add('scope-selected');
        });
    });

    // Action items — toggle visuel
    document.querySelectorAll('.action-item').forEach(function(label) {
        label.addEventListener('click', function() {
            const cb = this.querySelector('.action-checkbox');
            cb.checked = !cb.checked;
            this.classList.toggle('action-checked', cb.checked);
        });
    });
});
</script>
@endsection
