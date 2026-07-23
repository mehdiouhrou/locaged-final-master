@extends('layouts.app')

@section('content')
    <div class="pt-2">
        <h4 class="mb-4">Mes relectures</h4>
        @livewire('my-reviews-table')
    </div>
@endsection
