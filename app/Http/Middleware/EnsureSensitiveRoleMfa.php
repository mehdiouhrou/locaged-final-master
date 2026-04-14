<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSensitiveRoleMfa
{
    public function handle(Request $request, Closure $next)
    {
        if (! (bool) config('auth.enforce_sensitive_roles_mfa', false)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $isMaster = $user->can('view any role');
        $isItAdmin = $user->roles->contains(fn ($role) => $role->name === 'IT Admin');

        if (($isMaster || $isItAdmin) && ! $user->two_factor_confirmed_at) {
            if (! $request->routeIs('profile.show') && ! $request->routeIs('profile.update')) {
                return redirect()
                    ->route('profile.show')
                    ->with('error', 'MFA is required for your account before accessing this section.');
            }
        }

        return $next($request);
    }
}
