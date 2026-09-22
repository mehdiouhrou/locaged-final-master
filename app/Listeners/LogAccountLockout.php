<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Request;

class LogAccountLockout
{
    public function handle(Lockout $event): void
    {
        $email = $event->request->input('email');
        $user = $email ? User::query()->where('email', $email)->first() : null;

        AuditService::logSubject(
            action: 'account_locked',
            subjectType: 'authentication',
            subjectId: $user?->id,
            metadata: [
                'email' => $email,
                'ip_address' => Request::ip(),
            ],
            userId: null,
            userName: $user?->full_name,
        );
    }
}
