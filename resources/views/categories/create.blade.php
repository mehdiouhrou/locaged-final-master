@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row mt-5">
            <div class="col-md-8">
                <div class="">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h4 class="card-title mb-1">{{ ui_t('pages.categories_form.title') }}</h4>
                                <p class="text-muted small mb-0">{{ ui_t('pages.categories_form.subtitle') }}</p>
                            </div>
                          {{--  <button type="button" class="btn btn-dark btn-sm header-btn">
                                <i class="fas fa-plus me-1"></i> Category and subcategory
                            </button>--}}
                        </div>

                        <form id="categoryForm" method="post" action="{{ route('categories.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ ui_t('pages.categories_form.enter_category') }}</label>
                                <input type="text" name="category_name" value="{{ old('category_name') }}" class="form-control custom-input" id="categoryInput" placeholder="{{ ui_t('pages.categories_form.enter_category_ph') }}">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ ui_t('pages.categories_form.expiry_value') }}</label>
                                    <input type="number" min="1" name="expiry_value" value="{{ old('expiry_value') }}" class="form-control custom-input" placeholder="{{ ui_t('pages.categories_form.expiry_value_example') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ ui_t('pages.categories_form.expiry_unit') }}</label>
                                    <select name="expiry_unit" class="form-control custom-input" required>
                                        <option value="">{{ ui_t('pages.categories_form.select_unit') }}</option>
                                        <option value="days" {{ old('expiry_unit')=='days'?'selected':'' }}>{{ ui_t('pages.categories_form.days') }}</option>
                                        <option value="months" {{ old('expiry_unit')=='months'?'selected':'' }}>{{ ui_t('pages.categories_form.months') }}</option>
                                        <option value="years" {{ old('expiry_unit')=='years'?'selected':'' }}>{{ ui_t('pages.categories_form.years') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Services autorisés (direct)') }}</label>
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
                                            {{ in_array((string) $service->id, old('service_ids', []), true) ? 'selected' : '' }}>
                                            {{ $serviceLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('Ex: tous les services d’un pôle + un service spécifique d’un autre pôle.') }}</small>
                                @error('service_ids')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('service_ids.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Rôles autorisés (direct)') }}</label>
                                <select name="role_ids[]" class="form-select custom-input" multiple size="6">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ in_array((string) $role->id, old('role_ids', []), true) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('Ex: Direction, Assistante de direction, etc.') }}</small>
                                @error('role_ids')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('role_ids.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Profils d’accès liés (optionnel)') }}</label>
                                <select name="profile_ids[]" class="form-select custom-input" multiple size="8">
                                    @foreach($profiles as $profile)
                                        <option value="{{ $profile->id }}"
                                            {{ in_array((string) $profile->id, old('profile_ids', []), true) ? 'selected' : '' }}>
                                            {{ $profile->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('Associer cette catégorie à des profils existants (rôles/structure gérés dans Profils d’accès).') }}</small>
                                @error('profile_ids')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('profile_ids.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="subcategory-section">
                                <label class="form-label fw-medium mb-3">{{ ui_t('pages.categories_form.enter_subcategory') }} <span class="text-muted">{{ ui_t('pages.categories_form.optional') }}</span></label>
                                <div class="subcategory-visual-container">
                                    <div class="subcategory-container" id="subcategoryContainer">
                                        {{-- Subcategory inputs will be added dynamically by JS; no default value --}}
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-upload w-50 d-block mb-3" id="addSubcategoryBtn">{{ ui_t('pages.categories_form.add_new_subcategory') }}</button>
                            <button type="submit" class="btn-upload w-75 ">{{ ui_t('pages.categories_form.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Preview Section --}}
        <div class="row mt-5 w-50">
            <div class="col-md-8">
                <div class="">
                    <div class="card-body">
                        <h5 class="text-primary">{{ ui_t('pages.categories_form.live_preview') }}</h5>
                        <div class="mb-3">
                            <input type="text" class="form-control custom-input" id="liveCategory" placeholder="{{ ui_t('pages.categories_form.category_preview') }}" readonly>
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
        };
        const categoryInput = document.getElementById('categoryInput');
        const liveCategory = document.getElementById('liveCategory');
        const subcategoryContainer = document.getElementById('subcategoryContainer');
        const liveSubcategories = document.getElementById('liveSubcategories');
        const addBtn = document.getElementById('addSubcategoryBtn');

        // Sync category input
        categoryInput.addEventListener('input', function () {
            liveCategory.value = this.value;
        });

        // Helper: create a subcategory input with remove button
        function createSubcategoryItem(value = '') {
            const newItem = document.createElement('div');
            newItem.classList.add('subcategory-item', 'mb-3', 'd-flex', 'align-items-center');

            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = 'subcategories[]';
            newInput.classList.add('form-control', 'custom-input', 'subcategory-input');
            newInput.placeholder = translations.enterSubcategory;
            newInput.value = value;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.classList.add('btn', 'btn-sm', 'btn-danger', 'ms-2', 'remove-subcategory');
            removeBtn.title = translations.removeSubcategory;
            removeBtn.innerHTML = '<i class="fas fa-trash text-white"></i>';

            removeBtn.addEventListener('click', () => {
                newItem.remove();
                syncSubcategories();
            });

            newItem.appendChild(newInput);
            newItem.appendChild(removeBtn);

            // Also sync live preview on input change
            newInput.addEventListener('input', syncSubcategories);

            return newItem;
        }

        // Initial setup: replace existing inputs with new styled ones (to add remove buttons)
        function initializeSubcategories() {
            const oldItems = Array.from(subcategoryContainer.querySelectorAll('.subcategory-item'));
            oldItems.forEach(oldItem => {
                const val = oldItem.querySelector('input').value;
                const newItem = createSubcategoryItem(val);
                oldItem.replaceWith(newItem);
            });
        }

        // Sync subcategories preview
        function syncSubcategories() {
            liveSubcategories.innerHTML = '';
            const inputs = subcategoryContainer.querySelectorAll('.subcategory-input');
            inputs.forEach((input) => {
                if (input.value.trim() !== '') { // Only show non-empty subcategories in preview
                    const clone = input.cloneNode();
                    clone.setAttribute('readonly', true);
                    clone.value = input.value;
                    clone.classList.add('mb-3');
                    liveSubcategories.appendChild(clone);
                }
            });
        }

        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const newItem = createSubcategoryItem();
            subcategoryContainer.appendChild(newItem);
            syncSubcategories();
        });

        // Form submission handler to clean up empty subcategories
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const subcategoryInputs = subcategoryContainer.querySelectorAll('.subcategory-input');
            subcategoryInputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.disabled = true; // Disable empty inputs so they won't be submitted
                }
            });
        });

        // Run on page load
        initializeSubcategories();
        syncSubcategories();
    </script>
@endsection
