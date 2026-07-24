<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_support_tickets');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $ticket->isOwnedBy($user) || $user->hasPermission('view_support_tickets');
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        if ($ticket->status->isTerminal()) {
            return false;
        }

        return $ticket->isOwnedBy($user) || $user->hasPermission('reply_support_tickets');
    }

    public function addInternalNote(User $user, SupportTicket $ticket): bool
    {
        return ! $ticket->status->isTerminal() && $user->hasPermission('add_support_internal_notes');
    }

    public function assign(User $user, SupportTicket $ticket): bool
    {
        return $user->hasPermission('assign_support_tickets');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->hasPermission('manage_support_tickets');
    }

    public function resolve(User $user, SupportTicket $ticket): bool
    {
        return $user->hasPermission('resolve_support_tickets');
    }

    public function reopen(User $user, SupportTicket $ticket): bool
    {
        return $ticket->status->canReopen()
            && ($ticket->isOwnedBy($user) || $user->hasPermission('resolve_support_tickets'));
    }

    public function viewInternalNotes(User $user, SupportTicket $ticket): bool
    {
        return $user->hasPermission('view_support_tickets');
    }
    public function replyAsCustomer(User $user, SupportTicket $ticket): bool
    {
        return $ticket->isOwnedBy($user) && ! $ticket->status->isTerminal();
    }

}
