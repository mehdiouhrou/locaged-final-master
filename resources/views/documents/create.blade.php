@extends('layouts.app')

@section('content')
    <div class="pt-2">
        @if (request('mode') === 'archive')
            @livewire('multiple-documents-create-form', [
                'folderId'   => request('folder_id'),
                'categoryId' => request('category_id'),
            ])
        @elseif (request('mode') === 'collaboratif')
            @livewire('collaborative-document-create-form')
        @else
            <div class="d-flex flex-column align-items-center justify-content-center py-5">
                <h4 class="mb-4">Que souhaitez-vous faire ?</h4>
                <div class="d-flex gap-3">
                    <a href="{{ route('documents.create', array_merge(request()->query(), ['mode' => 'archive'])) }}"
                       class="btn btn-dark btn-lg px-4 py-3">
                        Archive
                    </a>
                    <a href="{{ route('documents.create', array_merge(request()->query(), ['mode' => 'collaboratif'])) }}"
                       class="btn btn-outline-dark btn-lg px-4 py-3">
                        Document actif
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/upload-pdf-preview.js'])
@endsection
