<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case New = 'new';
    case Open = 'open';
    case WaitingOnStaff = 'waiting_on_staff';
    case WaitingOnCustomer = 'waiting_on_customer';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    public function canReopen(): bool
    {
        return $this === self::Resolved;
    }
}
