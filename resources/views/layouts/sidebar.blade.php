@php
    $u = auth()->user();
    $canViewAnyDocument = $u && (
        $u->can('view any document')
        || $u->can('view department document')
        || $u->can('view service document')
        || $u->can('view own document')
    );
    /** Sous-menu « Documents » : uniquement si au moins un lien (ex. tous les documents) est affichable */
    $showDocumentsNavGroup = $canViewAnyDocument;
    $showGestionSection = $u && (
        $u->can('create category')
        || $u->can('update category')
        || $u->can('manage structures')
        || $u->can('view any department')
        || $u->can('view any profile')
        || $u->can('view any tag')
        || $u->can('view any physical location')
    );
    $showAdminSection = $u && (
        $u->hasRole('master')
        || $u->can('view any user')
        || $u->can('view audit log')
        || $u->can('view system activity log')
        || $u->can('view organization wide reports')
        || $u->can('view any role')
        || $u->can('viewHorizon')
    );
@endphp
<nav id="sidebar" class="sidebar">
    <div class="sidebar-brand text-center px-2 mb-1">
        <div class="d-flex justify-content-center">
            @php
                $sidebarLogoUrl = \App\Support\Branding::sidebarLogoUrl();
            @endphp
            <a href="{{ route('home') }}" class="sidebar-brand-logo-link text-decoration-none d-inline-flex flex-column align-items-center mt-3 mb-1">
                @if($sidebarLogoUrl)
                    <img src="{{ $sidebarLogoUrl }}" alt="LocaGed" class="sidebar-brand-logo-img" />
                @else
                    <div class="logo-name">Loca<span>Ged</span></div>
                @endif
            </a>
        </div>
    </div>

    <ul class="sidebar-menu mt-1 flex-grow-1">
        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Principal') }}</span>
        </li>

        <li class="mt-1">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/dashboard-square-01.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.dashboard') }}</span>
            </a>
        </li>

        @role('master')
        <li class="mt-1">
            <a href="{{ route('master.console') }}" class="{{ request()->routeIs('master.console') || request()->routeIs('ocr-jobs.*') || request()->routeIs('ui-translations.*') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-3" alt="" />
                <span class="sidebar-text">{{ __('pages.master_console.nav_label') }}</span>
            </a>
        </li>
        @endrole

        @can('create', \App\Models\Document::class)
        <li class="mt-1">
            <a href="{{ route('documents.create', ['mode' => 'archive']) }}" class="{{ request()->routeIs('documents.create') && request('mode') === 'archive' ? 'active' : '' }}">
                <img src="{{ asset('assets/template/icons8_upload-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.upload') }}</span>
            </a>
        </li>
        @if(\App\Support\Branding::isCollaborativeModuleEnabled())
        <li class="mt-1">
            <a href="{{ route('documents.create', ['mode' => 'collaboratif']) }}" class="{{ request()->routeIs('documents.create') && request('mode') === 'collaboratif' ? 'active' : '' }}">
                <img src="{{ asset('assets/template/message-edit.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Document actif (workflow)') }}</span>
            </a>
        </li>
        @endif
        @endcan

        @if($showDocumentsNavGroup)
        @can('viewAny', \App\Models\Document::class)
        <li>
            <a href="{{ route('documents.all') }}" class="{{ (request()->routeIs('documents.*') && !request()->routeIs('documents.active') && !request()->routeIs('documents.status')) || request()->routeIs('document-versions.*') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/document-text.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.documents') }}</span>
            </a>
        </li>
        @endcan
        @endif

        @if(\App\Support\Branding::isCollaborativeModuleEnabled())
        <li class="{{ request()->routeIs('documents.active') ? 'active' : '' }}">
            <a href="{{ route('documents.active') }}">
                <img src="{{ asset('assets/template/verify.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Tâches') }}</span>
                @if(($sidebarMyTasksCount ?? 0) > 0)
                    <span class="badge rounded-pill lgv2-sidebar-pill ms-auto">{{ $sidebarMyTasksCount }}</span>
                @endif
            </a>
        </li>
        @endif

        @canany(['approve','decline'], \App\Models\Document::class)
        <li class="{{ request()->routeIs('documents.status') || request()->routeIs('documents.all') && request('page_title') === 'pending_documents' ? 'active' : '' }}">
            @if(auth()->user() && auth()->user()->can('view service document') && ! auth()->user()->can('access management sidebar'))
                <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Pending->value, 'page_title' => 'pending_documents', 'show_expired' => 1, 'lock_status' => 1]) }}">
            @else
                <a href="{{ route('documents.status') }}">
            @endif
                <img src="{{ asset('assets/template/folder-open.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('À archiver') }}</span>
            </a>
        </li>
        @endcanany

        @php
            $myCategories = $sidebarMyCategories['items'] ?? [];
            $myCategoriesTotal = (int) ($sidebarMyCategories['total'] ?? 0);
            $myFavoriteCategories = $sidebarMyCategories['favorites'] ?? [];
            $hasMyCategories = !empty($myCategories) || !empty($myFavoriteCategories);
        @endphp
        {{-- Consultation : dossiers du profil (distinct du lien « Dossiers » sous Gestion) --}}
        <li class="has-submenu {{ request()->routeIs('documents.by-category') || request()->routeIs('my-categories.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle" title="{{ __('Dossiers accessibles selon votre profil (consultation)') }}">
                <img src="{{ asset('assets/template/category.svg') }}" class="me-3" alt="" />
                <span class="sidebar-text">{{ __('Mes dossiers') }}</span>
                @if($myCategoriesTotal > 0)
                    <span class="badge rounded-pill lgv2-sidebar-pill ms-auto me-2">{{ $myCategoriesTotal }}</span>
                @endif
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @if(!empty($myCategories))
                    @foreach($myCategories as $myCategory)
                        <li class="mt-1">
                            <a href="{{ route('documents.by-category', ['categoryId' => $myCategory['id']]) }}">
                                <i class="fa-regular fa-folder me-2"></i>
                                <span class="sidebar-text">{{ \Illuminate\Support\Str::limit($myCategory['name'], 28) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                @if(!empty($myFavoriteCategories))
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Mes dossiers favorites') }}</span>
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
                        <a href="{{ route('my-categories.index') }}">
                            <i class="fa-solid fa-list-ul me-2"></i>
                            <span class="sidebar-text">{{ __('pages.my_categories.view_all') }}</span>
                        </a>
                    </li>
                @endif

                @if(!$hasMyCategories)
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted px-3">{{ __('Aucune dossier accessible') }}</span>
                    </li>
                @endif
            </ul>
        </li>

        @if(false)
        @php
            $favoriteCategories = $sidebarFavorites['categories'] ?? [];
            $favoriteDocuments = $sidebarFavorites['documents'] ?? [];
            $recentActivity = $sidebarFavorites['recent'] ?? [];
            $hasFavoritesContent = !empty($favoriteCategories) || !empty($favoriteDocuments) || !empty($recentActivity);
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
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Dossiers favorites') }}</span>
                    </li>
                    @foreach($favoriteCategories as $favoriteCategory)
                        <li class="mt-1">
                            <a href="{{ route('documents.by-category', ['categoryId' => $favoriteCategory['id']]) }}">
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

                @if(!empty($recentActivity))
                    <li class="mt-2">
                        <span class="sidebar-text small text-muted fw-semibold px-3">{{ __('Activité récente') }}</span>
                    </li>
                    @foreach($recentActivity as $row)
                        @php
                            $actIcon = match ($row['kind'] ?? '') {
                                'upload' => 'fa-cloud-arrow-up',
                                'pending' => 'fa-hourglass-half',
                                'approved' => 'fa-circle-check',
                                'declined' => 'fa-circle-xmark',
                                default => 'fa-file-lines',
                            };
                        @endphp
                        <li class="mt-1">
                            <a href="{{ !empty($row['url']) ? $row['url'] : route('documents.all') }}" class="d-flex align-items-start gap-2">
                                <i class="fa-solid {{ $actIcon }} me-1 mt-1 opacity-75 flex-shrink-0"></i>
                                <span class="sidebar-text">
                                    <span class="d-block">{{ \Illuminate\Support\Str::limit($row['title'] ?? '', 28) }}</span>
                                    <span class="small text-muted">{{ $row['status_label'] ?? '' }} · {{ isset($row['at']) ? $row['at']->diffForHumans() : '' }}</span>
                                </span>
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
        @endif

        @if($showGestionSection)
        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Gestion') }}</span>
        </li>

        <li class="has-submenu {{ request()->routeIs('categories.*') || request()->routeIs('tags.*') || request()->routeIs('physical-locations.*') || request()->routeIs('reports.*') || request()->routeIs('users.logs') || request()->routeIs('documents.destructions') || request()->routeIs('destruction-certificates.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/setting-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Gestion') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @if(auth()->user()->can('create category') || auth()->user()->can('update category'))
                <li class="mt-2">
                    <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') ? 'active' : '' }}" title="{{ __('Gestion des dossiers (création, modification, suppression)') }}">
                        <img src="{{ asset('assets/template/elements.svg') }}" class="me-2" alt="" />
                        <span class="sidebar-text">{{ ui_t('nav.categories') }}</span>
                    </a>
                </li>
                @endif
{{-- tags masques --}}
                @can('view any physical location')
                <li class="mt-2">
                    <a href="{{ route('physical-locations.index') }}" class="{{ request()->routeIs('physical-locations.index') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/Flags.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.physical_location') }}</span>
                    </a>
                </li>
                @endcan
                @if(auth()->user()->can('view audit log') || auth()->user()->can('view system activity log'))
                <li class="mt-2">
                    <a href="{{ route('users.logs') }}" class="{{ request()->routeIs('users.logs') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/rotate-left.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('pages.activity_log.activity_log') ?? 'User Logs' }}</span>
                    </a>
                </li>
                @endif
                @can('access document expiration management')
                <li class="mt-2">
                    <a href="{{ route('documents.destructions') }}" class="{{ request()->routeIs('documents.destructions') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/trash.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.destruction') }}</span>
                    </a>
                </li>
                @endcan
                @can('view destruction certificates')
                <li class="mt-2">
                    <a href="{{ route('destruction-certificates.index') }}" class="{{ request()->routeIs('destruction-certificates.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/document-text2.svg') }}" class="me-2" style="width: 1.1rem; height: 1.1rem; opacity: .85;" alt="" />
                        <span class="sidebar-text">{{ __('pages.destruction_certificates.registry_link') }}</span>
                    </a>
                </li>
                @endcan
                @can('viewAny', \App\Models\LoanRequest::class)
                <li class="mt-2">
                    <a href="{{ route('loan-requests.index') }}" class="{{ request()->routeIs('loan-requests.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/rotate-left.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ __('Gestion physique des documents') }}</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endif

        @if($showAdminSection)
        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Administration') }}</span>
        </li>

        <li class="has-submenu {{ request()->routeIs('users.*') || request()->routeIs('departments.*') || request()->routeIs('access-profiles.*') || request()->routeIs('storage.overview') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/edit-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Administration') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                @canany(['manage structures', 'view any department'])
                <li class="mt-2">
                    <a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.index') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/brifecase-tick.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.structures') }}</span>
                    </a>
                </li>
                @endcanany
                @can('view any user')
                <li class="mt-2">
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') || request()->routeIs('users.create') || request()->routeIs('users.edit') || request()->routeIs('users.show') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/user.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.users') }}</span>
                    </a>
                </li>
                @endcan
                @canany(['view organization wide reports', 'view any role'])
                <li class="mt-2">
                    <a href="{{ route('storage.overview') }}" class="{{ request()->routeIs('storage.overview') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/Frame.svg') }}" class="me-2" />
                        <span class="sidebar-text">Storage &amp; Server Space</span>
                    </a>
                </li>
                @endcanany
                @can('view any role')
                <li class="mt-2">
                    <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/lock2.svg') }}" class="me-2" alt="" />
                        <span class="sidebar-text">{{ __('Rôles') }}</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endif

        @role('master')
        <li class="sidebar-section-label" aria-hidden="true">
            <span class="sidebar-section-label-text">{{ __('Outils LocaGed') }}</span>
        </li>
        <li class="has-submenu {{ request()->routeIs('master.console') || request()->routeIs('ocr-jobs.*') || request()->routeIs('ui-translations.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ __('Outils LocaGed') }}</span>
                <i class="fa-solid fa-chevron-down submenu-chevron ms-auto small opacity-50" aria-hidden="true"></i>
            </a>
            <ul class="submenu list-unstyled">
                <li class="mt-2">
                    <a href="{{ route('master.console') }}" class="{{ request()->routeIs('master.console') || request()->routeIs('ocr-jobs.*') || request()->routeIs('ui-translations.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-2" alt="" />
                        <span class="sidebar-text">{{ __('pages.master_console.nav_label') }}</span>
                    </a>
                </li>

                @can('viewHorizon')
                <li class="mt-2">
                    <a href="{{ url('/horizon') }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ asset('assets/template/clock.svg') }}" class="me-2" />
                        <span class="sidebar-text">Horizon</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endrole
    </ul>

</nav>
