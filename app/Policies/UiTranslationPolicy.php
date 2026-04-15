<?php

namespace App\Policies;

use App\Models\UiTranslation;
use App\Models\User;

class UiTranslationPolicy
{
    /**
     * Administration localisation / overrides : aligné sur la route (Master via permission « view any role »).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view any role');
    }

    public function view(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('view any role');
    }

    public function create(User $user): bool
    {
        return $user->can('view any role');
    }

    public function update(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('view any role');
    }

    public function delete(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('view any role');
    }

    public function restore(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('view any role');
    }

    public function forceDelete(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('view any role');
    }
}
