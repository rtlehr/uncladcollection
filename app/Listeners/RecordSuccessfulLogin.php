<?php

namespace App\Listeners;

use App\Models\AccountSecurityEvent;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class RecordSuccessfulLogin
{
    public function __construct(private readonly Request $request) {}

    public function handle(Login $event): void
    {
        AccountSecurityEvent::query()->create([
            'user_id' => $event->user->getAuthIdentifier(),
            'event_type' => 'login_success',
            'description' => 'Successful account sign-in.',
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => ['remember' => $event->remember],
            'occurred_at' => now(),
        ]);
    }
}
