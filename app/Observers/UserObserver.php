<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditService;

class UserObserver
{
    /**
     * Champs sensibles surveillés pour le detail avant/apres. password est volontairement
     * exclu : jamais de valeur de mot de passe, meme hachee, dans un log d\'audit.
     */
    private const TRACKED_FIELDS = ['full_name', 'phone', 'email', 'sub_department_id', 'service_id', 'active'];

    public function created(User $user): void
    {
        $actor = auth()->user();

        AuditService::logSubject(
            action: 'user_created',
            subjectType: 'user',
            subjectId: $user->id,
            metadata: [
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'sub_department_id' => $user->sub_department_id,
                'service_id' => $user->service_id,
                'active' => $user->active,
                'performed_by_id' => $actor?->id,
                'performed_by_name' => $actor?->full_name,
            ],
            userId: $user->id,
            userName: $user->full_name,
        );
    }

    public function updated(User $user): void
    {
        $changes = array_intersect_key($user->getChanges(), array_flip(self::TRACKED_FIELDS));

        if ($changes === []) {
            return;
        }

        $before = [];
        foreach (array_keys($changes) as $attr) {
            $before[$attr] = $user->getOriginal($attr);
        }

        $diff = AuditService::diff($before, $changes, array_keys($changes));

        $actor = auth()->user();

        AuditService::logSubject(
            action: 'user_updated',
            subjectType: 'user',
            subjectId: $user->id,
            metadata: [
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'changes' => $diff,
                'performed_by_id' => $actor?->id,
                'performed_by_name' => $actor?->full_name,
            ],
            userId: $user->id,
            userName: $user->full_name,
        );
    }

    public function deleted(User $user): void
    {
        $actor = auth()->user();

        AuditService::logSubject(
            action: 'user_deleted',
            subjectType: 'user',
            subjectId: $user->id,
            metadata: [
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'performed_by_id' => $actor?->id,
                'performed_by_name' => $actor?->full_name,
            ],
            userId: $user->id,
            userName: $user->full_name,
        );
    }
}
