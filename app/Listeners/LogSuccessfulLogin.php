<?php

namespace App\Listeners;

use App\Models\AuthenticationLog;
use App\Services\AuditService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        AuthenticationLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->full_name,
            'email' => $event->user->email,
            'type' => 'login_success',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ]);

        AuditService::logSubject(
            action: 'login_success',
            subjectType: 'authentication',
            subjectId: $event->user->id,
            metadata: [
                'email' => $event->user->email,
            ],
            actor: $event->user,
            userId: $event->user->id,
            userName: $event->user->full_name,
        );
    }
}
