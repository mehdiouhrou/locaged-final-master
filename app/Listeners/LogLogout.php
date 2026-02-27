<?php

namespace App\Listeners;

use App\Models\AuthenticationLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            AuthenticationLog::create([
                'user_id' => $event->user->id,
                'user_name' => $event->user->full_name,
                'email' => $event->user->email,
                'type' => 'logout',
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'occurred_at' => now(),
            ]);
        }
    }
}
