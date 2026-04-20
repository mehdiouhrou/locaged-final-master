<div>
    <div class="header-left">
        <div class="search-box d-flex align-items-start gap-2 flex-wrap">
            <div class="position-relative header-search-wrap">
                <input
                    type="text"
                    name="search"
                    placeholder="{{ __('actions.search') }}"
                    wire:model.live.debounce="query"
                    class="form-control pe-5 header-search-input"
                    autocomplete="off"
                    wire:keydown.enter.prevent="goToDocuments"
                />

                <!-- Persistent search icon over the input -->
                <i class="fas fa-magnifying-glass search-icon text-secondary"></i>

                <button
                    class="position-absolute border-0 text-secondary header-search-filter-btn"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#filterOverlay"
                >
                    <i class="fas fa-filter filter-icon" style="position: static;">
                        @if($this->activeFiltersCount > 0)
                            <span style="
            position: absolute;
            top: -6px;
            right: -8px;
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 50%;
            background-color: red;
            color: white;
        ">
                {{ $this->activeFiltersCount }}

        </span>
                        @endif
                    </i>
                </button>

                <!-- Filter Overlay (absolute, does not push layout) -->
                <div id="filterOverlay" class="collapse position-absolute top-100 start-0 search-filter-overlay">
                    <div class="card shadow border-0 rounded-3 mt-2">
                        <div class="card-body p-3 p-md-4" style="max-height: 70vh; overflow: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold">{{ __('filters.filters') }}</h6>
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#filterOverlay" aria-label="{{ __('actions.close') }}">{{ __('actions.close') }}</button>
                            </div>

                            <form wire:submit.prevent="applyFilters">
                                <div class="row g-3">
                                    <!-- Document Type -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('filters.file_type') }}</label>
                                        <select class="form-select" name="type" wire:model="filters.type">
                                            <option value="">{{ __('filters.select_type') }}</option>
                                            <option value="pdf">{{ __('filters.types.pdf') }}</option>
                                            <option value="doc">{{ __('filters.types.word') }}</option>
                                            <option value="image">{{ __('filters.types.image') }}</option>
                                            <option value="excel">{{ __('filters.types.excel') }}</option>
                                            <option value="video">{{ __('filters.types.video') }}</option>
                                            <option value="audio">{{ __('filters.types.audio') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('pages.upload.category') }}</label>
                                        <select class="form-select" name="category_id" wire:model="filters.category_id">
                                            <option value="">{{ __('filters.all') }}</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('filters.status') }}</label>
                                        <select class="form-select" name="status" wire:model="filters.status">
                                            <option value="">{{ __('filters.all') }}</option>
                                            @foreach($statuses as $status)
                                                <option value="{{ $status }}">{{ __('pages.documents.status.' . $status) }}</option>
                                            @endforeach
                                            <option value="expired">{{ __('pages.documents.status.expired') }}</option>
                                        </select>
                                    </div>

                                    <!-- Creation Date Range -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('pages.versions.creation_date') }}</label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input
                                                    type="date"
                                                    id="creationStartDate"
                                                    name="creation_start"
                                                    class="form-control"
                                                    placeholder="{{ __('filters.from') }}"
                                                    wire:model="filters.creation_start"
                                                >
                                            </div>
                                            <div class="col-6">
                                                <input
                                                    type="date"
                                                    id="creationEndDate"
                                                    name="creation_end"
                                                    class="form-control"
                                                    placeholder="{{ __('filters.to') }}"
                                                    wire:model="filters.creation_end"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tags (comma-separated tag names) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('filters.tags') }}</label>
                                        <input
                                            type="text"
                                            name="tags"
                                            class="form-control"
                                            placeholder="{{ __('filters.tags_placeholder') }}"
                                            wire:model="filters.tags"
                                        />
                                    </div>

                                    <!-- Author -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('filters.author') }}</label>
                                        <input
                                            type="text"
                                            name="author"
                                            class="form-control"
                                            placeholder="{{ __('filters.author_placeholder') }}"
                                            wire:model="filters.author"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary">{{ __('filters.reset_filters') }}</button>
                                    <button type="submit" class="btn btn-dark">{{ __('pages.reports.apply') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Search Results Overlay (absolute, does not push layout) -->
                @if($results)
                    <div class="position-absolute top-100 start-0 search-results-overlay mt-2">
                        <div class="card shadow border-0 rounded-3">
                            <ul class="list-group list-group-flush">
                                @forelse($results as $document)
                                    <li class="list-group-item">
                                        <a href="{{ route('document-versions.preview',['id' => $document->latestVersion?->id]) }}" class="text-decoration-none d-block">
                                            <div class="fw-semibold">{!! $this->highlightedTitle($document->title) !!}</div>
                                            @if(!empty($document->latestVersion?->ocr_text))
                                                <small class="text-muted d-block mt-1">{!! $this->highlightedSnippet($document->latestVersion->ocr_text) !!}</small>
                                            @endif
                                        </a>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">{{ __('pages.messages.no_results') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        /* Ensure filter/results dropdowns render above dashboard cards */
        .header,
        .header-left,
        .header .header-left,
        .header .search-box,
        .header .search-box .header-search-wrap {
            position: relative;
            overflow: visible !important;
        }
        .header {
            z-index: 1205;
        }
        .header .search-box {
            z-index: 1210;
        }

        /* Scoped styles for the search/filter header */
        .header-search-wrap {
            width: min(40rem, calc(100vw - 3.5rem));
        }
        .header-search-input {
            min-height: 42px;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            color: #111827 !important;
            border-radius: 10px !important;
            padding-left: 2.5rem !important;
            padding-right: 3.2rem !important;
            box-shadow: none;
        }
        .header-search-input::placeholder {
            color: #6b7280;
            opacity: 1;
        }
        .header-search-input:focus {
            border-color: #cc2929 !important;
            box-shadow: 0 0 0 4px rgba(204, 41, 41, 0.18) !important;
            background: #ffffff !important;
        }
        .header-search-filter-btn {
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #f9fafb;
            border-radius: 8px;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            pointer-events: none;
            color: #6b7280 !important;
        }
        /* Prevent global icon rule from affecting filter icon */
        .search-box .filter-icon {
            position: static !important;
            left: auto !important;
            top: auto !important;
            transform: none !important;
        }
        
        .search-filter-overlay .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .search-filter-overlay {
            z-index: 1300;
            min-width: 100%;
            max-width: 40rem;
        }
        .search-filter-overlay .form-label {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        .search-filter-overlay .form-control,
        .search-filter-overlay .form-select {
            width: 100%;
            min-height: 40px;
            font-size: 14px;
            padding-top: 10px;
            padding-bottom: 10px;
            padding-right: 12px;
            padding-left: 15px; /* override .search-box input { padding-left: 45px } */
        }
        .search-filter-overlay .btn {
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            /* Ensure overlay fits on small screens */
            .search-filter-overlay {
                max-width: calc(100vw - 2rem) !important;
                right: 0;
                left: auto;
            }
        }
        
        /* Results overlay styling */
        .search-results-overlay .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .search-results-overlay {
            z-index: 1290;
            min-width: 100%;
            max-width: 40rem;
        }
        @media (max-width: 768px) {
            .header-search-wrap,
            .search-filter-overlay,
            .search-results-overlay {
                max-width: calc(100vw - 2rem) !important;
            }
        }
        .search-results-overlay .list-group-item {
            padding: 0.75rem 1rem;
        }
        .search-results-overlay a:hover {
            text-decoration: underline;
        }
    </style>
</div>
