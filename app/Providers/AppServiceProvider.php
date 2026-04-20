<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Observers\DocumentObserver;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set application timezone from Branding settings
        $timezone = \App\Support\Branding::getTimezone();
        if ($timezone && $timezone !== 'UTC') {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        // AUDIT FIX #2: Runtime guard - block app if debug is enabled in production
        // This prevents accidental exposure of stack traces and sensitive information
        if ($this->app->environment('production') && config('app.debug') === true) {
            abort(503, 'Application misconfigured: APP_DEBUG must be false in production.');
        }

        // Disabled for HTTP deployment
        // if ($this->app->environment('production')) {
        //     URL::forceScheme('https');
        // }

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(\App\Models\DocumentDestructionRequest::class, \App\Policies\DocumentDestructionRequestPolicy::class);
        Gate::policy(\App\Models\DestructionCertificate::class, \App\Policies\DestructionCertificatePolicy::class);

        Document::observe(DocumentObserver::class);
        Category::observe(CategoryObserver::class);

        View::composer('layouts.sidebar', \App\View\Composers\SidebarComposer::class);

        Gate::before(function (User $user, string $ability) {
            // Ne pas appeler $user->can() ici : cela ré-entrerait dans Gate::before et peut saturer la pile.
            if ($ability === 'view any role') {
                return null;
            }

            $hasMasterBypass = $user->getAllPermissions()->contains(
                fn ($p) => $p->name === 'view any role'
            );

            return $hasMasterBypass ? true : null;
        });

    }
}
