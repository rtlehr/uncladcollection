<?php

namespace App\Services\Support;

use App\Enums\SupportTicketMessageType;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketSource;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportTicketService
{
    public function __construct(
        private readonly SupportTicketNumberService $numbers,
        private readonly GuestTicketAccessService $guestAccess,
    ) {}

    public function createForMember(User $user, array $data): SupportTicket
    {
        return $this->create($data, $user, null, SupportTicketSource::Member)['ticket'];
    }

    /** @return array{ticket: SupportTicket, token: string} */
    public function createForGuest(array $data): array
    {
        return $this->create($data, null, [
            'name' => $data['guest_name'],
            'email' => $data['guest_email'],
        ], SupportTicketSource::Public);
    }

    public function addCustomerMessage(SupportTicket $ticket, string $body, ?User $user = null, ?string $guestName = null, ?string $guestEmail = null): SupportTicketMessage
    {
        $this->ensureWritable($ticket);

        return DB::transaction(function () use ($ticket, $body, $user, $guestName, $guestEmail): SupportTicketMessage {
            $message = $ticket->messages()->create([
                'user_id' => $user?->id,
                'author_name' => $user?->name ?? $guestName ?? $ticket->guest_name,
                'author_email' => $user?->email ?? $guestEmail ?? $ticket->guest_email,
                'message_type' => SupportTicketMessageType::CustomerMessage,
                'body' => $body,
                'is_customer_visible' => true,
            ]);

            $ticket->update([
                'status' => SupportTicketStatus::WaitingOnStaff,
                'last_customer_reply_at' => now(),
            ]);

            return $message;
        });
    }

    public function addStaffReply(SupportTicket $ticket, User $staff, string $body): SupportTicketMessage
    {
        $this->ensureWritable($ticket);

        return DB::transaction(function () use ($ticket, $staff, $body): SupportTicketMessage {
            $message = $ticket->messages()->create([
                'user_id' => $staff->id,
                'author_name' => $staff->name,
                'author_email' => $staff->email,
                'message_type' => SupportTicketMessageType::StaffReply,
                'body' => $body,
                'is_customer_visible' => true,
            ]);

            $ticket->update([
                'status' => SupportTicketStatus::WaitingOnCustomer,
                'last_staff_reply_at' => now(),
                'first_response_at' => $ticket->first_response_at ?? now(),
            ]);

            return $message;
        });
    }

    public function addInternalNote(SupportTicket $ticket, User $staff, string $body): SupportTicketMessage
    {
        return $ticket->messages()->create([
            'user_id' => $staff->id,
            'author_name' => $staff->name,
            'author_email' => $staff->email,
            'message_type' => SupportTicketMessageType::InternalNote,
            'body' => $body,
            'is_customer_visible' => false,
        ]);
    }

    public function transition(SupportTicket $ticket, SupportTicketStatus $to, ?User $actor = null, ?string $publicNote = null, ?string $internalNote = null): SupportTicket
    {
        $from = $ticket->status;
        $allowed = $this->allowedTransitions($from);

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => "Ticket status cannot change from {$from->value} to {$to->value}."]);
        }

        return DB::transaction(function () use ($ticket, $from, $to, $actor, $publicNote, $internalNote): SupportTicket {
            SupportTicketStatusHistory::create([
                'support_ticket_id' => $ticket->id,
                'actor_id' => $actor?->id,
                'event_type' => 'status_change',
                'from_status' => $from,
                'to_status' => $to,
                'public_note' => $publicNote,
                'internal_note' => $internalNote,
            ]);

            $ticket->update([
                'status' => $to,
                'resolved_at' => $to === SupportTicketStatus::Resolved ? now() : ($to->canReopen() ? null : $ticket->resolved_at),
                'closed_at' => $to === SupportTicketStatus::Closed ? now() : null,
            ]);

            return $ticket->refresh();
        });
    }

    public function assign(SupportTicket $ticket, ?User $assignee, ?User $actor = null): SupportTicket
    {
        $from = $ticket->assigned_user_id;

        SupportTicketStatusHistory::create([
            'support_ticket_id' => $ticket->id,
            'actor_id' => $actor?->id,
            'event_type' => 'assignment_change',
            'from_assigned_user_id' => $from,
            'to_assigned_user_id' => $assignee?->id,
        ]);

        $ticket->update(['assigned_user_id' => $assignee?->id]);

        return $ticket->refresh();
    }

    public function changePriority(SupportTicket $ticket, SupportTicketPriority $priority, ?User $actor = null): SupportTicket
    {
        $from = $ticket->priority;
        SupportTicketStatusHistory::create([
            'support_ticket_id' => $ticket->id,
            'actor_id' => $actor?->id,
            'event_type' => 'priority_change',
            'from_priority' => $from,
            'to_priority' => $priority,
        ]);
        $ticket->update(['priority' => $priority]);

        return $ticket->refresh();
    }

    public function relate(SupportTicket $ticket, Model $related, ?User $actor = null, ?string $label = null): void
    {
        $allowed = (array) config('support.allowed_relation_types', []);
        if (! in_array($related::class, $allowed, true)) {
            throw ValidationException::withMessages(['related' => 'This record type cannot be linked to a support ticket.']);
        }

        $ticket->relations()->firstOrCreate([
            'related_type' => $related->getMorphClass(),
            'related_id' => $related->getKey(),
        ], [
            'label' => $label,
            'added_by' => $actor?->id,
        ]);
    }

    /** @return array{ticket: SupportTicket, token: string} */
    private function create(array $data, ?User $user, ?array $guest, SupportTicketSource $source): array
    {
        return DB::transaction(function () use ($data, $user, $guest, $source): array {
            $category = isset($data['category_id']) ? SupportTicketCategory::find($data['category_id']) : null;
            $priority = $category?->default_priority ?? SupportTicketPriority::Normal;

            $ticket = SupportTicket::create([
                'uuid' => (string) Str::uuid(),
                'ticket_number' => $this->numbers->next(),
                'user_id' => $user?->id,
                'guest_name' => $guest['name'] ?? null,
                'guest_email' => $guest['email'] ?? null,
                'category_id' => $category?->id,
                'assigned_user_id' => $category?->default_assignee_id,
                'status' => SupportTicketStatus::New,
                'priority' => $priority,
                'source' => $source,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'last_customer_reply_at' => now(),
            ]);

            $ticket->messages()->create([
                'user_id' => $user?->id,
                'author_name' => $user?->name ?? $guest['name'] ?? null,
                'author_email' => $user?->email ?? $guest['email'] ?? null,
                'message_type' => SupportTicketMessageType::CustomerMessage,
                'body' => $data['description'],
                'is_customer_visible' => true,
            ]);

            $token = $guest ? $this->guestAccess->issue($ticket) : '';

            return ['ticket' => $ticket->refresh(), 'token' => $token];
        });
    }

    private function ensureWritable(SupportTicket $ticket): void
    {
        if ($ticket->status->isTerminal()) {
            throw ValidationException::withMessages(['ticket' => 'This ticket is closed and cannot receive new messages.']);
        }
    }

    /** @return list<SupportTicketStatus> */
    private function allowedTransitions(SupportTicketStatus $from): array
    {
        return match ($from) {
            SupportTicketStatus::New => [SupportTicketStatus::Open, SupportTicketStatus::InProgress, SupportTicketStatus::Cancelled],
            SupportTicketStatus::Open, SupportTicketStatus::WaitingOnStaff, SupportTicketStatus::WaitingOnCustomer, SupportTicketStatus::InProgress => [SupportTicketStatus::Open, SupportTicketStatus::WaitingOnStaff, SupportTicketStatus::WaitingOnCustomer, SupportTicketStatus::InProgress, SupportTicketStatus::Resolved, SupportTicketStatus::Closed, SupportTicketStatus::Cancelled],
            SupportTicketStatus::Resolved => [SupportTicketStatus::Open, SupportTicketStatus::Closed],
            SupportTicketStatus::Closed, SupportTicketStatus::Cancelled => [],
        };
    }
}
