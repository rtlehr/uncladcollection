<?php

namespace App\Services\Support;

use App\Models\SupportTicket;
use RuntimeException;

class SupportTicketNumberService
{
    public function next(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $number = 'UC-'.random_int(100001, 999999);

            if (! SupportTicket::withTrashed()->where('ticket_number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('Unable to allocate a unique support ticket number.');
    }
}
