<?php

namespace App\Http\Controllers\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;

final class SupportPayload
{
    public static function ticket(SupportTicket $ticket, ?string $guestToken = null): array
    {
        $ticket->loadMissing(['category:id,name', 'customerVisibleMessages.attachments', 'relations']);

        return [
            'uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'category' => $ticket->category?->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'can_reply' => ! $ticket->status->isTerminal(),
            'can_reopen' => $ticket->status->canReopen(),
            'messages' => $ticket->customerVisibleMessages->map(fn ($message) => [
                'id' => $message->id,
                'author_name' => $message->author_name,
                'message_type' => $message->message_type->value,
                'body' => $message->body,
                'created_at' => $message->created_at?->toIso8601String(),
                'attachments' => $message->attachments
                    ->where('is_customer_visible', true)
                    ->map(fn (SupportTicketAttachment $attachment) => self::attachment($ticket, $attachment, $guestToken))
                    ->values(),
            ])->values(),
        ];
    }

    private static function attachment(SupportTicket $ticket, SupportTicketAttachment $attachment, ?string $guestToken): array
    {
        $url = $guestToken
            ? route('support.guest.attachments.download', [$ticket, $guestToken, $attachment])
            : route('support.attachments.download', [$ticket, $attachment]);

        return [
            'uuid' => $attachment->uuid,
            'name' => $attachment->original_filename,
            'size_bytes' => $attachment->size_bytes,
            'url' => $url,
        ];
    }
}
