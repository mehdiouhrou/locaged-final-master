{{-- Modal --}}
<div class="layer d-none" id="promptLayer">
    <div class="profile-edit-box profile-edit-box2">

        <div class="header bg-transparent">
            <span>{{ ui_t('pages.users_page.user_modal.title') }}</span>
            <button class="exit" id="exitProfileBtn" style="background: none; border: none;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form class="role-info" method="POST" id="userForm" enctype="multipart/form-data"     action="{{ old('id') !== null ? url('users/' . old('id')) : route('users.store') }}">
            @csrf
            <input type="hidden" id="formMethod" name="_method" value="{{ old('id') !== null ? 'PUT' : 'POST' }}">
            <input type="hidden" id="userId" name="id" value="{{ old('id') }}">

            <div class="mb-3">
                <div class="d-flex align-items-center gap-2 small">
                    <span class="badge rounded-pill text-bg-dark" id="wizardStepBadge">1/3</span>
                    <span id="wizardStepTitle" class="fw-semibold">{{ __('Informations de base') }}</span>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar" id="wizardProgress" role="progressbar" style="width: 33%;"></div>
                </div>
            </div>

            <div id="wizardStep1">
            <div class="mb-3">
                <label class="form-label">{{ ui_t('pages.users_page.user_modal.email') }}</label>
                <input type="text"
                       class="form-control @error('email') is-invalid @enderror"
                       id="emailInput"
                       name="email"
                       placeholder="{{ ui_t('pages.users_page.user_modal.enter_email') }}"
                       value="{{ old('email') }}">
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">{{ ui_t('pages.users_page.user_modal.full_name') }}</label>
                <input type="text"
                       class="form-control @error('full_name') is-invalid @enderror"
                       id="fullName"
                       name="full_name"
                       placeholder="{{ ui_t('pages.users_page.user_modal.full_name') }}"
                       value="{{ old('full_name') }}">
                @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">{{ ui_t('pages.users_page.profile_image') }}</label>
                <input type="file"
                       class="form-control @error('profile_image') is-invalid @enderror"
                       id="profileImageInputAdmin"
                       name="profile_image"
                       accept="image/*">
                @error('profile_image')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            

            {{-- Checkbox to set password now (only shown when creating new user) --}}
            <div class="mb-3" id="setPasswordNowContainer">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="setPasswordNowCheckbox" name="set_password_now" value="1">
                    <label class="form-check-label" for="setPasswordNowCheckbox">
                        {{ ui_t('pages.users_page.user_modal.set_password_now') }}
                    </label>
                    <small class="form-text text-muted d-block">
                        {{ ui_t('pages.users_page.user_modal.set_password_now_hint') }}
                    </small>
                </div>
            </div>

            <div class="mb-3" id="passwordFieldsContainer">
                <label class="form-label">{{ ui_t('pages.users_page.user_modal.password') }}</label>
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="passwordInput"
                       name="password"
                       placeholder="{{ ui_t('pages.users_page.user_modal.password') }}" >
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" id="passwordConfirmFieldsContainer">
                <label class="form-label">{{ ui_t('pages.users_page.user_modal.confirm_password') }}</label>
                <input type="password"
                       class="form-control"
                       id="passwordConfirmationInput"
                       name="password_confirmation"
                       placeholder="{{ ui_t('pages.users_page.user_modal.password_confirmation') }}" >
            </div>
            </div>

            <div id="wizardStep2" class="d-none">
            <label for="roleSelect" class="mb-2">{{ ui_t('pages.users_page.user_modal.role') }}</label>
            <select id="roleSelect" name="role" class="form-select mb-4 @error('role') is-invalid @enderror">
                @foreach($roles as $role)
                    <option
                        value="{{ $role->id }}"
                        data-role-name="{{ strtolower($role->name) }}"
                        {{ old('role') == $role->id ? 'selected' : '' }}
                    >
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="alert alert-info py-2 px-3 small mb-0" id="roleContextHint"></div>
            </div>

            <div id="wizardStep3" class="d-none">
            <div id="departmentContainer" class="mb-3">
                <label for="departmentSelect" class="mb-2">{{ ui_t('pages.users_page.user_modal.structures') }}</label>
                <input type="text" id="departmentSearchInput" class="form-control form-control-sm mb-2" placeholder="{{ __('Rechercher un pôle...') }}">
                <select id="departmentSelect" name="departments[]" class="form-select mb-2 @error('departments') is-invalid @enderror" multiple size="5">
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ (is_array(old('departments')) && in_array($department->id, old('departments'))) ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block">{{ ui_t('pages.users_page.user_modal.structures_hint') }}</small>
                @error('departments')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Sub-Department selector (multi-select, filtered by selected structures) --}}
            <div id="subDepartmentContainer" class="mb-3 d-none">
                <label for="subDepartmentSelect" class="form-label">{{ ui_t('pages.users_page.sub_departments_label') }}</label>
                <input type="text" id="subDepartmentSearchInput" class="form-control form-control-sm mb-2" placeholder="{{ __('Rechercher un département...') }}">
                <select id="subDepartmentSelect" name="sub_departments[]" class="form-select" multiple size="5">
                    @foreach($departments as $department)
                        @foreach($department->subDepartments as $subDepartment)
                            <option
                                value="{{ $subDepartment->id }}"
                                data-department-id="{{ $department->id }}"
                                @if(is_array(old('sub_departments')) && in_array($subDepartment->id, old('sub_departments'))) selected @endif
                            >
                                {{ $subDepartment->name }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('sub_departments')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- Service selector (shown for Service User / User when applicable) --}}
            <div id="serviceContainer" class="mb-3 d-none">
                <label for="serviceSelect" class="form-label">{{ ui_t('pages.users_page.services_label') }}</label>
                <input type="text" id="serviceSearchInput" class="form-control form-control-sm mb-2" placeholder="{{ __('Rechercher un service...') }}">
                <select id="serviceSelect" name="services[]" class="form-select" multiple size="5">
                    @foreach($departments as $department)
                        @foreach($department->subDepartments as $subDepartment)
                            @foreach($subDepartment->services as $service)
                                <option
                                    value="{{ $service->id }}"
                                    data-department-id="{{ $department->id }}"
                                    data-sub-department-id="{{ $subDepartment->id }}"
                                    @if(is_array(old('services')) && in_array($service->id, old('services'))) selected @endif
                                >
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        @endforeach
                    @endforeach
                </select>
                @error('services')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4 d-none" id="wizardPrevBtn">{{ __('Précédent') }}</button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary px-4" id="wizardNextBtn">{{ __('Suivant') }}</button>
                    <button type="submit" class="btn btn-update px-4 d-none" id="wizardSubmitBtn">{{ ui_t('pages.users_page.user_modal.save') }}</button>
                </div>
            </div>
        </form>

    </div>
</div>


<script>
    const modal = document.getElementById("promptLayer");
    const form = document.getElementById("userForm");
    const formMethodInput = document.getElementById("formMethod");
    const roleSelect = document.getElementById("roleSelect");
    const departmentContainer = document.getElementById("departmentContainer");
    const departmentSelect = document.getElementById("departmentSelect");
    const subDepartmentContainer = document.getElementById("subDepartmentContainer");
    const subDepartmentSelect = document.getElementById("subDepartmentSelect");
    const serviceContainer = document.getElementById("serviceContainer");
    const serviceSelect = document.getElementById("serviceSelect");
    
    // Password checkbox and containers
    const setPasswordNowCheckbox = document.getElementById("setPasswordNowCheckbox");
    const setPasswordNowContainer = document.getElementById("setPasswordNowContainer");
    const passwordFieldsContainer = document.getElementById("passwordFieldsContainer");
    const passwordConfirmFieldsContainer = document.getElementById("passwordConfirmFieldsContainer");
    const passwordInput = document.getElementById("passwordInput");
    const passwordConfirmInput = document.getElementById("passwordConfirmationInput");
    const roleContextHint = document.getElementById("roleContextHint");

    // Wizard elements
    const stepContainers = [
        document.getElementById("wizardStep1"),
        document.getElementById("wizardStep2"),
        document.getElementById("wizardStep3"),
    ];
    const wizardStepBadge = document.getElementById("wizardStepBadge");
    const wizardStepTitle = document.getElementById("wizardStepTitle");
    const wizardProgress = document.getElementById("wizardProgress");
    const wizardPrevBtn = document.getElementById("wizardPrevBtn");
    const wizardNextBtn = document.getElementById("wizardNextBtn");
    const wizardSubmitBtn = document.getElementById("wizardSubmitBtn");
    const wizardTitles = [
        "{{ __('Informations de base') }}",
        "{{ __('Rôle utilisateur') }}",
        "{{ __('Périmètre organisationnel') }}",
    ];
    let currentWizardStep = 0;

    // Searchable selects
    const departmentSearchInput = document.getElementById("departmentSearchInput");
    const subDepartmentSearchInput = document.getElementById("subDepartmentSearchInput");
    const serviceSearchInput = document.getElementById("serviceSearchInput");

    // Toggle password fields visibility based on checkbox and mode (create/edit)
    function togglePasswordFields() {
        const isCreating = formMethodInput.value === "POST";
        const setPasswordNow = setPasswordNowCheckbox.checked;
        
        if (isCreating) {
            // Creating new user
            setPasswordNowContainer.classList.remove("d-none");
            
            if (setPasswordNow) {
                // Show password fields if checkbox is checked
                passwordFieldsContainer.classList.remove("d-none");
                passwordConfirmFieldsContainer.classList.remove("d-none");
                passwordInput.required = true;
                passwordConfirmInput.required = true;
            } else {
                // Hide password fields if checkbox unchecked
                passwordFieldsContainer.classList.add("d-none");
                passwordConfirmFieldsContainer.classList.add("d-none");
                passwordInput.required = false;
                passwordConfirmInput.required = false;
                passwordInput.value = "";
                passwordConfirmInput.value = "";
            }
        } else {
            // Editing existing user - always show password fields (optional), hide checkbox
            setPasswordNowContainer.classList.add("d-none");
            passwordFieldsContainer.classList.remove("d-none");
            passwordConfirmFieldsContainer.classList.remove("d-none");
            passwordInput.required = false;
            passwordConfirmInput.required = false;
        }
    }

    function setWizardStep(stepIndex) {
        currentWizardStep = Math.max(0, Math.min(stepIndex, stepContainers.length - 1));
        stepContainers.forEach((el, idx) => {
            if (!el) return;
            el.classList.toggle("d-none", idx !== currentWizardStep);
        });

        const total = stepContainers.length;
        if (wizardStepBadge) wizardStepBadge.textContent = `${currentWizardStep + 1}/${total}`;
        if (wizardStepTitle) wizardStepTitle.textContent = wizardTitles[currentWizardStep] || "";
        if (wizardProgress) wizardProgress.style.width = `${((currentWizardStep + 1) / total) * 100}%`;

        if (wizardPrevBtn) wizardPrevBtn.classList.toggle("d-none", currentWizardStep === 0);
        if (wizardNextBtn) wizardNextBtn.classList.toggle("d-none", currentWizardStep === total - 1);
        if (wizardSubmitBtn) wizardSubmitBtn.classList.toggle("d-none", currentWizardStep !== total - 1);
    }

    function validateCurrentStep() {
        if (currentWizardStep === 0) {
            if (!document.getElementById("emailInput")?.value.trim()) return false;
            if (!document.getElementById("fullName")?.value.trim()) return false;
            if (formMethodInput.value === "POST" && setPasswordNowCheckbox.checked) {
                if (!passwordInput.value.trim() || !passwordConfirmInput.value.trim()) return false;
            }
        }
        if (currentWizardStep === 1) {
            if (!roleSelect?.value) return false;
        }
        return true;
    }

    function getSelectedRoleName() {
        if (!roleSelect) return '';
        const option = roleSelect.options[roleSelect.selectedIndex];
        return option && option.dataset.roleName ? option.dataset.roleName.toLowerCase() : '';
    }

    function applyRoleOrgVisibility() {
        const roleName = getSelectedRoleName();

        const config = {
            showDepartment: false,
            departmentRequired: false,
            showSubDepartment: false,
            subDepartmentRequired: false,
            showService: false,
            serviceRequired: false,
        };

        if (roleName === 'master' || roleName === 'super administrator') {
            // No org structure required or shown
        } else if (roleName === 'admin de pole' || roleName === 'department administrator') {
            // Department admin: only structures (departments)
            config.showDepartment = true;
            config.departmentRequired = true;
        } else if (roleName === 'admin de departments' || roleName === 'division chief') {
            // Sub-department admin: structures + filtered sub-departments
            config.showDepartment = true;
            config.departmentRequired = true;
            config.showSubDepartment = true;
            config.subDepartmentRequired = true;
        } else if (roleName === 'admin de cellule' || roleName === 'service manager' || roleName === 'user' || roleName === 'service user') {
            // Service-level roles: structures + sub-departments + services
            config.showDepartment = true;
            config.departmentRequired = true;
            config.showSubDepartment = true;
            config.subDepartmentRequired = true;
            config.showService = true;
            config.serviceRequired = true;
        } else {
            // Any other custom role: all dropdowns visible but optional
            config.showDepartment = true;
            config.showSubDepartment = true;
            config.showService = true;
        }

        if (roleContextHint) {
            let hint = "{{ __('Accès personnalisé: vérifiez le périmètre selon ce rôle.') }}";
            if (roleName === 'master' || roleName === 'super administrator') {
                hint = "{{ __('Rôle global: pas de rattachement structure requis.') }}";
            } else if (roleName === 'admin de pole' || roleName === 'department administrator') {
                hint = "{{ __('Ce rôle doit être rattaché à un ou plusieurs pôles.') }}";
            } else if (roleName === 'admin de departments' || roleName === 'division chief') {
                hint = "{{ __('Ce rôle doit être rattaché à des pôles et départements.') }}";
            } else if (roleName === 'admin de cellule' || roleName === 'service manager' || roleName === 'user' || roleName === 'service user') {
                hint = "{{ __('Ce rôle nécessite un rattachement service (avec pôle + département).') }}";
            }
            roleContextHint.textContent = hint;
        }

        if (departmentContainer) {
            departmentContainer.classList.toggle('d-none', !config.showDepartment);
        }
        if (departmentSelect) {
            departmentSelect.required = !!config.departmentRequired;
            if (!config.showDepartment) {
                Array.from(departmentSelect.options).forEach(opt => (opt.selected = false));
            }
        }

        if (subDepartmentContainer) {
            subDepartmentContainer.classList.toggle('d-none', !config.showSubDepartment);
        }
        if (subDepartmentSelect) {
            subDepartmentSelect.required = !!config.subDepartmentRequired;
            if (!config.showSubDepartment) {
                Array.from(subDepartmentSelect.options).forEach(opt => (opt.selected = false));
            }
        }

        if (serviceContainer) {
            serviceContainer.classList.toggle('d-none', !config.showService);
        }
            if (serviceSelect) {
                serviceSelect.required = !!config.serviceRequired;
                if (!config.showService) {
                    Array.from(serviceSelect.options).forEach(opt => (opt.selected = false));
                }
            }
    }

    function applySelectOptionVisibility(option) {
        const orgVisible = option.dataset.orgVisible !== '0';
        const searchVisible = option.dataset.searchVisible !== '0';
        option.hidden = !(orgVisible && searchVisible);
        if (option.hidden) {
            option.selected = false;
        }
    }

    function filterSelectByText(select, searchInput) {
        if (!select || !searchInput) return;
        const query = (searchInput.value || '').trim().toLowerCase();
        Array.from(select.options).forEach(option => {
            option.dataset.searchVisible = query === '' || option.text.toLowerCase().includes(query) ? '1' : '0';
            applySelectOptionVisibility(option);
        });
    }

    function filterSubDepartmentsByDepartments() {
        if (!departmentSelect || !subDepartmentSelect) return;
        const selectedDepartmentIds = Array.from(departmentSelect.selectedOptions)
            .map(o => o.value)
            .filter(v => v !== '');

        Array.from(subDepartmentSelect.options).forEach(option => {
            const deptId = option.dataset.departmentId;
            option.dataset.orgVisible = (selectedDepartmentIds.length > 0 && (!deptId || !selectedDepartmentIds.includes(deptId))) ? '0' : '1';
            applySelectOptionVisibility(option);
        });

        filterSelectByText(subDepartmentSelect, subDepartmentSearchInput);
        filterServicesBySubDepartment();
    }

    function filterServicesBySubDepartment() {
        if (!subDepartmentSelect || !serviceSelect) return;
        const selectedSubDeptIds = Array.from(subDepartmentSelect.selectedOptions)
            .map(o => o.value)
            .filter(v => v !== '');

        Array.from(serviceSelect.options).forEach(option => {
            const optionSubDeptId = option.dataset.subDepartmentId;
            const match = selectedSubDeptIds.length === 0 || selectedSubDeptIds.includes(optionSubDeptId);
            option.dataset.orgVisible = match ? '1' : '0';
            applySelectOptionVisibility(option);
        });

        filterSelectByText(serviceSelect, serviceSearchInput);
    }

    function resetForm() {
        form.reset();
        document.getElementById("userId").value = ""; // reset hidden user ID
        formMethodInput.value = "POST";
        form.action = "{{ route('users.store') }}"; // default to create

        // Reset password checkbox
        if (setPasswordNowCheckbox) {
            setPasswordNowCheckbox.checked = false;
        }

        if (departmentSelect) {
            Array.from(departmentSelect.options).forEach(opt => (opt.selected = false));
        }
        if (subDepartmentSelect) {
            Array.from(subDepartmentSelect.options).forEach(opt => (opt.selected = false));
        }
        if (serviceSelect) {
            Array.from(serviceSelect.options).forEach(opt => (opt.selected = false));
        }

        applyRoleOrgVisibility();
        filterSubDepartmentsByDepartments();
        filterServicesBySubDepartment();
        togglePasswordFields(); // Update password fields visibility
        filterSelectByText(departmentSelect, departmentSearchInput);
        filterSelectByText(subDepartmentSelect, subDepartmentSearchInput);
        filterSelectByText(serviceSelect, serviceSearchInput);
        setWizardStep(0);
    }

    // Event listener for password checkbox
    setPasswordNowCheckbox?.addEventListener('change', togglePasswordFields);
    wizardPrevBtn?.addEventListener('click', () => setWizardStep(currentWizardStep - 1));
    wizardNextBtn?.addEventListener('click', () => {
        if (!validateCurrentStep()) {
            return;
        }
        setWizardStep(currentWizardStep + 1);
    });

    departmentSearchInput?.addEventListener('input', () => filterSelectByText(departmentSelect, departmentSearchInput));
    subDepartmentSearchInput?.addEventListener('input', () => filterSelectByText(subDepartmentSelect, subDepartmentSearchInput));
    serviceSearchInput?.addEventListener('input', () => filterSelectByText(serviceSelect, serviceSearchInput));

    document.getElementById("nextBtn")?.addEventListener("click", () => {
        resetForm();
        modal.classList.remove("d-none");
    });

    roleSelect?.addEventListener('change', () => {
        applyRoleOrgVisibility();
        filterSubDepartmentsByDepartments();
    });

    departmentSelect?.addEventListener('change', () => {
        filterSubDepartmentsByDepartments();
    });

    subDepartmentSelect?.addEventListener('change', () => {
        filterServicesBySubDepartment();
    });

    document.getElementById("exitProfileBtn")?.addEventListener("click", function () {
        modal.classList.add("d-none");
       // resetForm();
    });

    modal.addEventListener("click", function (e) {
        if (e.target.id === "promptLayer") {
            modal.classList.add("d-none");
        }
    });

    // Open modal on Edit click
    document.querySelectorAll(".edit-user-btn").forEach(button => {
        button.addEventListener("click", () => {
            const userId = button.dataset.id;
            const fullname = button.dataset.fullname;
            const email = button.dataset.email;
            const roleId = button.dataset.roleId;
            const departmentIds = button.dataset.departmentIds; // Comma-separated IDs
            const subDepartmentIds = button.dataset.subDepartmentIds || ''; // Comma-separated IDs
            const serviceIds = (button.dataset.serviceIds || '').split(',').map(id => id.trim()).filter(id => id !== '');

            if (roleSelect) {
                roleSelect.value = roleId;
            }
            document.getElementById("userId").value = userId;
            document.getElementById("fullName").value = fullname;
            
            document.getElementById("emailInput").value = email;
            
            // Clear all department selections first
            if (departmentSelect) {
                Array.from(departmentSelect.options).forEach(option => option.selected = false);

                // Select multiple departments
                if (departmentIds) {
                    const ids = departmentIds.split(',').map(id => id.trim());
                    Array.from(departmentSelect.options).forEach(option => {
                        if (ids.includes(option.value)) {
                            option.selected = true;
                        }
                    });
                }
            }

            applyRoleOrgVisibility();
            filterSubDepartmentsByDepartments();

            // Clear all sub-department selections first
            if (subDepartmentSelect) {
                Array.from(subDepartmentSelect.options).forEach(option => option.selected = false);

                if (subDepartmentIds) {
                    const ids = subDepartmentIds.split(',').map(id => id.trim());
                    Array.from(subDepartmentSelect.options).forEach(option => {
                        if (ids.includes(option.value)) {
                            option.selected = true;
                        }
                    });
                }
            }

            filterServicesBySubDepartment();

            if (serviceSelect) {
                Array.from(serviceSelect.options).forEach(option => {
                    option.selected = serviceIds.includes(option.value);
                });
            }

            form.action = `/users/${userId}`;
            formMethodInput.value = "PUT";

            // Clear password fields for security
            document.getElementById("passwordInput").value = "";
            document.getElementById("passwordConfirmationInput").value = "";

            togglePasswordFields(); // Update password fields visibility for edit mode
            setWizardStep(0);
            filterSelectByText(departmentSelect, departmentSearchInput);
            filterSelectByText(subDepartmentSelect, subDepartmentSearchInput);
            filterSelectByText(serviceSelect, serviceSearchInput);

            modal.classList.remove("d-none");
        });
    });



    // Auto-open modal if validation errors exist (optional)
    @if ($errors->any())
    document.addEventListener("DOMContentLoaded", function() {
        applyRoleOrgVisibility();
        filterSubDepartmentsByDepartments();
        filterServicesBySubDepartment();
        filterSelectByText(departmentSelect, departmentSearchInput);
        filterSelectByText(subDepartmentSelect, subDepartmentSearchInput);
        filterSelectByText(serviceSelect, serviceSearchInput);
        setWizardStep(0);
        modal.classList.remove("d-none");
    });
    @endif

</script>
