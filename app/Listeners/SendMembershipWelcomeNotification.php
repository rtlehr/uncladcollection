<?php

namespace App\Listeners;

use App\Notifications\WelcomeMembershipEmail;
use Illuminate\Auth\Events\Verified;

class SendMembershipWelcomeNotification
{
    public function handle(Verified $event): void
    {
        $event->user->notify(new WelcomeMembershipEmail);
    }
}
