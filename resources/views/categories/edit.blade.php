@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row mt-5">
            <div class="col-md-8">
                <div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h4 class="card-title mb-1">{{ ui_t('pages.categories_edit.title') }}</h4>
                                <p class="text-muted small mb-0">{{ ui_t('pages.categories_edit.subtitle') }}</p>
                            </div>
                        </div>

                        <form id="categoryForm" method="post" action="{{ route('categories.update', $category->id) }}">
                            @csrf
                            @method('PUT')

                            @php
                                $oldDept = old('department_id', $category->department_id);
                                $oldSubDept = old('sub_department_id', $category->sub_department_id);
                                $oldService = old('service_id', $category->service_id);
                            @endphp

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ ui_t('pages.categories_edit.structure') }} <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select id="categoryDepartmentSelect" name="department_id" class="form-control custom-input" required>
                                            <option value="">{{ ui_t('pages.categories_edit.select_structure') }}</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}" {{ $oldDept == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <select id="categorySubDepartmentSelect" name="sub_department_id" class="form-control custom-input" required>
                                            <option value="">{{ ui_t('pages.categories_edit.select_sub_department') }}</option>
                                            @foreach($departments as $department)
                                                @foreach($department->subDepartments as $sub)
                                                    <option value="{{ $sub->id }}" data-department-id="{{ $department->id }}" {{ $oldSubDept == $sub->id ? 'selected' : '' }}>
                                                        {{ $sub->name }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                        @error('sub_department_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <select id="categoryServiceSelect" name="service_id" class="form-control custom-input" required>
                                            <option value="">{{ ui_t('pages.categories_edit.select_service') }}</option>
                                            @foreach($departments as $department)
                                                @foreach($department->subDepartments as $sub)
                                                    @foreach($sub->services as $service)
                                                        <option value="{{ $service->id }}" data-sub-department-id="{{ $sub->id }}" {{ $oldService == $service->id ? 'selected' : '' }}>
                                                            {{ $service->name }}
                                                        </option>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        </select>
                                        @error('service_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="categoryInput" class="form-label fw-medium">{{ ui_t('pages.categories_edit.enter_category') }}</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="categoryInput"
                                    value="{{ old('name', $category->name) }}"
                                    class="form-control custom-input"
                                    placeholder="{{ ui_t('pages.categories_edit.enter_category_ph') }}"
                                    required
                                >
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ ui_t('pages.categories_edit.expiry_value') }}</label>
                                    <input type="number" min="1" name="expiry_value" value="{{ old('expiry_value', $category->expiry_value) }}" class="form-control custom-input" placeholder="{{ ui_t('pages.categories_form.expiry_value_example') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ ui_t('pages.categories_edit.expiry_unit') }}</label>
                                    <select name="expiry_unit" class="form-control custom-input" required>
                                        <option value="">{{ ui_t('pages.categories_edit.select_unit') }}</option>
                                        <option value="days" {{ old('expiry_unit', $category->expiry_unit)=='days'?'selected':'' }}>{{ ui_t('pages.categories_edit.days') }}</option>
                                        <option value="months" {{ old('expiry_unit', $category->expiry_unit)=='months'?'selected':'' }}>{{ ui_t('pages.categories_edit.months') }}</option>
                                        <option value="years" {{ old('expiry_unit', $category->expiry_unit)=='years'?'selected':'' }}>{{ ui_t('pages.categories_edit.years') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Services partagés : master et IT Admin uniquement --}}
                            @if(auth()->user()->hasAnyRole(['master', 'IT Admin']))
                            <div class="mb-4">
                                <label class="form-label fw-medium">Services partagés <span class="text-muted small">(optionnel)</span></label>
                                <p class="text-muted small mb-2">Ces services pourront voir cette catégorie et ses documents en lecture seule.</p>
                                <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;" id="sharedServicesContainer">
                                    @foreach($allServices as $service)
                                        @php
                                            $isMainService = $service->id == $oldService;
                                            $isChecked = in_array($service->id, old('shared_service_ids', $selectedSharedServiceIds));
                                        @endphp
                                        <div class="form-check mb-1 shared-service-item" data-service-id="{{ $service->id }}" style="{{ $isMainService ? 'display:none' : '' }}">
                                            <input
                                                class="form-check-input shared-service-checkbox"
                                                type="checkbox"
                                                name="shared_service_ids[]"
                                                value="{{ $service->id }}"
                                                id="shared_service_{{ $service->id }}"
                                                {{ $isChecked && !$isMainService ? 'checked' : '' }}
                                            >
                                            <label class="form-check-label small" for="shared_service_{{ $service->id }}">
                                                {{ $service->name }}
                                                @if($service->subDepartment?->department)
                                                    <span class="text-muted">— {{ $service->subDepartment->department->name }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="subcategory-section">
                                <label class="form-label fw-medium mb-3">{{ ui_t('pages.categories_edit.enter_subcategories') }}</label>
                                <div class="subcategory-visual-container">
                                    <div class="subcategory-container" id="subcategoryContainer">
                                        @php
                                            $oldSubsIds = old('subcategories_id', $category->subcategories->pluck('id')->toArray());
                                            $oldSubsNames = old('subcategories_name', $category->subcategories->pluck('name')->toArray());
                                        @endphp
                                        @foreach ($oldSubsNames as $index => $subName)
                                            <div class="subcategory-item mb-3 d-flex align-items-center">
                                                <input type="hidden" name="subcategories_id[]" value="{{ $oldSubsIds[$index] ?? '' }}">
                                                <input
                                                    type="text"
                                                    name="subcategories_name[]"
                                                    class="form-control custom-input subcategory-input"
                                                    placeholder="{{ ui_t('pages.categories_form.enter_subcategory') }}"
                                                    value="{{ $subName }}"
                                                    required
                                                >
                                                <button type="button" class="btn btn-sm btn-danger ms-2 remove-subcategory" title="{{ ui_t('pages.categories_edit.remove_subcategory') }}">
                                                    <i class="fas fa-trash text-white"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn-upload w-50 d-block mb-3" id="addSubcategoryBtn">{{ ui_t('pages.categories_edit.add_new_subcategory') }}</button>
                            <button type="submit" class="btn-upload w-75">{{ ui_t('pages.categories_edit.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Preview --}}
        <div class="row mt-5 w-50">
            <div class="col-md-8">
                <div>
                    <div class="card-body">
                        <h5 class="text-primary">{{ ui_t('pages.categories_edit.live_preview') }}</h5>
                        <div class="mb-3">
                            <input type="text" class="form-control custom-input" id="liveCategory" placeholder="{{ ui_t('pages.categories_edit.category_preview') }}" readonly>
                        </div>
                        <div class="subcategory-section">
                            <div class="subcategory-visual-container">
                                <div class="subcategory-container bg-ver" id="liveSubcategories">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script>
        (function() {
            const deptSelect = document.getElementById('categoryDepartmentSelect');
            const subDeptSelect = document.getElementById('categorySubDepartmentSelect');
            const serviceSelect = document.getElementById('categoryServiceSelect');

            function filterSubDepartments() {
                const deptId = deptSelect.value;
                Array.from(subDeptSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !deptId || opt.dataset.departmentId === deptId;
                    opt.hidden = !match;
                    if (!match && opt.selected) opt.selected = false;
                });
                filterServices();
            }

            function filterServices() {
                const subId = subDeptSelect.value;
                Array.from(serviceSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !subId || opt.dataset.subDepartmentId === subId;
                    opt.hidden = !match;
                    if (!match && opt.selected) opt.selected = false;
                });
                updateSharedServicesVisibility(serviceSelect.value);
            }

            function updateSharedServicesVisibility(selectedServiceId) {
                const items = document.querySelectorAll('.shared-service-item');
                items.forEach(item => {
                    const sid = item.dataset.serviceId;
                    if (String(sid) === String(selectedServiceId)) {
                        item.style.display = 'none';
                        item.querySelector('input').checked = false;
                    } else {
                        item.style.display = '';
                    }
                });
            }

            if (deptSelect && subDeptSelect && serviceSelect) {
                deptSelect.addEventListener('change', filterSubDepartments);
                subDeptSelect.addEventListener('change', filterServices);
                serviceSelect.addEventListener('change', () => updateSharedServicesVisibility(serviceSelect.value));
                filterSubDepartments();
            }
        })();

        const translations = {
            enterSubcategory: @json(ui_t('pages.categories_form.enter_subcategory')),
            removeSubcategory: @json(ui_t('pages.categories_form.remove_subcategory')),
        };

        const categoryInput = document.getElementById('categoryInput');
        const liveCategory = document.getElementById('liveCategory');
        const subcategoryContainer = document.getElementById('subcategoryContainer');
        const liveSubcategories = document.getElementById('liveSubcategories');
        const addBtn = document.getElementById('addSubcategoryBtn');

        categoryInput.addEventListener('input', function () {
            liveCategory.value = this.value;
        });

        function createSubcategoryItem(value = '', id = '') {
            const newItem = document.createElement('div');
            newItem.classList.add('subcategory-item', 'mb-3', 'd-flex', 'align-items-center');

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'subcategories_id[]';
            idInput.value = id;

            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = 'subcategories_name[]';
            newInput.classList.add('form-control', 'custom-input', 'subcategory-input');
            newInput.placeholder = translations.enterSubcategory;
            newInput.value = value;
            newInput.required = true;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.classList.add('btn', 'btn-sm', 'btn-danger', 'ms-2', 'remove-subcategory');
            removeBtn.title = translations.removeSubcategory;
            removeBtn.innerHTML = '<i class="fas fa-trash text-white"></i>';

            removeBtn.addEventListener('click', () => {
                newItem.remove();
                syncSubcategories();
            });

            newInput.addEventListener('input', syncSubcategories);

            newItem.appendChild(idInput);
            newItem.appendChild(newInput);
            newItem.appendChild(removeBtn);

            return newItem;
        }

        function syncSubcategories() {
            liveSubcategories.innerHTML = '';
            const inputs = subcategoryContainer.querySelectorAll('.subcategory-input');
            inputs.forEach(input => {
                const clone = document.createElement('input');
                clone.type = 'text';
                clone.classList.add('form-control', 'custom-input', 'mb-3');
                clone.value = input.value;
                clone.readOnly = true;
                liveSubcategories.appendChild(clone);
            });
        }

        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const newItem = createSubcategoryItem();
            subcategoryContainer.appendChild(newItem);
            syncSubcategories();
        });

        document.querySelectorAll('.remove-subcategory').forEach(button => {
            button.addEventListener('click', function () {
                this.closest('.subcategory-item').remove();
                syncSubcategories();
            });
        });

        liveCategory.value = categoryInput.value;
        syncSubcategories();
    </script>
@endsection