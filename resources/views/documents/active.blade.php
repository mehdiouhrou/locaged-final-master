@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-flex justify-content-between mb-5">
            <div class="d-flex align-items-center all-cat">
                <a href="{{ route('documents.all') }}">
                    <h4 class="me-3">{{ ui_t('nav.documents') }} <i class="fa-solid fa-angle-right"></i></h4>
                </a>
                <h5 class="me-3">{{ __('Documents actifs') }}</h5>
            </div>
        </div>
        @livewire('my-active-documents-table')
    </div>
@endsection
