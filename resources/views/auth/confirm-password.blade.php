@extends('layouts.guest')

@section('content')
    <div class="login-card" style="width: min(70%, 520px); margin: 0 auto;">
        <div class="text-center mb-4">
            <img src="{{ asset('assets/Logo 3.svg') }}" alt="Logo" style="max-width: 220px; width: 100%;" />
        </div>
        <h5 class="text-center mb-3">{{ __('Confirmation du mot de passe') }}</h5>
        <p class="text-muted small text-center mb-4">{{ __('Pour des raisons de sécurité, confirmez votre mot de passe pour continuer.') }}</p>

        @if ($errors->any())
            <div class="alert alert-danger small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('password.confirm') }}">
            @csrf
            <div class="mb-3">
                <label for="password" class="form-label">{{ ui_t('auth.ui.password') }}</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required
                    autocomplete="current-password"
                    autofocus
                />
            </div>
            <button type="submit" class="btn btn-primary w-100">{{ __('Confirmer') }}</button>
        </form>
    </div>
@endsection
