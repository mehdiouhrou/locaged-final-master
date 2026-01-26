@extends('layouts.guest')

@section('content')
            <style>
                body { font-family: Helvetica, Arial, sans-serif !important; }
                .login-card { width: min(70%, 520px); margin: 0 auto; position: relative; }
                .language-selector { 
                    position: absolute; 
                    top: -10px; 
                    right: 0; 
                    z-index: 10;
                }
                .language-selector select {
                    padding: 5px 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    background-color: white;
                    font-size: 14px;
                }
            </style>
            <div class="login-card">
                <!-- Language Selector -->
                <div class="language-selector">
                    <select id="languageSelect" class="form-select form-select-sm">
                        <option value="en">{{ ui_t('auth.ui.language_en') }}</option>
                        <option value="fr">{{ ui_t('auth.ui.language_fr') }}</option>
                        <option value="ar">{{ ui_t('auth.ui.language_ar') }}</option>
                    </select>
                </div>

                <div class="text-center mb-4">
                    <img src="{{ asset('assets/Logo 3.svg') }}" alt="Logo" style="max-width: 220px; width: 100%;" />
                </div>

                <h4 class="mb-2 text-center" id="pageTitle">{{ ui_t('auth.ui.reset_password') }}</h4>
                <p class="text-muted small text-center mb-4" id="pageSubtitle">{{ ui_t('auth.ui.reset_password_subtitle') }}</p>

                @if ($errors->any())
                    <div class="alert d-flex align-items-start bg-danger-subtle border border-danger-subtle shadow-sm mb-4 fade show" role="alert" aria-live="assertive">
                        <span class="me-3 mt-1 text-danger" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 14a1 1 0 1 1-1-1 1 1 0 0 1 1 1Zm0-4a1 1 0 0 1-2 0V7a1 1 0 0 1 2 0Z"/>
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-danger mb-1" id="errorTitle">{{ ui_t('auth.ui.we_couldnt_process') }}</div>
                            <ul class="mb-0 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li class="mb-1">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="{{ ui_t('actions.close') }}"></button>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const firstInvalid = document.querySelector('.is-invalid');
                            if (firstInvalid) { try { firstInvalid.focus(); } catch (e) {} }
                        });
                    </script>
                @endif

                <form method="POST" action="{{ route('password.store') }}" class="mt-3">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label" id="emailLabel">{{ ui_t('auth.ui.email') }}</label>
                        <input
                            id="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            name="email"
                            value="{{ old('email', $request->email) }}"
                            required
                            autofocus
                            autocomplete="email"
                            readonly
                            style="background-color: #e9ecef; cursor: not-allowed;"
                        />
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label" id="passwordLabel">{{ ui_t('auth.ui.password') }}</label>
                        <input
                            id="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password"
                            required
                            autocomplete="new-password"
                        />
                        <div class="form-text small" id="passwordRequirements">{{ ui_t('auth.ui.password_requirements') }}</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label" id="confirmPasswordLabel">{{ ui_t('auth.ui.confirm_password') }}</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </div>

                    <button class="btn btn-primary w-100" type="submit" id="submitButton">{{ ui_t('auth.ui.reset_password') }}</button>
                </form>
            </div>

            <script>
                // Translation data
                const translations = {
                    en: {
                        pageTitle: "{{ ui_t('auth.ui.reset_password', [], 'en') }}",
                        pageSubtitle: "{{ ui_t('auth.ui.reset_password_subtitle', [], 'en') }}",
                        emailLabel: "{{ ui_t('auth.ui.email', [], 'en') }}",
                        passwordLabel: "{{ ui_t('auth.ui.password', [], 'en') }}",
                        confirmPasswordLabel: "{{ ui_t('auth.ui.confirm_password', [], 'en') }}",
                        passwordRequirements: "{{ ui_t('auth.ui.password_requirements', [], 'en') }}",
                        submitButton: "{{ ui_t('auth.ui.reset_password', [], 'en') }}",
                        errorTitle: "{{ ui_t('auth.ui.we_couldnt_process', [], 'en') }}"
                    },
                    fr: {
                        pageTitle: "{{ ui_t('auth.ui.reset_password', [], 'fr') }}",
                        pageSubtitle: "{{ ui_t('auth.ui.reset_password_subtitle', [], 'fr') }}",
                        emailLabel: "{{ ui_t('auth.ui.email', [], 'fr') }}",
                        passwordLabel: "{{ ui_t('auth.ui.password', [], 'fr') }}",
                        confirmPasswordLabel: "{{ ui_t('auth.ui.confirm_password', [], 'fr') }}",
                        passwordRequirements: "{{ ui_t('auth.ui.password_requirements', [], 'fr') }}",
                        submitButton: "{{ ui_t('auth.ui.reset_password', [], 'fr') }}",
                        errorTitle: "{{ ui_t('auth.ui.we_couldnt_process', [], 'fr') }}"
                    },
                    ar: {
                        pageTitle: "{{ ui_t('auth.ui.reset_password', [], 'ar') }}",
                        pageSubtitle: "{{ ui_t('auth.ui.reset_password_subtitle', [], 'ar') }}",
                        emailLabel: "{{ ui_t('auth.ui.email', [], 'ar') }}",
                        passwordLabel: "{{ ui_t('auth.ui.password', [], 'ar') }}",
                        confirmPasswordLabel: "{{ ui_t('auth.ui.confirm_password', [], 'ar') }}",
                        passwordRequirements: "{{ ui_t('auth.ui.password_requirements', [], 'ar') }}",
                        submitButton: "{{ ui_t('auth.ui.reset_password', [], 'ar') }}",
                        errorTitle: "{{ ui_t('auth.ui.we_couldnt_process', [], 'ar') }}"
                    }
                };

                // Get language from URL parameter or default to French
                document.addEventListener('DOMContentLoaded', function() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const langFromUrl = urlParams.get('lang') || 'fr';
                    
                    const languageSelect = document.getElementById('languageSelect');
                    languageSelect.value = langFromUrl;
                    updateLanguage(langFromUrl);

                    languageSelect.addEventListener('change', function() {
                        updateLanguage(this.value);
                    });
                });

                function updateLanguage(lang) {
                    const trans = translations[lang];
                    if (trans) {
                        document.getElementById('pageTitle').textContent = trans.pageTitle;
                        document.getElementById('pageSubtitle').textContent = trans.pageSubtitle;
                        document.getElementById('emailLabel').textContent = trans.emailLabel;
                        document.getElementById('passwordLabel').textContent = trans.passwordLabel;
                        document.getElementById('confirmPasswordLabel').textContent = trans.confirmPasswordLabel;
                        document.getElementById('passwordRequirements').textContent = trans.passwordRequirements;
                        document.getElementById('submitButton').textContent = trans.submitButton;
                        
                        const errorTitle = document.getElementById('errorTitle');
                        if (errorTitle) {
                            errorTitle.textContent = trans.errorTitle;
                        }
                    }
                }
            </script>
@endsection

