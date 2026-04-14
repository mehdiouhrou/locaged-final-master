<header class="header d-md-flex justify-content-between align-items-center">
    <div class="header-left">
        <livewire:document-elastic-search />
    </div>

    <div class="header-right mt-lg-0 mt-5 position-relative">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('documents.all', ['favoritesOnly' => true]) }}" class="me-3 align-middle header-favorites-icon" title="{{ ui_t('header.favorites') }}" aria-label="{{ ui_t('header.favorites') }}">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
            </a>
            {{-- Help button removed as requested --}}
            @livewire('notification-dropdown')
            <img src="{{ \App\Support\Branding::headerLogoUrl() }}" alt="Logo client" width="120" class="ms-2" />
        </div>
    </div>
</header>
