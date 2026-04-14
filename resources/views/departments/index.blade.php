@extends('layouts.app')

@section('content')
    <div class="container my-5 position-relative">
        <x-page-hero
            class="mb-4"
            :title="ui_t('pages.structures_page.title')"
            :subtitle="ui_t('pages.structures_page.manage')"
        />

        <x-auth-session-status class="mb-3 text-success small" :status="session('success')" />
        <x-auth-session-status class="mb-3 text-danger small" :status="session('error')" />

        @if($canCreateStructures)
            {{-- Departments management (only for higher admins, not Admin de pole) --}}
            @if($canCreatePole ?? true)
            <h5 class="fw-bold my-4">{{ ui_t('pages.structures_page.add') }}</h5>
            <form method="post" action="{{ route('departments.store') }}">
                @csrf
                <div class="row g-3 align-items-center add-role">
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.name') }}</label>
                        <input type="text" name="name" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.placeholder_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.description') }}</label>
                        <textarea name="description" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.placeholder_description') }}"></textarea>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.structures_page.add_button') }}</button>
                    </div>
                </div>
            </form>
            @endif

            {{-- Sub-Departments management --}}
            <h5 class="fw-bold my-4">{{ __('Départements / sous-structures') }}</h5>
            <form method="post" action="{{ route('sub-departments.store') }}">
                @csrf
                <div class="row g-3 align-items-center add-role">
                    <div class="col-md-4">
                        <label>{{ __('Pôle') }}</label>
                        <select name="department_id" class="form-control py-3 mt-1">
                            @foreach($allDepartments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>{{ __('Nom du département / sous-structure') }}</label>
                        <input type="text" name="name" class="form-control py-3 mt-1" placeholder="{{ __('Ex: Département Finance') }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.structures_page.sub_departments_add_button') }}</button>
                    </div>
                </div>
            </form>

            {{-- Services management --}}
            <h5 class="fw-bold my-4">{{ ui_t('pages.structures_page.services_title') }}</h5>
            <form method="post" action="{{ route('services.store') }}">
                @csrf
                <div class="row g-3 align-items-center add-role">
                    <div class="col-md-4">
                        <label>{{ __('Niveau de rattachement') }}</label>
                        <select name="scope_type" id="service_scope_type" class="form-control py-3 mt-1">
                            <option value="sub">{{ __('Service dans une sous-structure (3 niveaux)') }}</option>
                            <option value="direct">{{ __('Service direct dans un pôle (2 niveaux)') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="service_sub_department_wrap">
                        <label>{{ __('Département / sous-structure') }}</label>
                        <select name="sub_department_id" class="form-control py-3 mt-1">
                            @foreach($allDepartments as $dept)
                                @foreach($dept->subDepartments->where('name', '!=', '__DIRECT__') as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-none" id="service_department_wrap">
                        <label>{{ __('Pôle (service direct)') }}</label>
                        <select name="department_id" class="form-control py-3 mt-1">
                            @foreach($allDepartments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.services_name_label') }}</label>
                        <input type="text" name="name" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.services_name_placeholder') }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.structures_page.services_add_button') }}</button>
                    </div>
                </div>
            </form>

            <h5 class="fw-bold my-4">{{ __('Import organigramme (Excel)') }}</h5>
            <p class="text-muted small mb-3">
                {{ __('Feuille : 1re ligne = en-têtes ignorés. Colonne A = pôle, B = département / sous-structure, C = service. Les lignes existantes du même nom sont réutilisées.') }}
            </p>
            <form method="post" action="{{ route('departments.import-org-chart') }}" enctype="multipart/form-data" class="row g-3 align-items-end add-role mb-4">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">{{ __('Fichier .xlsx, .xls ou .csv') }}</label>
                    <input type="file" name="org_chart" class="form-control" accept=".xlsx,.xls,.csv" required />
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-upload" type="submit">{{ __('Importer') }}</button>
                </div>
            </form>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-5">
            <h6 class="fw-bold mb-0">{{ ui_t('pages.structures_page.existing') }}</h6>
            <div class="input-group input-group-sm" style="max-width: 360px;">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input id="orgFilterInput" type="text" class="form-control" placeholder="{{ __('Rechercher pôle, sous-structure, service ou utilisateur') }}">
            </div>
            <div class="input-group input-group-sm" style="max-width: 240px;">
                <span class="input-group-text bg-white"><i class="fa-solid fa-filter"></i></span>
                <select id="orgServiceTypeFilter" class="form-select">
                    <option value="all">{{ __('Tous les services') }}</option>
                    <option value="direct">{{ __('Services directs uniquement') }}</option>
                    <option value="standard">{{ __('Services 3 niveaux uniquement') }}</option>
                </select>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mb-2 small">
            <span class="badge rounded-pill text-bg-danger">{{ __('Service vide') }}</span>
            <span class="badge rounded-pill text-bg-warning text-dark">{{ __('Service partiel') }}</span>
            <span class="badge rounded-pill text-bg-success">{{ __('Service complet') }}</span>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-dark rounded-pill"><i class="fas fa-sitemap"></i></span>
                <strong>{{ \App\Support\Branding::getOrgRootName() }}</strong>
                <span class="text-muted small">{{ __('Branche principale de la hiérarchie') }}</span>
            </div>
        </div>

        {{-- Org-chart v2: accordion by department + sub-department --}}
        <div id="orgAccordion" class="accordion mt-2">
            @foreach($departments as $department)
                @php
                    $directSubDepartments = $department->subDepartments->where('name', '__DIRECT__');
                    $regularSubDepartments = $department->subDepartments->where('name', '!=', '__DIRECT__');
                    $directServices = $directSubDepartments->flatMap->services;
                    $departmentServiceCount = $department->subDepartments->sum(fn($sub) => $sub->services->count());
                    $departmentUserCount = $department->subDepartments->sum(function ($sub) {
                        return $sub->services->sum(function ($service) {
                            return $service->usersViaPivot->concat($service->users)->unique('id')->count();
                        });
                    });
                @endphp
                <div class="accordion-item shadow-sm border-0 mb-3 org-department-col" data-org-text="{{ strtolower($department->name.' '.$department->description) }}">
                    <h2 class="accordion-header" id="dept-heading-{{ $department->id }}">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#dept-collapse-{{ $department->id }}" aria-expanded="false" aria-controls="dept-collapse-{{ $department->id }}">
                            <span class="me-2 badge bg-dark rounded-pill"><i class="fas fa-building"></i></span>
                            <span class="me-2">{{ $department->name }}</span>
                            <span class="badge rounded-pill text-bg-light border me-2">{{ $departmentServiceCount }} {{ __('service(s)') }}</span>
                            <span class="badge rounded-pill text-bg-light border">{{ $departmentUserCount }} {{ __('utilisateur(s)') }}</span>
                        </button>
                    </h2>
                    <div id="dept-collapse-{{ $department->id }}" class="accordion-collapse collapse department-collapse" aria-labelledby="dept-heading-{{ $department->id }}" data-bs-parent="#orgAccordion">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    @if($department->description)
                                        <p class="text-muted small mb-0">{{ $department->description }}</p>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    @can('update', $department)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $department->id }}" title="{{ ui_t('pages.structures_page.edit') }}">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        @include('components.modals.edit-department-modal', ['department' => $department])
                                    @endcan
                                    @can('delete',$department)
                                        <form method="post" action="{{ route('departments.destroy', $department->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ ui_t('pages.structures_page.delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>

                            @if($directServices->isNotEmpty())
                                <div class="mb-3 p-2 rounded-3 border bg-light-subtle org-direct-services">
                                    <div class="small text-uppercase text-muted fw-bold mb-2">{{ __('Services directs du pôle') }}</div>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($directServices as $service)
                                            @php
                                                $serviceUsers = $service->usersViaPivot
                                                    ->concat($service->users)
                                                    ->unique('id')
                                                    ->sortBy('full_name')
                                                    ->values();
                                                $serviceUsersCount = $serviceUsers->count();
                                                $serviceStatus = $serviceUsersCount === 0 ? 'empty' : ($serviceUsersCount < 3 ? 'partial' : 'complete');
                                                $serviceStatusLabel = $serviceStatus === 'empty' ? __('Vide') : ($serviceStatus === 'partial' ? __('Partiel') : __('Complet'));
                                                $serviceStatusClass = $serviceStatus === 'empty' ? 'text-bg-danger' : ($serviceStatus === 'partial' ? 'text-bg-warning text-dark' : 'text-bg-success');
                                            @endphp
                                            <div class="border rounded-3 p-2 org-service-card org-service-direct {{ $serviceStatus === 'empty' ? 'border-danger-subtle bg-danger-subtle' : ($serviceStatus === 'partial' ? 'border-warning-subtle bg-warning-subtle' : 'border-success-subtle bg-success-subtle') }}" data-service-type="direct" data-org-text="{{ strtolower($service->name.' '.$serviceUsers->pluck('full_name')->implode(' ')) }}">
                                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-circle {{ $serviceStatus === 'empty' ? 'text-danger' : ($serviceStatus === 'partial' ? 'text-warning' : 'text-success') }} small"></i>
                                                        <span class="small fw-semibold service-name-label text-truncate" style="max-width: 260px;" title="{{ $service->name }}">{{ $service->name }}</span>
                                                        <span class="badge rounded-pill text-bg-dark">{{ __('Service direct') }}</span>
                                                        <span class="badge rounded-pill {{ $serviceStatusClass }}">{{ $serviceStatusLabel }}</span>
                                                        <span class="badge rounded-pill text-bg-light border">{{ $serviceUsersCount }} {{ __('utilisateur(s)') }}</span>
                                                    </div>
                                                    <div class="d-flex gap-1 ms-1">
                                                        @can('update', $department)
                                                            <form method="post" action="{{ route('services.update', $service->id) }}" class="d-inline-flex align-items-center">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="text" name="name" value="{{ $service->name }}" class="form-control form-control-sm me-1 d-none service-edit-input" style="max-width: 160px;">
                                                                <button class="btn btn-xs btn-link text-muted p-0 service-edit-toggle" type="button" title="{{ ui_t('pages.structures_page.edit') }}">
                                                                    <i class="fas fa-pen small"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                        @can('delete', $department)
                                                            <form method="post" action="{{ route('services.destroy', $service->id) }}" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-xs btn-link text-danger p-0" title="{{ ui_t('pages.structures_page.delete') }}">
                                                                    <i class="fas fa-trash small"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </div>

                                                @if($serviceUsers->isNotEmpty())
                                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                                        @foreach($serviceUsers as $serviceUser)
                                                            <span class="d-inline-flex align-items-center gap-2 px-2 py-1 border rounded-pill bg-white small">
                                                                <img src="{{ $serviceUser->avatar_url }}" alt="{{ $serviceUser->full_name }}" class="rounded-circle" width="20" height="20" style="object-fit: cover;">
                                                                <span>{{ $serviceUser->full_name }}</span>
                                                                @if($serviceUser->role)
                                                                    <span class="text-muted">({{ $serviceUser->role }})</span>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-muted small mb-0 mt-2">{{ __('Aucun utilisateur affecté') }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($regularSubDepartments->isNotEmpty())
                                <div class="accordion org-sub-accordion" id="sub-accordion-{{ $department->id }}">
                                    @foreach($regularSubDepartments as $sub)
                                        @php
                                            $subServiceCount = $sub->services->count();
                                            $subUserCount = $sub->services->sum(function ($service) {
                                                return $service->usersViaPivot->concat($service->users)->unique('id')->count();
                                            });
                                        @endphp
                                        <div class="accordion-item border rounded-3 mb-2 org-sub-item" data-org-text="{{ strtolower($sub->name) }}">
                                            <h2 class="accordion-header" id="sub-heading-{{ $sub->id }}">
                                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sub-collapse-{{ $sub->id }}" aria-expanded="false" aria-controls="sub-collapse-{{ $sub->id }}">
                                                    <span class="me-2 badge bg-secondary rounded-pill"><i class="fas fa-sitemap"></i></span>
                                                    <span class="me-2 fw-semibold">{{ $sub->name }}</span>
                                                    <span class="badge rounded-pill text-bg-light border me-2">{{ $subServiceCount }} {{ __('service(s)') }}</span>
                                                    <span class="badge rounded-pill text-bg-light border">{{ $subUserCount }} {{ __('utilisateur(s)') }}</span>
                                                </button>
                                            </h2>
                                            <div id="sub-collapse-{{ $sub->id }}" class="accordion-collapse collapse show sub-collapse" aria-labelledby="sub-heading-{{ $sub->id }}">
                                                <div class="accordion-body pt-2">
                                                    <div class="d-flex justify-content-end gap-1 mb-2">
                                                        @can('update', $department)
                                                            <form method="post" action="{{ route('sub-departments.update', $sub->id) }}" class="d-inline-flex align-items-center">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="text" name="name" value="{{ $sub->name }}" class="form-control form-control-sm me-1" style="max-width: 180px;">
                                                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                                                    <i class="fas fa-save"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                        @can('delete', $department)
                                                            <form method="post" action="{{ route('sub-departments.destroy', $sub->id) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>

                                                    @if($sub->services->isNotEmpty())
                                                        <div class="d-flex flex-column gap-2 ms-1 org-services">
                                                            @foreach($sub->services as $service)
                                                                @php
                                                                    $serviceUsers = $service->usersViaPivot
                                                                        ->concat($service->users)
                                                                        ->unique('id')
                                                                        ->sortBy('full_name')
                                                                        ->values();
                                                                    $serviceUsersCount = $serviceUsers->count();
                                                                    $serviceStatus = $serviceUsersCount === 0 ? 'empty' : ($serviceUsersCount < 3 ? 'partial' : 'complete');
                                                                    $serviceStatusLabel = $serviceStatus === 'empty'
                                                                        ? __('Vide')
                                                                        : ($serviceStatus === 'partial' ? __('Partiel') : __('Complet'));
                                                                    $serviceStatusClass = $serviceStatus === 'empty'
                                                                        ? 'text-bg-danger'
                                                                        : ($serviceStatus === 'partial' ? 'text-bg-warning text-dark' : 'text-bg-success');
                                                                @endphp
                                                                <div class="border rounded-3 p-2 org-service-card org-service-standard {{ $serviceStatus === 'empty' ? 'border-danger-subtle bg-danger-subtle' : ($serviceStatus === 'partial' ? 'border-warning-subtle bg-warning-subtle' : 'border-success-subtle bg-success-subtle') }}" data-service-type="standard" data-org-text="{{ strtolower($service->name.' '.$serviceUsers->pluck('full_name')->implode(' ')) }}">
                                                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <i class="fas fa-circle {{ $serviceStatus === 'empty' ? 'text-danger' : ($serviceStatus === 'partial' ? 'text-warning' : 'text-success') }} small"></i>
                                                                            <span class="small fw-semibold service-name-label text-truncate" style="max-width: 260px;" title="{{ $service->name }}">{{ $service->name }}</span>
                                                                            <span class="badge rounded-pill {{ $serviceStatusClass }}">{{ $serviceStatusLabel }}</span>
                                                                            <span class="badge rounded-pill text-bg-light border">{{ $serviceUsersCount }} {{ __('utilisateur(s)') }}</span>
                                                                        </div>
                                                                        <div class="d-flex gap-1 ms-1">
                                                                            @can('update', $department)
                                                                                <form method="post" action="{{ route('services.update', $service->id) }}" class="d-inline-flex align-items-center">
                                                                                    @csrf
                                                                                    @method('PUT')
                                                                                    <input type="text" name="name" value="{{ $service->name }}" class="form-control form-control-sm me-1 d-none service-edit-input" style="max-width: 160px;">
                                                                                    <button class="btn btn-xs btn-link text-muted p-0 service-edit-toggle" type="button" title="{{ ui_t('pages.structures_page.edit') }}">
                                                                                        <i class="fas fa-pen small"></i>
                                                                                    </button>
                                                                                </form>
                                                                            @endcan
                                                                            @can('delete', $department)
                                                                                <form method="post" action="{{ route('services.destroy', $service->id) }}" class="d-inline">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit" class="btn btn-xs btn-link text-danger p-0" title="{{ ui_t('pages.structures_page.delete') }}">
                                                                                        <i class="fas fa-trash small"></i>
                                                                                    </button>
                                                                                </form>
                                                                            @endcan
                                                                        </div>
                                                                    </div>

                                                                    @if($serviceUsers->isNotEmpty())
                                                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                                                            @foreach($serviceUsers as $serviceUser)
                                                                                <span class="d-inline-flex align-items-center gap-2 px-2 py-1 border rounded-pill bg-white small">
                                                                                    <img src="{{ $serviceUser->avatar_url }}" alt="{{ $serviceUser->full_name }}" class="rounded-circle" width="20" height="20" style="object-fit: cover;">
                                                                                    <span>{{ $serviceUser->full_name }}</span>
                                                                                    @if($serviceUser->role)
                                                                                        <span class="text-muted">({{ $serviceUser->role }})</span>
                                                                                    @endif
                                                                                </span>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <p class="text-muted small mb-0 mt-2">{{ __('Aucun utilisateur affecté') }}</p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-muted small mb-0 ms-1">{{ __('Aucun service dans cette sous-structure') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                @if($directServices->isEmpty())
                                    <p class="text-muted small mb-0">{{ __('Aucune sous-structure pour ce pôle') }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <x-pagination :items="$departments"/>
    </div>

    <script>
        // Service name edit toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const serviceScopeSelect = document.getElementById('service_scope_type');
            const subWrap = document.getElementById('service_sub_department_wrap');
            const deptWrap = document.getElementById('service_department_wrap');

            function syncServiceScope() {
                if (!serviceScopeSelect || !subWrap || !deptWrap) {
                    return;
                }
                const isDirect = serviceScopeSelect.value === 'direct';
                subWrap.classList.toggle('d-none', isDirect);
                deptWrap.classList.toggle('d-none', !isDirect);
            }

            serviceScopeSelect?.addEventListener('change', syncServiceScope);
            syncServiceScope();

            document.querySelectorAll('.service-edit-toggle').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const input = form.querySelector('.service-edit-input');
                    const nameSpan = form.closest('.org-service-card').querySelector('.service-name-label');
                    
                    if (input.classList.contains('d-none')) {
                        // Show input, hide original name text
                        input.classList.remove('d-none');
                        input.focus();
                        input.select();
                        nameSpan.classList.add('d-none');
                        this.innerHTML = '<i class="fas fa-save small"></i>';
                        this.classList.add('text-success');
                        this.classList.remove('text-muted');
                    } else {
                        // Submit form
                        form.submit();
                    }
                });
            });
            
            // Allow pressing Enter to submit
            document.querySelectorAll('.service-edit-input').forEach(function(input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.closest('form').submit();
                    }
                });
                
                // Cancel on Escape
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const form = this.closest('form');
                        const nameSpan = form.closest('.org-service-card').querySelector('.service-name-label');
                        const btn = form.querySelector('.service-edit-toggle');
                        
                        this.classList.add('d-none');
                        nameSpan.classList.remove('d-none');
                        btn.innerHTML = '<i class="fas fa-pen small"></i>';
                        btn.classList.remove('text-success');
                        btn.classList.add('text-muted');
                    }
                });
            });

            // Quick client-side search for visual org chart
            const filterInput = document.getElementById('orgFilterInput');
            const serviceTypeFilter = document.getElementById('orgServiceTypeFilter');

            function isServiceTypeVisible(card, selectedType) {
                if (!selectedType || selectedType === 'all') {
                    return true;
                }
                return (card.dataset.serviceType || '') === selectedType;
            }

            function applyOrgFilters() {
                const term = (filterInput?.value || '').trim().toLowerCase();
                const selectedType = serviceTypeFilter?.value || 'all';

                document.querySelectorAll('.org-department-col').forEach(function(col) {
                    const departmentText = (col.dataset.orgText || '').toLowerCase();
                    const departmentCollapseEl = col.querySelector('.department-collapse');
                    const directServiceCards = col.querySelectorAll('.org-direct-services .org-service-card');
                    const subItems = col.querySelectorAll('.org-sub-item');
                    let subMatchCount = 0;
                    let directMatchCount = 0;

                    directServiceCards.forEach(function(card) {
                        const serviceText = (card.dataset.orgText || '').toLowerCase();
                        const typeMatch = isServiceTypeVisible(card, selectedType);
                        const textMatch = term === '' || serviceText.includes(term) || departmentText.includes(term);
                        const match = typeMatch && textMatch;
                        card.style.display = match ? '' : 'none';
                        if (match) {
                            directMatchCount += 1;
                        }
                    });

                    subItems.forEach(function(subItem) {
                        const subText = (subItem.dataset.orgText || '').toLowerCase();
                        const subCollapseEl = subItem.querySelector('.sub-collapse');
                        const serviceCards = subItem.querySelectorAll('.org-service-card');
                        let serviceMatchCount = 0;

                        serviceCards.forEach(function(card) {
                            const serviceText = (card.dataset.orgText || '').toLowerCase();
                            const typeMatch = isServiceTypeVisible(card, selectedType);
                            const textMatch = term === '' || serviceText.includes(term) || subText.includes(term) || departmentText.includes(term);
                            const match = typeMatch && textMatch;
                            card.style.display = match ? '' : 'none';
                            if (match) {
                                serviceMatchCount += 1;
                            }
                        });

                        const showSub = term === ''
                            ? serviceMatchCount > 0 || selectedType === 'all'
                            : subText.includes(term) || departmentText.includes(term) || serviceMatchCount > 0;
                        subItem.style.display = showSub ? '' : 'none';

                        if (window.bootstrap && subCollapseEl) {
                            const bsSubCollapse = window.bootstrap.Collapse.getOrCreateInstance(subCollapseEl, { toggle: false });
                            if ((term !== '' || selectedType !== 'all') && showSub) {
                                bsSubCollapse.show();
                            } else if (term === '' && selectedType === 'all') {
                                // keep default behavior from initial state
                            }
                        }

                        if (showSub) {
                            subMatchCount += 1;
                        }
                    });

                    const showDepartment = (term === '' && selectedType === 'all')
                        ? true
                        : (term === '' ? (subMatchCount > 0 || directMatchCount > 0) : (departmentText.includes(term) || subMatchCount > 0 || directMatchCount > 0));
                    col.style.display = showDepartment ? '' : 'none';

                    if (window.bootstrap && departmentCollapseEl) {
                        const bsDeptCollapse = window.bootstrap.Collapse.getOrCreateInstance(departmentCollapseEl, { toggle: false });
                        if ((term !== '' || selectedType !== 'all') && showDepartment) {
                            bsDeptCollapse.show();
                        } else if (term === '' && selectedType === 'all') {
                            // keep default behavior from initial state
                        }
                    }
                });
            }

            if (filterInput) {
                filterInput.addEventListener('input', applyOrgFilters);
            }
            serviceTypeFilter?.addEventListener('change', applyOrgFilters);
        });
    </script>
@endsection
