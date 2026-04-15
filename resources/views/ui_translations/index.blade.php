@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-md-4 mt-4 mb-5">
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="fa-solid fa-shield-halved mt-1" aria-hidden="true"></i>
            <div>
                <strong>{{ __('pages.translations.admin_master_only_title') }}</strong>
                <p class="mb-0 small">{{ __('pages.translations.admin_master_only_body') }}</p>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold">{{ __('pages.translations.admin_page_title') }}</h1>
                <p class="text-muted small mb-0">{{ __('pages.translations.admin_page_subtitle') }}</p>
            </div>
            @can('create', \App\Models\UiTranslation::class)
                <a class="btn btn-dark" href="{{ route('ui-translations.create') }}">
                    <i class="fa-solid fa-language me-1"></i>{{ ui_t('pages.translations.add_button') }}
                </a>
            @endcan
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3 fw-semibold">
                        {{ ui_t('pages.translations.timezone') }}
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ui-translations.branding') }}" id="timezone-form">
                            @csrf
                            <select class="form-select" name="timezone" id="timezone" onchange="this.form.submit()">
                                <option value="">{{ ui_t('pages.translations.select_timezone') }}</option>
                                <optgroup label="Europe">
                                    <option value="Europe/Paris" {{ \App\Support\Branding::getTimezone() === 'Europe/Paris' ? 'selected' : '' }}>CET (Europe/Paris)</option>
                                    <option value="Europe/London" {{ \App\Support\Branding::getTimezone() === 'Europe/London' ? 'selected' : '' }}>WET (Europe/London)</option>
                                    <option value="Europe/Athens" {{ \App\Support\Branding::getTimezone() === 'Europe/Athens' ? 'selected' : '' }}>EET (Europe/Athens)</option>
                                    <option value="Europe/Berlin" {{ \App\Support\Branding::getTimezone() === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin</option>
                                </optgroup>
                                <optgroup label="Middle East">
                                    <option value="Asia/Riyadh" {{ \App\Support\Branding::getTimezone() === 'Asia/Riyadh' ? 'selected' : '' }}>Arabia (Asia/Riyadh)</option>
                                    <option value="Asia/Dubai" {{ \App\Support\Branding::getTimezone() === 'Asia/Dubai' ? 'selected' : '' }}>Gulf (Asia/Dubai)</option>
                                    <option value="Asia/Baghdad" {{ \App\Support\Branding::getTimezone() === 'Asia/Baghdad' ? 'selected' : '' }}>Arabia (Asia/Baghdad)</option>
                                    <option value="Asia/Kuwait" {{ \App\Support\Branding::getTimezone() === 'Asia/Kuwait' ? 'selected' : '' }}>Arabia (Asia/Kuwait)</option>
                                </optgroup>
                                <optgroup label="Americas">
                                    <option value="America/New_York" {{ \App\Support\Branding::getTimezone() === 'America/New_York' ? 'selected' : '' }}>EST (America/New_York)</option>
                                    <option value="America/Chicago" {{ \App\Support\Branding::getTimezone() === 'America/Chicago' ? 'selected' : '' }}>CST (America/Chicago)</option>
                                    <option value="America/Denver" {{ \App\Support\Branding::getTimezone() === 'America/Denver' ? 'selected' : '' }}>MST (America/Denver)</option>
                                    <option value="America/Los_Angeles" {{ \App\Support\Branding::getTimezone() === 'America/Los_Angeles' ? 'selected' : '' }}>PST (America/Los_Angeles)</option>
                                </optgroup>
                                <optgroup label="Asia Pacific">
                                    <option value="Asia/Tokyo" {{ \App\Support\Branding::getTimezone() === 'Asia/Tokyo' ? 'selected' : '' }}>JST (Asia/Tokyo)</option>
                                    <option value="Asia/Singapore" {{ \App\Support\Branding::getTimezone() === 'Asia/Singapore' ? 'selected' : '' }}>SGT (Asia/Singapore)</option>
                                    <option value="Asia/Hong_Kong" {{ \App\Support\Branding::getTimezone() === 'Asia/Hong_Kong' ? 'selected' : '' }}>HKT (Asia/Hong_Kong)</option>
                                </optgroup>
                                <optgroup label="Africa">
                                    <option value="Africa/Casablanca" {{ \App\Support\Branding::getTimezone() === 'Africa/Casablanca' ? 'selected' : '' }}>Morocco (Africa/Casablanca – UTC+1)</option>
                                </optgroup>
                                <optgroup label="Other">
                                    <option value="UTC" {{ \App\Support\Branding::getTimezone() === 'UTC' ? 'selected' : '' }}>UTC</option>
                                </optgroup>
                            </select>
                            <small class="text-muted d-block mt-2">{{ ui_t('pages.translations.current') }} {{ \App\Support\Branding::getTimezone() }}</small>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3 fw-semibold">
                        {{ ui_t('pages.translations.introduction') ?? ui_t('pages.translations.title') }}
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">{{ __('pages.translations.admin_strings_hint') }}</p>
                        <a href="{{ route('ui-translations.create') }}" class="btn btn-outline-primary btn-sm">
                            {{ ui_t('pages.translations.add_button') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold">{{ ui_t('pages.translations.branding') }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted small">{{ __('pages.translations.upload_nginx_hint') }}</p>
                <form method="post" action="{{ route('ui-translations.branding') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ ui_t('pages.translations.header_logo') }}</label>
                            <input type="file" name="header_logo" class="form-control" accept="image/*" />
                            <small class="text-muted">{{ ui_t('pages.translations.image_limit_5mb') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ ui_t('pages.translations.login_left_image') }}</label>
                            <input type="file" name="login_left_image" class="form-control" accept="image/*" />
                            <small class="text-muted">{{ ui_t('pages.translations.image_limit_8mb') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ ui_t('pages.translations.max_users') }}</label>
                            <input type="number" name="max_users" class="form-control"
                                   value="{{ \App\Support\Branding::getMaxUsers() }}"
                                   min="0" placeholder="{{ ui_t('pages.translations.zero_unlimited_ph') }}" />
                            <small class="text-muted">{{ ui_t('pages.translations.max_users_help') }}</small>
                            @php
                                $currentUserCount = \App\Models\User::count();
                                $maxUsers = \App\Support\Branding::getMaxUsers();
                            @endphp
                            <div class="mt-2">
                                @if($maxUsers > 0)
                                    <div class="alert alert-sm alert-info mb-0 py-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>{{ ui_t('pages.translations.current') }}</strong> {{ $currentUserCount }} / {{ $maxUsers }} {{ ui_t('pages.users_page.users') }}
                                        @if($currentUserCount >= $maxUsers)
                                            <span class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> {{ ui_t('pages.translations.limit_reached') }}</span>
                                        @else
                                            <span class="text-success ms-2"><i class="fas fa-check-circle"></i> {{ $maxUsers - $currentUserCount }} {{ ui_t('pages.translations.slots_remaining') }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="alert alert-sm alert-success mb-0 py-2">
                                        <i class="fas fa-users me-1"></i>
                                        <strong>{{ ui_t('pages.translations.current') }}</strong> {{ $currentUserCount }} {{ ui_t('pages.users_page.users') }} ({{ ui_t('pages.users_page.unlimited') }})
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if(auth()->user()?->can('view any role'))
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Branche principale de la hiérarchie') }}</label>
                                <input type="text" name="org_root_name" class="form-control"
                                       value="{{ \App\Support\Branding::getOrgRootName() }}"
                                       maxlength="120" placeholder="{{ __('Direction Générale') }}" />
                                <small class="text-muted">{{ __('Libellé affiché en tête de l’organigramme.') }}</small>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-dark px-4">{{ ui_t('actions.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
