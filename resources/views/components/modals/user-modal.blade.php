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
            <div id="bypassRoleNotice" class="alert alert-info py-2 px-3 small d-none">
                <i class="fas fa-info-circle me-1"></i>
                {{ __('Ce rôle a accès à tous les documents — aucun périmètre requis.') }}
            </div>
            <div id="categoryAccessContainer">
                <label class="form-label fw-semibold mb-2">{{ __('Dossiers accessibles') }}</label>
                <input type="text" id="categorySearchInput" class="form-control form-control-sm mb-2"
                       placeholder="{{ __('Rechercher une catégorie...') }}">
                <div id="categoryCheckboxList"
                     style="max-height: 240px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.5rem;">
                    @forelse($categories as $category)
                        <div class="category-block mb-2" data-category-name="{{ strtolower($category->name) }}">
                            <div class="form-check">
                                <input class="form-check-input category-checkbox" type="checkbox"
                                       name="category_ids[]" value="{{ $category->id }}"
                                       id="cat_{{ $category->id }}">
                                <label class="form-check-label fw-semibold" for="cat_{{ $category->id }}">
                                    {{ $category->name }}
                                </label>
                            </div>
                            @foreach($category->subcategories as $subcategory)
                                <div class="subcategory-item ms-3 form-check"
                                     data-subcategory-name="{{ strtolower($subcategory->name) }}">
                                    <input class="form-check-input subcategory-checkbox" type="checkbox"
                                           name="subcategory_ids[]" value="{{ $subcategory->id }}"
                                           id="subcat_{{ $subcategory->id }}">
                                    <label class="form-check-label text-muted" for="subcat_{{ $subcategory->id }}">
                                        {{ $subcategory->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted small mb-0">{{ __('Aucune catégorie disponible.') }}</p>
                    @endforelse
                </div>
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
    const roleContextHint = document.getElementById("roleContextHint");

    // Password
    const setPasswordNowCheckbox = document.getElementById("setPasswordNowCheckbox");
    const setPasswordNowContainer = document.getElementById("setPasswordNowContainer");
    const passwordFieldsContainer = document.getElementById("passwordFieldsContainer");
    const passwordConfirmFieldsContainer = document.getElementById("passwordConfirmFieldsContainer");
    const passwordInput = document.getElementById("passwordInput");
    const passwordConfirmInput = document.getElementById("passwordConfirmationInput");

    // Wizard
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
        "Dossiers & sous-dossiers",
    ];
    let currentWizardStep = 0;

    // Catégories
    const bypassRoleNotice = document.getElementById("bypassRoleNotice");
    const categoryAccessContainer = document.getElementById("categoryAccessContainer");
    const categorySearchInput = document.getElementById("categorySearchInput");

    // Rôles bypass (accès total — pas de périmètre requis)
    const BYPASS_ROLES = [
        'master',
        'directrice générale',
        'it admin',
        'assistante de direction',
        'chargée de dépôt',
    ];

    function isBypassRole(roleName) {
        return BYPASS_ROLES.includes((roleName || '').toLowerCase());
    }

    function getSelectedRoleName() {
        if (!roleSelect) return '';
        const option = roleSelect.options[roleSelect.selectedIndex];
        return option && option.dataset.roleName ? option.dataset.roleName.toLowerCase() : '';
    }

    function applyRoleVisibility() {
        const roleName = getSelectedRoleName();
        const bypass = isBypassRole(roleName);

        if (roleContextHint) {
            roleContextHint.textContent = bypass
                ? "{{ __('Rôle global : accès à tous les documents sans restriction.') }}"
                : "{{ __('Sélectionnez les catégories que cet utilisateur peut consulter.') }}";
        }

        if (bypassRoleNotice) bypassRoleNotice.classList.toggle('d-none', !bypass);
        if (categoryAccessContainer) categoryAccessContainer.classList.toggle('d-none', bypass);

        if (bypass) {
            document.querySelectorAll('.category-checkbox, .subcategory-checkbox').forEach(cb => {
                cb.checked = false;
            });
        }
    }

    function filterCategories() {
        const query = (categorySearchInput?.value || '').trim().toLowerCase();
        document.querySelectorAll('#categoryCheckboxList .category-block').forEach(block => {
            const catName = block.dataset.categoryName || '';
            const subcatItems = block.querySelectorAll('.subcategory-item');
            let anySubMatch = false;

            subcatItems.forEach(item => {
                const subName = item.dataset.subcategoryName || '';
                const subMatch = query === '' || subName.includes(query) || catName.includes(query);
                item.style.display = subMatch ? '' : 'none';
                if (subMatch) anySubMatch = true;
            });

            const catMatch = query === '' || catName.includes(query);
            block.style.display = (catMatch || anySubMatch) ? '' : 'none';
        });
    }

    function togglePasswordFields() {
        const isCreating = formMethodInput.value === "POST";
        const setPasswordNow = setPasswordNowCheckbox?.checked;

        if (isCreating) {
            setPasswordNowContainer?.classList.remove("d-none");
            if (setPasswordNow) {
                passwordFieldsContainer?.classList.remove("d-none");
                passwordConfirmFieldsContainer?.classList.remove("d-none");
                if (passwordInput) passwordInput.required = true;
                if (passwordConfirmInput) passwordConfirmInput.required = true;
            } else {
                passwordFieldsContainer?.classList.add("d-none");
                passwordConfirmFieldsContainer?.classList.add("d-none");
                if (passwordInput) { passwordInput.required = false; passwordInput.value = ""; }
                if (passwordConfirmInput) { passwordConfirmInput.required = false; passwordConfirmInput.value = ""; }
            }
        } else {
            setPasswordNowContainer?.classList.add("d-none");
            passwordFieldsContainer?.classList.add("d-none");
            passwordConfirmFieldsContainer?.classList.add("d-none");
            if (passwordInput) { passwordInput.required = false; passwordInput.value = ""; }
            if (passwordConfirmInput) { passwordConfirmInput.required = false; passwordConfirmInput.value = ""; }
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

        wizardPrevBtn?.classList.toggle("d-none", currentWizardStep === 0);
        wizardNextBtn?.classList.toggle("d-none", currentWizardStep === total - 1);
        wizardSubmitBtn?.classList.toggle("d-none", currentWizardStep !== total - 1);
    }

    function validateCurrentStep() {
        if (currentWizardStep === 0) {
            if (!document.getElementById("emailInput")?.value.trim()) return false;
            if (!document.getElementById("fullName")?.value.trim()) return false;
            if (formMethodInput.value === "POST" && setPasswordNowCheckbox?.checked) {
                if (!passwordInput?.value.trim() || !passwordConfirmInput?.value.trim()) return false;
            }
        }
        if (currentWizardStep === 1) {
            if (!roleSelect?.value) return false;
        }
        return true;
    }

    function resetForm() {
        form.reset();
        document.getElementById("userId").value = "";
        formMethodInput.value = "POST";
        form.action = "{{ route('users.store') }}";

        if (setPasswordNowCheckbox) setPasswordNowCheckbox.checked = false;

        document.querySelectorAll('.category-checkbox, .subcategory-checkbox').forEach(cb => {
            cb.checked = false;
        });
        document.querySelectorAll('#categoryCheckboxList .category-block').forEach(b => b.style.display = '');
        document.querySelectorAll('.subcategory-item').forEach(i => i.style.display = '');
        if (categorySearchInput) categorySearchInput.value = '';

        applyRoleVisibility();
        togglePasswordFields();
        setWizardStep(0);
    }

    // Events
    setPasswordNowCheckbox?.addEventListener('change', togglePasswordFields);

    wizardPrevBtn?.addEventListener('click', () => setWizardStep(currentWizardStep - 1));
    wizardNextBtn?.addEventListener('click', () => {
        if (!validateCurrentStep()) return;
        if (currentWizardStep + 1 === 2) applyRoleVisibility();
        setWizardStep(currentWizardStep + 1);
    });

    roleSelect?.addEventListener('change', applyRoleVisibility);
    categorySearchInput?.addEventListener('input', filterCategories);

    document.getElementById("nextBtn")?.addEventListener("click", () => {
        resetForm();
        modal.classList.remove("d-none");
    });

    document.getElementById("exitProfileBtn")?.addEventListener("click", function () {
        modal.classList.add("d-none");
    });

    modal.addEventListener("click", function (e) {
        if (e.target.id === "promptLayer") modal.classList.add("d-none");
    });

    // Ouverture modale en mode édition
    document.querySelectorAll(".edit-user-btn").forEach(button => {
        button.addEventListener("click", () => {
            const userId = button.dataset.id;
            const categoryIds = (button.dataset.categoryIds || '').split(',').map(s => s.trim()).filter(Boolean);
            const subcategoryIds = (button.dataset.subcategoryIds || '').split(',').map(s => s.trim()).filter(Boolean);

            document.getElementById("userId").value = userId;
            document.getElementById("fullName").value = button.dataset.fullname;
            document.getElementById("emailInput").value = button.dataset.email;
            if (roleSelect) roleSelect.value = button.dataset.roleId;

            document.querySelectorAll('.category-checkbox').forEach(cb => {
                cb.checked = categoryIds.includes(cb.value);
            });
            document.querySelectorAll('.subcategory-checkbox').forEach(cb => {
                cb.checked = subcategoryIds.includes(cb.value);
            });

            form.action = `/users/${userId}`;
            formMethodInput.value = "PUT";

            applyRoleVisibility();
            togglePasswordFields();
            setWizardStep(0);
            modal.classList.remove("d-none");
        });
    });

    // Auto-open si erreurs de validation
    @if ($errors->any())
    document.addEventListener("DOMContentLoaded", function() {
        applyRoleVisibility();
        setWizardStep(0);
        modal.classList.remove("d-none");
    });
    @endif

</script>
