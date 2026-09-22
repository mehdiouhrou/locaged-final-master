<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">{{ ui_t('pages.users_page.reset_password.title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small" id="resetPasswordUserLabel"></p>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="resetPasswordNoEmail" name="no_email" value="1">
                        <label class="form-check-label" for="resetPasswordNoEmail">
                            {{ ui_t('pages.users_page.reset_password.no_email_checkbox') }}
                        </label>
                        <div class="form-text">{{ ui_t('pages.users_page.reset_password.no_email_hint') }}</div>
                    </div>

                    <div id="resetPasswordEmailInfo" class="alert alert-info small mb-0">
                        {{ ui_t('pages.users_page.reset_password.email_mode_info') }}
                    </div>

                    <div id="resetPasswordManualFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">{{ ui_t('pages.users_page.user_modal.password') }}</label>
                            <input type="password" class="form-control" name="password" id="resetPasswordManualPassword">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ ui_t('pages.users_page.user_modal.password_confirmation') }}</label>
                            <input type="password" class="form-control" name="password_confirmation" id="resetPasswordManualPasswordConfirmation">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ ui_t('pages.users_page.reset_password.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
if (!window.__RESET_PASSWORD_MODAL_BOUND__) {
    window.__RESET_PASSWORD_MODAL_BOUND__ = true;

    const resetModal = document.getElementById('resetPasswordModal');
    const resetForm = document.getElementById('resetPasswordForm');
    const userLabel = document.getElementById('resetPasswordUserLabel');
    const noEmailCheckbox = document.getElementById('resetPasswordNoEmail');
    const emailInfo = document.getElementById('resetPasswordEmailInfo');
    const manualFields = document.getElementById('resetPasswordManualFields');
    const manualPassword = document.getElementById('resetPasswordManualPassword');
    const manualPasswordConfirmation = document.getElementById('resetPasswordManualPasswordConfirmation');

    function toggleManualFields() {
        const isManual = noEmailCheckbox.checked;
        manualFields.classList.toggle('d-none', !isManual);
        emailInfo.classList.toggle('d-none', isManual);
        manualPassword.required = isManual;
        manualPasswordConfirmation.required = isManual;
    }

    noEmailCheckbox.addEventListener('change', toggleManualFields);

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-reset-password-btn');
        if (!btn) {
            return;
        }
        e.preventDefault();

        resetForm.action = btn.dataset.url || '#';
        userLabel.textContent = btn.dataset.userName || '';
        noEmailCheckbox.checked = false;
        manualPassword.value = '';
        manualPasswordConfirmation.value = '';
        toggleManualFields();

        const bsModal = new bootstrap.Modal(resetModal);
        bsModal.show();
    });
}
</script>
