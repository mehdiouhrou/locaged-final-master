<nav id="sidebar" class="sidebar">
    <div class="sidebar-brand text-center px-2 mb-1">
        <div class="d-flex justify-content-center">
            <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex flex-column align-items-center">
                <img src="{{ asset('assets/template/Logo 1.svg') }}" alt="LocaGed" class="sidebar-logo mb-1 mt-3 expanded-only pointer" />
                <img src="{{ asset('assets/template/Frame 2078547825 1.svg') }}" alt="" class="collapsed-only mb-1 mt-3 pointer" />
            </a>
        </div>
        <div class="sidebar-brand-sub">{{ __('Par Locarchives Group') }}</div>
    </div>

    <ul class="sidebar-menu mt-1 flex-grow-1">
        @php
            $u = auth()->user();
        @endphp

        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Principal') }}</span>
        </li>

        <li class="mt-1">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/dashboard-square-01.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.dashboard') }}</span>
            </a>
        </li>

        @can('create', \App\Models\Document::class)
        <li class="mt-1">
            <a href="{{ route('documents.create') }}" class="{{ request()->routeIs('documents.create') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/icons8_upload-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.upload') }}</span>
            </a>
        </li>
        @endcan

        @php
            $u = auth()->user();
            $canDocumentsMenu = $u && (
                $u->can('viewAny', \App\Models\Document::class)
                || $u->can('viewAny', \App\Models\Category::class)
                || $u->can('viewAny', \App\Models\DocumentVersion::class)
                || $u->can('viewAny', \App\Models\DocumentDestructionRequest::class)
            );
        @endphp
        @if($canDocumentsMenu)
        <li class="has-submenu {{ request()->routeIs('documents.*') || request()->routeIs('document-versions.*') || request()->routeIs('categories.*') || request()->routeIs('subcategories.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/document-text.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.documents') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @can('viewAny', \App\Models\Document::class)
                <li class="mt-2">
                    <a href="{{ route('documents.all') }}" class="{{ request()->routeIs('documents.all') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/document-favorite.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.all_documents') }}</span>
                    </a>
                </li>
                @endcan
                {{-- Optional: versions page kept hidden for now
                @can('viewAny', \App\Models\DocumentVersion::class)
                <li class="mt-2">
                    <a href="{{ route('document-versions.index') }}" class="{{ request()->routeIs('document-versions.index') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/document-favorite.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.versions') }}</span>
                    </a>
                </li>
                @endcan
                --}}
            </ul>
        </li>
        @endif

        @canany(['approve','decline'], \App\Models\Document::class)
        <li class="{{ request()->routeIs('documents.status') ? 'active' : '' }}">
            <a href="{{ route('documents.status') }}">
                <img src="{{ asset('assets/template/verify.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.approvals') }}</span>
            </a>
        </li>
        @endcanany
        
        <li class="{{ request()->routeIs('notifications') || request()->routeIs('activity.feed') ? 'active' : '' }}">
            <a href="{{ route('notifications') }}">
                <img src="{{ asset('assets/template/notification.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Fil d’événements') }}</span>
            </a>
        </li>

        @can('viewAny', \App\Models\PhysicalLocation::class)
            @if(! $u->can('access management sidebar'))
                <li class="mt-1">
                    <a href="{{ route('physical-locations.index') }}" class="{{ request()->routeIs('physical-locations.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/Flags.svg') }}" class="me-3" />
                        <span class="sidebar-text">{{ ui_t('nav.physical_location') }}</span>
                    </a>
                </li>
            @endif
        @endcan

        @php
            $myCategories = $sidebarMyCategories['items'] ?? [];
            $myCategoriesTotal = (int) ($sidebarMyCategories['total'] ?? 0);
            $myFavoriteCategories = $sidebarMyCategories['favorites'] ?? [];
            $hasMyCategories = !empty($myCategories) || !empty($myFavoriteCategories);
        @endphp
        <li class="has-submenu {{ request()->has('category') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/category.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Mes catégories') }}</span>
                @if($myCategoriesTotal > 0)
                    <span class="badge rounded-pill lgv2-sidebar-pill ms-auto me-2">{{ $myCategoriesTotal }}</span>
                @endif
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @if(!empty($myCategories))
                    @foreach($myCategories as $myCategory)
                        <li class="mt-1">
                            <a href="{{ route('documents.all', ['category' => $myCategory['id']]) }}">
                                <i class="fa-regular fa-folder me-2"></i>
                                <span class="sidebar-text">{{ \Illuminate\Support\Str::limit($myCategory['name'], 28) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                @if(!empty($myFavoriteCategories))
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Mes catégories favorites') }}</span>
                    </li>
                    @foreach($myFavoriteCategories as $favCategory)
                        <li class="mt-1">
                            <a href="{{ route('documents.all', ['category' => $favCategory['id'], 'favoritesOnly' => true]) }}">
                                <i class="fa-solid fa-star me-2 text-warning"></i>
                                <span class="sidebar-text">{{ \Illuminate\Support\Str::limit($favCategory['name'], 26) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                @if($myCategoriesTotal > 5)
                    <li class="mt-2">
                        <a href="{{ route('documents.all') }}">
                            <i class="fa-solid fa-ellipsis me-2"></i>
                            <span class="sidebar-text">{{ __('Voir plus') }}</span>
                        </a>
                    </li>
                @endif

                @if(!$hasMyCategories)
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted px-3">{{ __('Aucune catégorie accessible') }}</span>
                    </li>
                @endif
            </ul>
        </li>

        @php
            $favoriteCategories = $sidebarFavorites['categories'] ?? [];
            $favoriteDocuments = $sidebarFavorites['documents'] ?? [];
            $recentDocuments = $sidebarFavorites['recent'] ?? [];
            $hasFavoritesContent = !empty($favoriteCategories) || !empty($favoriteDocuments) || !empty($recentDocuments);
        @endphp
        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Favoris') }}</span>
        </li>
        <li class="has-submenu {{ request()->boolean('favoritesOnly') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/star.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Favoris') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                <li class="mt-2">
                    <a href="{{ route('documents.all', ['favoritesOnly' => true]) }}" class="{{ request()->boolean('favoritesOnly') ? 'active' : '' }}">
                        <i class="fa-regular fa-star me-2"></i>
                        <span class="sidebar-text">{{ __('Documents favoris') }}</span>
                    </a>
                </li>

                @if(!empty($favoriteCategories))
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Catégories favorites') }}</span>
                    </li>
                    @foreach($favoriteCategories as $favoriteCategory)
                        <li class="mt-1">
                            <a href="{{ route('documents.all', ['category' => $favoriteCategory['id']]) }}">
                                <i class="fa-regular fa-folder me-2"></i>
                                <span class="sidebar-text">{{ $favoriteCategory['name'] }}</span>
                                <span class="sidebar-text ms-auto text-muted small">{{ $favoriteCategory['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                @if(!empty($favoriteDocuments))
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Top documents favoris') }}</span>
                    </li>
                    @foreach($favoriteDocuments as $favoriteDocument)
                        <li class="mt-1">
                            <a href="{{ $favoriteDocument->latestVersion ? route('document-versions.preview', ['id' => $favoriteDocument->latestVersion->id]) : route('documents.all') }}">
                                <i class="fa-regular fa-file-lines me-2"></i>
                                <span class="sidebar-text">{{ \Illuminate\Support\Str::limit($favoriteDocument->title, 26) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                @if(!empty($recentDocuments))
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Derniers consultés') }}</span>
                    </li>
                    @foreach($recentDocuments as $recentDocument)
                        <li class="mt-1">
                            <a href="{{ $recentDocument->latestVersion ? route('document-versions.preview', ['id' => $recentDocument->latestVersion->id]) : route('documents.all') }}">
                                <i class="fa-solid fa-clock-rotate-left me-2"></i>
                                <span class="sidebar-text">{{ \Illuminate\Support\Str::limit($recentDocument->title, 26) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                @if(!$hasFavoritesContent)
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted px-3">{{ __('Aucun favori pour le moment') }}</span>
                    </li>
                @endif
            </ul>
        </li>

        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Gestion') }}</span>
        </li>

        @php
            $canManageCategories = $u && $u->can('viewAny', \App\Models\Category::class);
            $canManageStructures = $u && ($u->can('manage structures') || $u->can('view any department') || $u->can('view any role') || $u->can('view organization wide reports'));
            $canManageProfiles = $u && $u->can('viewAny', \App\Models\Profile::class);
            $canManageTags = $u && $u->can('viewAny', \App\Models\Tag::class);
            $canManageLocations = $u && $u->can('access management sidebar');
            $canManageReports = $u && $u->can('access management sidebar') && ! $u->can('filter audit logs by assigned services') && ! $u->can('view subdepartment scoped documents');

            $canGestionAccordion = $canManageCategories || $canManageStructures || $canManageProfiles || $canManageTags || $canManageLocations || $canManageReports;
        @endphp
        @if($canGestionAccordion)
        <li class="has-submenu {{ request()->routeIs('categories.*') || request()->routeIs('departments.*') || request()->routeIs('tags.*') || request()->routeIs('physical-locations.*') || request()->routeIs('reports.*') || request()->routeIs('access-profiles.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/setting-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Gestion') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @if($canManageCategories)
                    <li class="mt-2">
                        <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/category.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.categories') }}</span>
                        </a>
                    </li>
                @endif
                @if($canManageStructures)
                    <li class="mt-2">
                        <a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.index') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/brifecase-tick.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.structures') }}</span>
                        </a>
                    </li>
                @endif
                @if($canManageProfiles)
                    <li class="mt-2">
                        <a href="{{ route('access-profiles.index') }}" class="{{ request()->routeIs('access-profiles.*') ? 'active' : '' }} d-flex align-items-center flex-wrap gap-1">
                            <img src="{{ asset('assets/template/brifecase-tick.svg') }}" class="me-2" alt="" />
                            <span class="sidebar-text">{{ __('Profils d’accès') }}</span>
                            <span class="badge rounded-pill lgv2-sidebar-pill ms-auto">V2</span>
                        </a>
                    </li>
                @endif

                @if($canManageTags)
                    <li class="mt-2">
                        <a href="{{ route('tags.index') }}" class="{{ request()->routeIs('tags.index') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/star.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.tags') }}</span>
                        </a>
                    </li>
                @endif
                @if($canManageLocations)
                    <li class="mt-2">
                        <a href="{{ route('physical-locations.index') }}" class="{{ request()->routeIs('physical-locations.index') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/Flags.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.physical_location') }}</span>
                        </a>
                    </li>
                @endif
                @if($canManageReports)
                    <li class="mt-2">
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/document-favorite.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.reports') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
        @endif

        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Administration') }}</span>
        </li>

        @php
            $canViewUsers = $u && $u->can('access management sidebar');
            $canViewRoles = $u && $u->can('view any role');
            $canViewAuditLogs = $u && $u->can('view system activity log');
            $canViewDestruction = $u && $u->can('access document expiration management');
            $canViewOcr = $u && $u->can('viewAny', \App\Models\OcrJob::class);
            $canViewLocalization = $u && $u->can('viewAny', \App\Models\UiTranslation::class);
            $canViewStorage = $u && ($u->can('view any role') || $u->can('view organization wide reports'));
            $canViewHorizon = $u && $u->can('viewHorizon');

            $canAdministrationAccordion = $canViewUsers || $canViewRoles || $canViewAuditLogs || $canViewDestruction || $canViewOcr || $canViewLocalization || $canViewStorage || $canViewHorizon;
        @endphp
        @if($canAdministrationAccordion)
        <li class="has-submenu {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('users.logs') || request()->routeIs('documents.destructions') || request()->routeIs('ocr-jobs.*') || request()->routeIs('ui-translations.*') || request()->routeIs('storage.overview') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Administration') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @if($canViewUsers)
                    <li class="mt-2">
                        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/user.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.users') }}</span>
                        </a>
                    </li>
                @endif
                @if($canViewRoles)
                    <li class="mt-2">
                        <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/brifecase-tick.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.roles') }}</span>
                        </a>
                    </li>
                @endif
                @if($canViewAuditLogs)
                    <li class="mt-2">
                        <a href="{{ route('users.logs') }}" class="{{ request()->routeIs('users.logs') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/rotate-left.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('pages.activity_log.activity_log') ?? 'User Logs' }}</span>
                        </a>
                    </li>
                @endif
                @if($canViewDestruction)
                    <li class="mt-2">
                        <a href="{{ route('documents.destructions') }}" class="{{ request()->routeIs('documents.destructions') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/rotate-left.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.destruction') }}</span>
                        </a>
                    </li>
                @endif
                @if($canViewOcr)
                    <li class="mt-2">
                        <a href="{{ route('ocr-jobs.index') }}" class="{{ request()->routeIs('ocr-jobs.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/eye.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.ocr') }}</span>
                        </a>
                    </li>
                @endif
                @if($canViewLocalization)
                    <li class="mt-2">
                        <a href="{{ route('ui-translations.index') }}" class="{{ request()->routeIs('ui-translations.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.localization') }}</span>
                        </a>
                    </li>
                @endif
                @if($canViewStorage)
                    <li class="mt-2">
                        <a href="{{ route('storage.overview') }}" class="{{ request()->routeIs('storage.overview') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-2" />
                            <span class="sidebar-text">Storage &amp; Server Space</span>
                        </a>
                    </li>
                @endif
                @if($canViewHorizon)
                    <li class="mt-2">
                        <a href="{{ url('/horizon') }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-2" />
                            <span class="sidebar-text">Horizon</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
        @endif
    </ul>

    @php
        $authUserImageUrl = auth()->user()?->avatar_url ?? asset('assets/user.png');
    @endphp
    <div class="bottom-icons mt-auto">
        <a href="{{ route('profile.show') }}" class="sidebar-profile-card text-decoration-none">
            <img
                src="{{ $authUserImageUrl }}"
                class="sidebar-profile-avatar"
                alt="{{ ui_t('actions.user') }}"
                onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
            />
            <div class="sidebar-profile-meta">
                <div class="sidebar-profile-name">{{ auth()->user()->full_name }}</div>
                <div class="sidebar-profile-role">{{ auth()->user()->role }}</div>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form px-2 pb-2 mb-0">
            @csrf
            <button type="submit" class="btn btn-sm sidebar-logout-btn w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>{{ __('header.logout') }}</span>
            </button>
        </form>
    </div>
</nav>
