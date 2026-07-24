<?php

namespace App\Enums;

enum SupportTicketMessageType: string
{
    case CustomerMessage = 'customer_message';
    case StaffReply = 'staff_reply';
    case InternalNote = 'internal_note';
    case SystemEvent = 'system_event';

    public function isCustomerVisible(): bool
    {
        return $this !== self::InternalNote;
    }
}
