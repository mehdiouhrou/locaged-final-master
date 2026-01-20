@extends('layouts.guest')

@section('content')
            <style>
                body { font-family: Helvetica, Arial, sans-serif !important; }
                .login-card { width: min(70%, 520px); margin: 0 auto; }
            </style>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/Logo 3.svg') }}" alt="Logo" style="max-width: 220px; width: 100%;" />
                </div>

                <h4 class="mb-2 text-center">{{ ui_t('auth.ui.reset_password') }}</h4>
                <p class="text-muted small text-center mb-4">Enter your new password below</p>

                @if ($errors->any())
                    <div class="alert d-flex align-items-start bg-danger-subtle border border-danger-subtle shadow-sm mb-4 fade show" role="alert" aria-live="assertive">
                        <span class="me-3 mt-1 text-danger" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 14a1 1 0 1 1-1-1 1 1 0 0 1 1 1Zm0-4a1 1 0 0 1-2 0V7a1 1 0 0 1 2 0Z"/>
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-danger mb-1">{{ ui_t('auth.ui.we_couldnt_process') }}</div>
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
                        <label for="email" class="form-label">{{ ui_t('auth.ui.email') }}</label>
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
                        <label for="password" class="form-label">{{ ui_t('auth.ui.password') }}</label>
                        <input
                            id="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password"
                            required
                            autocomplete="new-password"
                        />
                        <div class="form-text small">Must be at least 8 characters with uppercase, lowercase, numbers, and symbols</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">{{ ui_t('auth.ui.confirm_password') }}</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </div>

                    <button class="btn btn-primary w-100" type="submit">{{ ui_t('auth.ui.reset_password') }}</button>
                </form>
            </div>
@endsection

