@props([
    'title',
    'subtitle' => null,
    'dense' => false,
])

@php
    $heroClass = 'lgv2-page-hero' . ($dense ? ' lgv2-page-hero--dense' : '');
@endphp

<div {{ $attributes->merge(['class' => $heroClass]) }}>
    <div class="row align-items-start align-items-md-center g-2 g-md-3 flex-column flex-md-row">
        <div class="col min-w-0">
            <h1 class="lgv2-page-hero__title">{{ $title }}</h1>
            @if ($subtitle)
                <p class="lgv2-page-hero__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="col-auto lgv2-page-hero__actions w-100 w-md-auto">
                <div class="d-flex gap-2 flex-wrap justify-content-start justify-content-md-end">
                    {{ $actions }}
                </div>
            </div>
        @endisset
    </div>
</div>

@isset($below)
    <div class="mb-3">
        {{ $below }}
    </div>
@endisset
