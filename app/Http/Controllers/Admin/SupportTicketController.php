<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', SupportTicket::class);

        $base = SupportTicket::query();
        $metrics = [
            'new' => (clone $base)->where('status', SupportTicketStatus::New)->count(),
            'unassigned' => (clone $base)->whereNull('assigned_user_id')->whereNotIn('status', [SupportTicketStatus::Closed, SupportTicketStatus::Cancelled])->count(),
            'waiting_on_staff' => (clone $base)->where('status', SupportTicketStatus::WaitingOnStaff)->count(),
            'urgent' => (clone $base)->where('priority', SupportTicketPriority::Urgent)->whereNotIn('status', [SupportTicketStatus::Closed, SupportTicketStatus::Cancelled])->count(),
            'resolved_today' => (clone $base)->whereDate('resolved_at', today())->count(),
            'open_total' => (clone $base)->whereNotIn('status', [SupportTicketStatus::Resolved, SupportTicketStatus::Closed, SupportTicketStatus::Cancelled])->count(),
        ];

        $oldest = SupportTicket::query()
            ->with(['user:id,name,email', 'category:id,name', 'assignee:id,name'])
            ->whereIn('status', [SupportTicketStatus::New, SupportTicketStatus::WaitingOnStaff])
            ->orderByRaw('COALESCE(last_customer_reply_at, created_at) asc')
            ->limit(10)->get()->map(fn (SupportTicket $ticket) => $this->ticketListPayload($ticket));

        $recent = SupportTicket::query()->with(['user:id,name,email', 'category:id,name', 'assignee:id,name'])
            ->latest('updated_at')->limit(10)->get()->map(fn (SupportTicket $ticket) => $this->ticketListPayload($ticket));

        return Inertia::render('Admin/Support/Dashboard', compact('metrics', 'oldest', 'recent'));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupportTicket::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(SupportTicketStatus::class)],
            'priority' => ['nullable', Rule::enum(SupportTicketPriority::class)],
            'category_id' => ['nullable', 'integer'],
            'assignee' => ['nullable', 'string', Rule::in(['mine', 'unassigned'])],
        ]);

        $query = SupportTicket::query()->with(['user:id,name,email', 'category:id,name', 'assignee:id,name']);
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('guest_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }
        if ($status = $filters['status'] ?? null) $query->where('status', $status);
        if ($priority = $filters['priority'] ?? null) $query->where('priority', $priority);
        if ($category = $filters['category_id'] ?? null) $query->where('category_id', $category);
        if (($filters['assignee'] ?? null) === 'mine') $query->where('assigned_user_id', $request->user()->id);
        if (($filters['assignee'] ?? null) === 'unassigned') $query->whereNull('assigned_user_id');

        $tickets = $query->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->latest('updated_at')->paginate(25)->withQueryString()->through(fn (SupportTicket $ticket) => $this->ticketListPayload($ticket));

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'statuses' => array_map(fn ($e) => $e->value, SupportTicketStatus::cases()),
            'priorities' => array_map(fn ($e) => $e->value, SupportTicketPriority::cases()),
            'categories' => SupportTicketCategory::query()->orderBy('sort_order')->get(['id','name']),
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);
        $ticket->load(['user:id,name,email,username', 'category', 'assignee:id,name,email', 'messages.user:id,name,email', 'messages.attachments', 'statusHistories.actor:id,name', 'statusHistories.fromAssignee:id,name', 'statusHistories.toAssignee:id,name', 'relations.related']);

        return Inertia::render('Admin/Support/Show', [
            'ticket' => [
                ...$this->ticketListPayload($ticket),
                'description' => $ticket->description,
                'resolution_summary' => $ticket->resolution_summary,
                'messages' => $ticket->messages->map(fn ($m) => [
                    'id' => $m->id, 'body' => $m->body, 'type' => $m->message_type->value,
                    'is_customer_visible' => $m->is_customer_visible, 'author_name' => $m->author_name,
                    'created_at' => $m->created_at?->format('M j, Y g:i A'),
                ]),
                'history' => $ticket->statusHistories->map(fn ($h) => [
                    'id' => $h->id, 'event_type' => $h->event_type, 'actor' => $h->actor?->name,
                    'from_status' => $h->from_status?->value, 'to_status' => $h->to_status?->value,
                    'from_priority' => $h->from_priority?->value, 'to_priority' => $h->to_priority?->value,
                    'from_assignee' => $h->fromAssignee?->name, 'to_assignee' => $h->toAssignee?->name,
                    'created_at' => $h->created_at?->format('M j, Y g:i A'),
                ]),
            ],
            'categories' => SupportTicketCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['id','name']),
            'staff' => User::query()->whereHas('permissions', fn ($q) => $q->where('name', 'view_support_tickets'))
                ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', 'view_support_tickets'))->orderBy('name')->get(['id','name','email']),
            'statuses' => array_map(fn ($e) => $e->value, SupportTicketStatus::cases()),
            'priorities' => array_map(fn ($e) => $e->value, SupportTicketPriority::cases()),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportTicketService $service): RedirectResponse
    {
        $this->authorize('reply', $ticket);
        $data = $request->validate(['body' => ['required','string','max:20000']]);
        $service->addStaffReply($ticket, $request->user(), $data['body']);
        return back()->with('success', 'Reply sent.');
    }

    public function note(Request $request, SupportTicket $ticket, SupportTicketService $service): RedirectResponse
    {
        $this->authorize('addInternalNote', $ticket);
        $data = $request->validate(['body' => ['required','string','max:20000']]);
        $service->addInternalNote($ticket, $request->user(), $data['body']);
        return back()->with('success', 'Internal note added.');
    }

    public function update(Request $request, SupportTicket $ticket, SupportTicketService $service): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $data = $request->validate([
            'category_id' => ['nullable','exists:support_ticket_categories,id'],
            'priority' => ['nullable', Rule::enum(SupportTicketPriority::class)],
            'assigned_user_id' => ['nullable','exists:users,id'],
            'status' => ['nullable', Rule::enum(SupportTicketStatus::class)],
            'resolution_summary' => ['nullable','string','max:5000'],
        ]);
        if (array_key_exists('category_id', $data)) $ticket->update(['category_id' => $data['category_id']]);
        if (isset($data['priority']) && $data['priority'] !== $ticket->priority->value) $service->changePriority($ticket, SupportTicketPriority::from($data['priority']), $request->user());
        if (array_key_exists('assigned_user_id', $data) && (int) $data['assigned_user_id'] !== (int) $ticket->assigned_user_id) $service->assign($ticket, $data['assigned_user_id'] ? User::find($data['assigned_user_id']) : null, $request->user());
        if (isset($data['resolution_summary'])) $ticket->update(['resolution_summary' => $data['resolution_summary']]);
        if (isset($data['status']) && $data['status'] !== $ticket->status->value) $service->transition($ticket, SupportTicketStatus::from($data['status']), $request->user());
        return back()->with('success', 'Ticket updated.');
    }

    private function ticketListPayload(SupportTicket $ticket): array
    {
        return [
            'uuid' => $ticket->uuid, 'ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject,
            'status' => $ticket->status->value, 'priority' => $ticket->priority->value,
            'customer' => $ticket->user?->name ?? $ticket->guest_name,
            'customer_email' => $ticket->user?->email ?? $ticket->guest_email,
            'category' => $ticket->category?->name, 'category_id' => $ticket->category_id,
            'assignee' => $ticket->assignee?->name, 'assigned_user_id' => $ticket->assigned_user_id,
            'created_at' => $ticket->created_at?->format('M j, Y g:i A'),
            'updated_at' => $ticket->updated_at?->format('M j, Y g:i A'),
            'age_hours' => $ticket->created_at?->diffInHours(now()),
        ];
    }
}
