<?php

namespace App\Http\Controllers\Support;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketCategory;
use App\Notifications\SupportTicketCreatedNotification;
use App\Services\Support\SupportTicketAttachmentService;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MemberSupportController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = SupportTicket::query()->where('user_id', $request->user()->id)
            ->with('category:id,name')->latest('updated_at')->paginate(15)->withQueryString()
            ->through(fn (SupportTicket $ticket) => [
                'uuid' => $ticket->uuid,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'category' => $ticket->category?->name,
                'updated_at' => $ticket->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Support/Index', ['tickets' => $tickets]);
    }

    public function create(): Response
    {
        return Inertia::render('Support/MemberCreate', [
            'mode' => 'member',
            'categories' => SupportTicketCategory::query()->where('is_active', true)->where('is_member', true)
                ->orderBy('sort_order')->get(['id', 'name', 'description']),
            'attachmentRules' => ['max_kb' => config('support.attachments.max_kb'), 'extensions' => config('support.attachments.allowed_extensions')],
        ]);
    }

    public function store(Request $request, SupportTicketService $tickets, SupportTicketAttachmentService $attachments): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:support_ticket_categories,id'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file'],
        ]);
        $ticket = $tickets->createForMember($request->user(), $data);
        $message = $ticket->customerVisibleMessages()->oldest()->first();
        foreach (($request->allFiles()['attachments'] ?? []) as $file) {
            $attachments->store($ticket, $file, $message, $request->user(), true);
        }
        $request->user()->notify(new SupportTicketCreatedNotification($ticket));

        return redirect()->route('support.show', $ticket)->with('success', 'Your support request was submitted.');
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);
        return Inertia::render('Support/MemberShow', [
            'mode' => 'member',
            'ticket' => SupportPayload::ticket($ticket),
            'attachmentRules' => ['max_kb' => config('support.attachments.max_kb'), 'extensions' => config('support.attachments.allowed_extensions')],
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportTicketService $tickets, SupportTicketAttachmentService $attachments): RedirectResponse
    {
        $this->authorize('replyAsCustomer', $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000'], 'attachments' => ['nullable', 'array', 'max:5'], 'attachments.*' => ['file']]);
        $message = $tickets->addCustomerMessage($ticket, $data['body'], $request->user());
        foreach (($request->allFiles()['attachments'] ?? []) as $file) {
            $attachments->store($ticket, $file, $message, $request->user(), true);
        }
        return back()->with('success', 'Your reply was added.');
    }

    public function close(Request $request, SupportTicket $ticket, SupportTicketService $tickets): RedirectResponse
    {
        $this->authorize('view', $ticket);
        if ($ticket->status !== SupportTicketStatus::Closed) {
            $tickets->transition($ticket, $ticket->status === SupportTicketStatus::New ? SupportTicketStatus::Cancelled : SupportTicketStatus::Closed, $request->user(), 'Closed by customer.');
        }
        return back()->with('success', 'The ticket was closed.');
    }

    public function reopen(Request $request, SupportTicket $ticket, SupportTicketService $tickets): RedirectResponse
    {
        $this->authorize('view', $ticket);
        abort_unless($ticket->status->canReopen(), 422);
        $tickets->transition($ticket, SupportTicketStatus::Open, $request->user(), 'Reopened by customer.');
        return back()->with('success', 'The ticket was reopened.');
    }

    public function download(Request $request, SupportTicket $ticket, SupportTicketAttachment $attachment)
    {
        $this->authorize('view', $ticket);
        abort_unless($attachment->support_ticket_id === $ticket->id && $attachment->is_customer_visible, 404);
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_filename);
    }
}
