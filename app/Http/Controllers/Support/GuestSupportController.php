<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketCategory;
use App\Notifications\SupportTicketCreatedNotification;
use App\Services\Support\GuestTicketAccessService;
use App\Services\Support\SupportTicketAttachmentService;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GuestSupportController extends Controller
{
    public function landing(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('support.index');
        }

        return Inertia::render('Support/Landing');
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('support.member.create');
        }

        return Inertia::render('Support/GuestCreate', [
            'mode' => 'guest',
            'categories' => $this->categories('public'),
            'attachmentRules' => $this->attachmentRules(),
        ]);
    }

    public function store(Request $request, SupportTicketService $tickets, SupportTicketAttachmentService $attachments): RedirectResponse
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email:rfc', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:support_ticket_categories,id'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file'],
        ]);

        $result = $tickets->createForGuest($data);
        $message = $result['ticket']->customerVisibleMessages()->oldest()->first();
        foreach (($request->allFiles()['attachments'] ?? []) as $file) {
            $attachments->store($result['ticket'], $file, $message, null, true);
        }

        Notification::route('mail', [$data['guest_email'] => $data['guest_name']])
            ->notify(new SupportTicketCreatedNotification($result['ticket'], $result['token']));

        return redirect()->route('support.guest.show', [$result['ticket'], $result['token']])
            ->with('success', 'Your support request was submitted. Save this secure link and check your email for a copy.');
    }

    public function show(SupportTicket $ticket, string $token, GuestTicketAccessService $access): Response
    {
        abort_unless($ticket->isGuestTicket() && $access->validate($ticket, $token), 404);

        return Inertia::render('Support/GuestShow', [
            'mode' => 'guest',
            'ticket' => SupportPayload::ticket($ticket, $token),
            'guestToken' => $token,
            'attachmentRules' => $this->attachmentRules(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, string $token, GuestTicketAccessService $access, SupportTicketService $tickets, SupportTicketAttachmentService $attachments): RedirectResponse
    {
        abort_unless($ticket->isGuestTicket() && $access->validate($ticket, $token), 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file'],
        ]);
        $message = $tickets->addCustomerMessage($ticket, $data['body'], null, $ticket->guest_name, $ticket->guest_email);
        foreach (($request->allFiles()['attachments'] ?? []) as $file) {
            $attachments->store($ticket, $file, $message, null, true);
        }

        return back()->with('success', 'Your reply was added.');
    }

    public function download(SupportTicket $ticket, string $token, SupportTicketAttachment $attachment, GuestTicketAccessService $access)
    {
        abort_unless($ticket->isGuestTicket() && $access->validate($ticket, $token), 404);
        abort_unless($attachment->support_ticket_id === $ticket->id && $attachment->is_customer_visible && ! $attachment->isRedacted(), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_filename);
    }

    private function categories(string $audience): array
    {
        return SupportTicketCategory::query()->where('is_active', true)
            ->where("is_{$audience}", true)
            ->orderBy('sort_order')->get(['id', 'name', 'description'])->toArray();
    }

    private function attachmentRules(): array
    {
        return ['max_kb' => config('support.attachments.max_kb'), 'extensions' => config('support.attachments.allowed_extensions')];
    }
}
