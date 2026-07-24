<?php

namespace App\Services\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Str;

class GuestTicketAccessService
{
    public function issue(SupportTicket $ticket): string
    {
        $token = Str::random(64);
        $ticket->forceFill(['guest_access_token_hash' => hash('sha256', $token)])->save();

        return $token;
    }

    public function validate(SupportTicket $ticket, string $token): bool
    {
        return filled($ticket->guest_access_token_hash)
            && hash_equals($ticket->guest_access_token_hash, hash('sha256', $token));
    }

    public function revoke(SupportTicket $ticket): void
    {
        $ticket->forceFill(['guest_access_token_hash' => null])->save();
    }
}
