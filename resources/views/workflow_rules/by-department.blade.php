@extends('layouts.app')

@section('content')

    <div class="container my-5 position-relative">
        <h2 class="fw-bold">{{ ui_t('pages.workflow.title') }}</h2>
        <p class="new-mange">{{ ui_t('pages.workflow.manage_for', ['name' => $department->name]) }}</p>

        <h5 class="fw-bold my-4">{{ ui_t('pages.workflow.add_rule') }}</h5>
        
        <div class="card border-0 shadow-sm p-4 mb-5 bg-light">
            <form method="post" action="{{ route('workflow-rules.store.department',['departmentId' => $department->id]) }}" id="workflowForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Catégorie (Optionnel)</label>
                        <select name="category_id" class="form-control">
                            <option value="">Toutes les catégories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ ui_t('pages.workflow.from_status') }}</label>
                        <select name="from_status" class="form-control" required>
                            @foreach (\App\Enums\DocumentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ $status->value == 'pending' ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ ui_t('pages.workflow.to_status') }}</label>
                        <select name="to_status" class="form-control" required>
                            @foreach (\App\Enums\DocumentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ $status->value == 'approved' ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="levelsContainer" class="mt-4">
                    <h6 class="fw-bold border-bottom pb-2">Niveaux d'approbation</h6>
                    
                    <div class="workflow-level card p-3 mb-3 border-0 shadow-sm" data-level="1">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary">Niveau 1</span>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label">Type d'approbateur</label>
                                <select name="levels[0][approver_type]" class="form-control approver-type-select">
                                    <option value="role">Par rôle</option>
                                    <option value="user">Par utilisateur spécifique</option>
                                </select>
                            </div>
                            <div class="col-md-6 role-select-container">
                                <label class="form-label">Rôle approbateur</label>
                                <select name="levels[0][approver_role]" class="form-control">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 user-select-container d-none">
                                <label class="form-label">Utilisateur approbateur</label>
                                <select name="levels[0][approver_user_id]" class="form-control">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" id="addLevelBtn" class="btn btn-outline-primary btn-sm mb-3">
                    <i class="fa-solid fa-plus"></i> Ajouter un niveau
                </button>

                <div class="text-end">
                    <button class="btn btn-upload" type="submit">{{ ui_t('pages.workflow.add') }}</button>
                </div>
            </form>
        </div>

        <h6 class="fw-bold mt-5">{{ ui_t('pages.workflow.existing') }}</h6>
        <div id="categoryList" class="mt-3">
            @foreach($rules as $groupKey => $ruleGroup)
                @php
                    $first = $ruleGroup->first();
                @endphp
                <div class="card mb-4 border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="badge bg-info me-2">{{ $first->category ? $first->category->name : 'Toutes catégories' }}</span>
                            <strong>{{ $first->from_status }}</strong> 
                            <i class="fa-solid fa-arrow-right mx-2 text-muted"></i> 
                            <strong>{{ $first->to_status }}</strong>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="post" action="{{ route('workflow-rules.destroy', $first->id) }}" onsubmit="return confirm('Supprimer ce workflow ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body bg-white p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;">Niveau</th>
                                    <th>Approbateur</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ruleGroup as $rule)
                                    <tr>
                                        <td><span class="badge bg-secondary">Niveau {{ $rule->level }}</span></td>
                                        <td>
                                            @if($rule->approver_user_id)
                                                <i class="fa-solid fa-user me-2 text-primary"></i> {{ $rule->approverUser->full_name }}
                                            @else
                                                <i class="fa-solid fa-users me-2 text-success"></i> {{ $rule->approver_role }}
                                            @endif
                                        </td>
                                        <td>{{ $rule->approver_user_id ? 'Utilisateur' : 'Rôle' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('levelsContainer');
            const addBtn = document.getElementById('addLevelBtn');
            let levelCount = 1;

            addBtn.addEventListener('click', function() {
                if (levelCount >= 3) {
                    alert('Maximum 3 niveaux autorisés.');
                    return;
                }

                const newLevel = levelCount;
                const levelHtml = `
                    <div class="workflow-level card p-3 mb-3 border-0 shadow-sm" data-level="${newLevel + 1}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary">Niveau ${newLevel + 1}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-level-btn">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label">Type d'approbateur</label>
                                <select name="levels[${newLevel}][approver_type]" class="form-control approver-type-select">
                                    <option value="role">Par rôle</option>
                                    <option value="user">Par utilisateur spécifique</option>
                                </select>
                            </div>
                            <div class="col-md-6 role-select-container">
                                <label class="form-label">Rôle approbateur</label>
                                <select name="levels[${newLevel}][approver_role]" class="form-control">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 user-select-container d-none">
                                <label class="form-label">Utilisateur approbateur</label>
                                <select name="levels[${newLevel}][approver_user_id]" class="form-control">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', levelHtml);
                levelCount++;
                
                if (levelCount >= 3) {
                    addBtn.classList.add('d-none');
                }
            });

            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('approver-type-select')) {
                    const levelCard = e.target.closest('.workflow-level');
                    const roleContainer = levelCard.querySelector('.role-select-container');
                    const userContainer = levelCard.querySelector('.user-select-container');
                    
                    if (e.target.value === 'role') {
                        roleContainer.classList.remove('d-none');
                        userContainer.classList.add('d-none');
                    } else {
                        roleContainer.classList.add('d-none');
                        userContainer.classList.remove('d-none');
                    }
                }
            });

            container.addEventListener('click', function(e) {
                if (e.target.closest('.remove-level-btn')) {
                    e.target.closest('.workflow-level').remove();
                    levelCount--;
                    addBtn.classList.remove('d-none');
                    
                    // Update levels badges and indexes
                    const levels = container.querySelectorAll('.workflow-level');
                    levels.forEach((lvl, idx) => {
                        lvl.dataset.level = idx + 1;
                        lvl.querySelector('.badge').textContent = `Niveau ${idx + 1}`;
                        
                        const selects = lvl.querySelectorAll('select');
                        selects.forEach(select => {
                            const name = select.getAttribute('name');
                            select.setAttribute('name', name.replace(/levels\[\d+\]/, `levels[${idx}]`));
                        });
                    });
                }
            });
        });
    </script>

    <style>
        .workflow-level {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd !important;
        }
        .btn-upload {
            background-color: #2c3e50;
            color: white;
            padding: 8px 25px;
            border-radius: 5px;
        }
        .btn-upload:hover {
            background-color: #1a252f;
            color: white;
        }
    </style>

@endsection
