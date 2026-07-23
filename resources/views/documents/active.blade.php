@extends('layouts.app')

@section('content')
    <div class="pt-2">
        <h4 class="mb-4">Documents actifs</h4>
        @livewire('my-active-documents-table')
    </div>
@endsection
