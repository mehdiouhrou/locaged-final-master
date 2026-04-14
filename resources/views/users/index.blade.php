@extends('layouts.app')

@section('content')
        @php
            $currentUserCount = \App\Models\User::count();
            $maxUsers = \App\Support\Branding::getMaxUsers();
            $usersHeroSubtitle = number_format($currentUserCount) . ' ' . ui_t('pages.users_page.users');
        @endphp

        <x-page-hero :title="ui_t('pages.users_page.users')" :subtitle="$usersHeroSubtitle">
            <x-slot:actions>
                @can('viewAny', \Spatie\Permission\Models\Role::class)
                    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">{{ ui_t('pages.users_page.roles') }}</a>
                @endcan
                @can('viewAny', \App\Models\User::class)
                    @if(auth()->user()->can('view any role') || auth()->user()->can('view organization wide reports'))
                        <a href="{{ route('users.export') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1" aria-hidden="true"></i>{{ ui_t('pages.users_page.export_users') }}
                        </a>
                    @endif
                @endcan
                @can('create', \App\Models\User::class)
                    @if($maxUsers > 0 && $currentUserCount >= $maxUsers)
                        <button type="button" class="btn btn-sm btn-secondary" disabled title="{{ ui_t('pages.users_page.add_user_limit_reached_title', ['max' => $maxUsers]) }}">
                            <i class="fas fa-ban me-1" aria-hidden="true"></i>{{ ui_t('pages.users_page.add_user_limit_reached_btn') }}
                        </button>
                    @else
                        <a class="btn btn-sm btn-upload" id="nextBtn" href="#" role="button">{{ ui_t('pages.users_page.add_user') }}</a>
                    @endif
                @endcan
            </x-slot:actions>
            @if(auth()->user()->can('view any role') || auth()->user()->can('view organization wide reports'))
                <x-slot:below>
                    @if($maxUsers > 0)
                        <div class="alert alert-info mt-0 mb-0">
                            <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                            <strong>{{ ui_t('pages.users_page.user_limit') }}</strong> {{ $currentUserCount }} / {{ $maxUsers }} {{ ui_t('pages.users_page.users') }}
                            @if($currentUserCount >= $maxUsers)
                                <span class="text-danger ms-2">
                                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ ui_t('pages.users_page.limit_reached') }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-success mt-0 mb-0">
                            <i class="fas fa-users me-2" aria-hidden="true"></i>
                            <strong>{{ ui_t('pages.users_page.total_users') }}</strong> {{ $currentUserCount }} ({{ ui_t('pages.users_page.unlimited') }})
                        </div>
                    @endif
                </x-slot:below>
            @endif
        </x-page-hero>

        <livewire:users-table/>

@endsection
