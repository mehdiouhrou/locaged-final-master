@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <x-page-hero
            :title="__('pages.my_categories.page_title')"
            :subtitle="__('pages.my_categories.page_subtitle')"
        >
            <x-slot:actions>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-angle-left me-1" aria-hidden="true"></i>{{ ui_t('nav.dashboard') }}
                </a>
            </x-slot:actions>
        </x-page-hero>

        @if($categories->isEmpty())
            <p class="text-muted">{{ __('pages.my_categories.empty') }}</p>
        @else
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-5">
                @php
                    $colors = [
                        ['bar' => '#f0d672', 'icon' => 'assets/Group 634.svg'],
                        ['bar' => '#e63946', 'icon' => 'assets/Group 6.svg'],
                        ['bar' => '#47a778', 'icon' => 'assets/Group 8.svg'],
                        ['bar' => '#68a0fd', 'icon' => 'assets/Clip path group.svg'],
                        ['bar' => '#ff6b6b', 'icon' => 'assets/Group 634.svg'],
                        ['bar' => '#4ecdc4', 'icon' => 'assets/Group 6.svg'],
                        ['bar' => '#45b7d1', 'icon' => 'assets/Group 8.svg'],
                        ['bar' => '#96ceb4', 'icon' => 'assets/Clip path group.svg'],
                    ];
                @endphp
                @foreach($categories->sortBy('name') as $category)
                    <div class="col">
                        @php
                            $color = $colors[$loop->index % count($colors)];
                        @endphp
                        <div class="category-card position-relative h-100">
                            <div class="category-card-bar" style="background-color: {{ $color['bar'] }}"></div>
                            <a href="{{ route('documents.by-category', ['categoryId' => $category->id]) }}" class="text-decoration-none stretched-link" aria-label="{{ $category->name }}">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="category-card-icon">
                                        <img src="{{ asset($color['icon']) }}" alt="" />
                                    </div>
                                    <div class="info-cat text-start">
                                        <h3 class="category-card-title mb-1">{{ $category->name }}</h3>
                                        <p class="category-card-count mb-0">{{ $category->subcategories_count }} {{ ui_t('pages.categories_page.subcategories') }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @parent
    <style>
        .category-card {
            background: #ffffff;
            border: 1px solid #eef0f3;
            border-radius: 12px;
            padding: 16px;
            padding-top: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            min-height: 96px;
        }
        .category-card-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: 12px 12px 0 0;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border-color: #e2e8f0;
        }
        .category-card:active { transform: translateY(-1px) scale(0.997); }
        .category-card:focus-within {
            outline: none;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.15);
            border-color: #cbd5e1;
        }
        .category-card-icon img {
            width: 52px;
            height: 52px;
            transition: transform .15s ease;
        }
        .category-card:hover .category-card-icon img { transform: scale(1.03); }
        .category-card-title {
            font-weight: 600;
            font-size: 16px;
            color: #1a1f36;
        }
        .category-card-count {
            font-size: 13px;
            color: #64748b;
        }
        @media (max-width: 576px) {
            .category-card { padding: 14px; }
            .category-card-icon img { width: 46px; height: 46px; }
            .category-card-title { font-size: 15px; }
            .category-card-count { font-size: 12px; }
        }
    </style>
@endsection
