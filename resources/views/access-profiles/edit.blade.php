@extends('layouts.app')

@section('content')
    <div class="container my-5">
        <div class="mb-4">
            <a href="{{ route('access-profiles.index') }}" class="text-decoration-none"><i class="fa-solid fa-angle-left me-1" aria-hidden="true"></i>{{ __('Back to list') }}</a>
            <h2 class="fw-bold mt-2">{{ __('Modifier le profil') }} : {{ $profile->name }}</h2>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" action="{{ route('access-profiles.update', $profile) }}">
                    @csrf
                    @method('PUT')
                    @include('access-profiles._form', ['profile' => $profile])
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-upload">{{ __('Enregistrer') }}</button>
                        <a href="{{ route('access-profiles.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
