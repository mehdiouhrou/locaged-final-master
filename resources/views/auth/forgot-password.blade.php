@extends('layouts.guest')

@section('content')

            <style>
                body { font-family: Helvetica, Arial, sans-serif !important; }
                .login-card { width: min(70%, 520px); margin: 0 auto; position: relative; padding-top: 40px; }
                .language-selector { 
                    position: absolute; 
                    top: 0; 
                    right: 0; 
                    z-index: 100;
                }
                .language-selector select {
                    padding: 6px  12px;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                    background-color: white;
                    font-size: 14px;
                    cursor: pointer;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .language-selector select:hover {
                    border-color: #adb5bd;
                }
                .language-selector select:focus {
                    outline: none;
                    border-color: #0d6efd;
                    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
                }
            </style>
            <div class="login-card">
                <!-- Language Selector -->
                <div class="language-selector">
                    <select id="languageSelect" class="form-select form-select-sm">
                        <option value="en">{{ ui_t('auth.ui.language_en') }}</option>
                        <option value="fr" selected>{{ ui_t('auth.ui.language_fr') }}</option>
                        <option value="ar">{{ ui_t('auth.ui.language_ar') }}</option>
                    </select>
                </div>

                <div class="text-center mb-4">
                    <img src="{{ \App\Support\Branding::headerLogoUrl() }}" alt="Logo" style="max-width: 220px; width: 100%;" />
                </div>

                <h4 class="mb-2 text-center" id="pageTitle">{{ ui_t('auth.ui.forgot_password_q') }}</h4>
                <p class="text-muted small text-center mb-4" id="pageSubtitle">{{ ui_t('auth.ui.enter_email_send_link') }}</p>

                @if (session('status'))
                    <div class="alert alert-success border-success-subtle bg-success-subtle text-success shadow-sm mb-4" role="status">
                        {{ session('status') }}
                    </div>
                @endif

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

                <form method="POST" action="{{ route('password.email') }}" class="mt-2" id="forgotPasswordForm">
                    @csrf
                    <input type="hidden" name="language" id="selectedLanguage" value="fr">

                    <div class="mb-3">
                        <label for="email" class="form-label" id="emailLabel">{{ ui_t('auth.ui.email') }}</label>
                        <input
                            id="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="{{ ui_t('auth.ui.email_placeholder') }}"
                            name="email"
                            value="{{ old('email') }}"
                            required autofocus autocomplete="email"
                        />
                    </div>

                    <button class="btn btn-primary w-100" type="submit" id="submitButton">{{ ui_t('auth.ui.email_reset_link') }}</button>
                </form>
            </div>

            <script>
                // Translation data
                const translations = {
                    en: {
                        pageTitle: "{!! ui_t('auth.ui.forgot_password_q', [], 'en') !!}",
                        pageSubtitle: "{!! ui_t('auth.ui.enter_email_send_link', [], 'en') !!}",
                        emailLabel: "{!! ui_t('auth.ui.email', [], 'en') !!}",
                        submitButton: "{!! ui_t('auth.ui.email_reset_link', [], 'en') !!}",
                        errorTitle: "{!! ui_t('auth.ui.we_couldnt_process', [], 'en') !!}"
                    },
                    fr: {
                        pageTitle: "{!! ui_t('auth.ui.forgot_password_q', [], 'fr') !!}",
                        pageSubtitle: "{!! ui_t('auth.ui.enter_email_send_link', [], 'fr') !!}",
                        emailLabel: "{!! ui_t('auth.ui.email', [], 'fr') !!}",
                        submitButton: "{!! ui_t('auth.ui.email_reset_link', [], 'fr') !!}",
                        errorTitle: "{!! ui_t('auth.ui.we_couldnt_process', [], 'fr') !!}"
                    },
                    ar: {
                        pageTitle: "{!! ui_t('auth.ui.forgot_password_q', [], 'ar') !!}",
                        pageSubtitle: "{!! ui_t('auth.ui.enter_email_send_link', [], 'ar') !!}",
                        emailLabel: "{!! ui_t('auth.ui.email', [], 'ar') !!}",
                        submitButton: "{!! ui_t('auth.ui.email_reset_link', [], 'ar') !!}",
                        errorTitle: "{!! ui_t('auth.ui.we_couldnt_process', [], 'ar') !!}"
                    }
                };

                // Set French as default
                document.addEventListener('DOMContentLoaded', function() {
                    const languageSelect = document.getElementById('languageSelect');
                    const selectedLanguage = document.getElementById('selectedLanguage');
                    
                    // Set default to French
                    languageSelect.value = 'fr';
                    selectedLanguage.value = 'fr';
                    updateLanguage('fr');

                    languageSelect.addEventListener('change', function() {
                        const lang = this.value;
                        selectedLanguage.value = lang;
                        updateLanguage(lang);
                    });
                });

                function updateLanguage(lang) {
                    const trans = translations[lang];
                    if (trans) {
                        document.getElementById('pageTitle').textContent = trans.pageTitle;
                        document.getElementById('pageSubtitle').textContent = trans.pageSubtitle;
                        document.getElementById('emailLabel').textContent = trans.emailLabel;
                        document.getElementById('submitButton').textContent = trans.submitButton;
                        
                        const errorTitle = document.getElementById('errorTitle');
                        if (errorTitle) {
                            errorTitle.textContent = trans.errorTitle;
                        }
                    }
                }
            </script>
@endsection
