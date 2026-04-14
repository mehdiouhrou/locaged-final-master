<?php

namespace App\Policies;

use App\Models\DestructionCertificate;
use App\Models\User;

class DestructionCertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view any document')
            || $user->can('access document expiration management');
    }

    public function view(User $user, DestructionCertificate $certificate): bool
    {
        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('access document expiration management')) {
            return true;
        }

        if ($user->can('view own document') && $certificate->document?->created_by === $user->id) {
            return true;
        }

        return false;
    }
}
