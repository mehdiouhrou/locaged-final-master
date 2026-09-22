<?php

namespace App\Listeners;

use App\Models\AuthenticationLog;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'unknown';

        AuthenticationLog::create([
            'user_id' => null,
            'user_name' => $event->user?->full_name ?? null,
            'email' => $email,
            'type' => 'login_failed',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ]);

        AuditService::logSubject(
            action: 'login_failed',
            subjectType: 'authentication',
            subjectId: $event->user?->id,
            metadata: [
                'email' => $email,
                'reason' => $event->user ? 'invalid_credentials' : 'unknown_account',
            ],
            userId: null,
            userName: $event->user?->full_name,
        );
    }
}
