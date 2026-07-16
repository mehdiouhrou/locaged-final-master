<header class="header d-md-flex justify-content-between align-items-center">
    <div class="header-left">
        <livewire:document-elastic-search />
    </div>

    <div class="header-right mt-lg-0 mt-5 position-relative">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('documents.all', ['favoritesOnly' => true]) }}" class="me-3 align-middle header-favorites-icon" title="{{ ui_t('header.favorites') }}" aria-label="{{ ui_t('header.favorites') }}">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
            </a>
            {{-- Help button removed as requested --}}
            @livewire('notification-dropdown')
            <img src="{{ \App\Support\Branding::headerLogoUrl() }}" alt="Logo client" width="120" class="ms-2" />

            @php
                $authUserImageUrl = auth()->user()?->avatar_url ?? asset('assets/user.png');
            @endphp
            <div class="dropdown ms-2 header-profile-dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="headerProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img
                        src="{{ $authUserImageUrl }}"
                        class="rounded-circle"
                        width="36"
                        height="36"
                        style="object-fit: cover;"
                        alt="{{ ui_t('actions.user') }}"
                        onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                    />
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="headerProfileDropdown">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ auth()->user()->full_name }}</div>
                        <div class="text-muted small">{{ auth()->user()->role }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="fa-solid fa-user me-2" aria-hidden="true"></i>{{ __('Profil') }}
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="px-3 py-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">
                                <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>{{ __('header.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
