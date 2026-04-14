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

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Services autorisés (direct)') }}</label>
                                @php
                                    $selectedServiceIds = old(
                                        'service_ids',
                                        $category->services->pluck('id')->map(fn($id) => (string) $id)->all()
                                    );
                                @endphp
                                <select name="service_ids[]" class="form-select custom-input" multiple size="8">
                                    @foreach($services as $service)
                                        @php
                                            $deptName = optional(optional($service->subDepartment)->department)->name;
                                            $subName = optional($service->subDepartment)->name;
                                            $serviceLabel = $service->name;
                                            if ($subName || $deptName) {
                                                $serviceLabel .= ' — ' . trim(implode(' / ', array_filter([$deptName, $subName])));
                                            }
                                        @endphp
                                        <option value="{{ $service->id }}"
                                            {{ in_array((string) $service->id, $selectedServiceIds, true) ? 'selected' : '' }}>
                                            {{ $serviceLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('service_ids')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('service_ids.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Rôles autorisés (direct)') }}</label>
                                @php
                                    $selectedRoleIds = old(
                                        'role_ids',
                                        $category->roles->pluck('id')->map(fn($id) => (string) $id)->all()
                                    );
                                @endphp
                                <select name="role_ids[]" class="form-select custom-input" multiple size="6">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ in_array((string) $role->id, $selectedRoleIds, true) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_ids')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('role_ids.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Profils d’accès liés (optionnel)') }}</label>
                                @php
                                    $selectedProfileIds = old(
                                        'profile_ids',
                                        $category->profiles->pluck('id')->map(fn($id) => (string) $id)->all()
                                    );
                                @endphp
                                <select name="profile_ids[]" class="form-select custom-input" multiple size="8">
                                    @foreach($profiles as $profile)
                                        <option value="{{ $profile->id }}"
                                            {{ in_array((string) $profile->id, $selectedProfileIds, true) ? 'selected' : '' }}>
                                            {{ $profile->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('Cette liaison complète la gestion fine dans Profils d’accès.') }}</small>
                                @error('profile_ids')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('profile_ids.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

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

        {{-- Live Preview Section --}}
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
                                    <!-- Subcategory previews will be appended here -->
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
        const translations = {
            enterSubcategory: @json(ui_t('pages.categories_form.enter_subcategory')),
            removeSubcategory: @json(ui_t('pages.categories_form.remove_subcategory')),
            selectSubDepartment: @json(ui_t('pages.categories_edit.select_sub_department')),
            selectService: @json(ui_t('pages.categories_edit.select_service')),
        };
        const categoryInput = document.getElementById('categoryInput');
        const liveCategory = document.getElementById('liveCategory');
        const subcategoryContainer = document.getElementById('subcategoryContainer');
        const liveSubcategories = document.getElementById('liveSubcategories');
        const addBtn = document.getElementById('addSubcategoryBtn');

        // Sync category input to live preview
        categoryInput.addEventListener('input', function () {
            liveCategory.value = this.value;
        });

        // Helper: create a subcategory input with remove button
        function createSubcategoryItem(value = '', id = '') {
            const newItem = document.createElement('div');
            newItem.classList.add('subcategory-item', 'mb-3', 'd-flex', 'align-items-center');

            // Hidden input for ID, empty for new subcategory
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

        // Sync subcategories live preview
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

        // Add new subcategory input on button click
        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const newItem = createSubcategoryItem();
            subcategoryContainer.appendChild(newItem);
            syncSubcategories();
        });

        // Remove buttons for existing subcategories
        document.querySelectorAll('.remove-subcategory').forEach(button => {
            button.addEventListener('click', function () {
                this.closest('.subcategory-item').remove();
                syncSubcategories();
            });
        });

        // Initialize live preview with current values on page load
        liveCategory.value = categoryInput.value;
        syncSubcategories();
    </script>
@endsection
