<?php
namespace App\Policies;
use App\Models\User;
use App\Support\Branding;
use App\Support\RoleHierarchy;
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        // Seule vérification : la permission Spatie
        return $user->can('view any user')
            || $user->can('view department user')
            || $user->can('view service user');
    }

    public function view(User $user, User $model): bool
    {
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }
        if ($user->can('view any user')) {
            return true;
        }
        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view own user') && $user->id === $model->id) {
            return true;
        }
        return false;
    }

    public function create(User $user): bool
    {
        // CORRIGÉ : uniquement la permission Spatie — plus de fallback par rôle
        // Seul IT Admin (et master) ont 'create user' dans le seeder
        if (! $user->can('create user')) {
            return false;
        }
        // Vérifier la limite d'utilisateurs
        if (Branding::isUserLimitReached()) {
            return false;
        }
        return true;
    }

    public function update(User $user, User $model): bool
    {
        // CORRIGÉ : uniquement la permission Spatie
        if ($user->cannot('update user')) {
            return false;
        }
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }
        if ($user->can('view any user')) {
            return true;
        }
        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view own user') && $user->id === $model->id) {
            return true;
        }
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        // CORRIGÉ : uniquement la permission Spatie — plus de vérification par rôle en dur
        // Seul IT Admin (et master) ont 'delete user' dans le seeder
        if ($user->cannot('delete user')) {
            return false;
        }
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }
        if ($user->can('view any user')) {
            return true;
        }
        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        if ($user->cannot('restore user')) {
            return false;
        }
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }
        if ($user->can('view any user')) {
            return true;
        }
        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($user->cannot('forceDelete user')) {
            return false;
        }
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }
        if ($user->can('view any user')) {
            return true;
        }
        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }
        return false;
    }

    public function updatePassword(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }
        return $user->can('update user');
    }
}
