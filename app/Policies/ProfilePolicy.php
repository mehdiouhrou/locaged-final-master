<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view any profile');
    }

    public function view(User $user, Profile $profile): bool
    {
        return $user->can('view any profile');
    }

    public function create(User $user): bool
    {
        return $user->can('create profile');
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->can('update profile');
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->can('delete profile');
    }
}
