<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportAttachmentController extends Controller
{
    public function redact(Request $request, SupportTicket $ticket, SupportTicketAttachment $attachment): RedirectResponse
    {
        $this->authorize('update', $ticket);
        abort_unless($attachment->support_ticket_id === $ticket->id, 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($attachment->redacted_at === null) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->update([
                'redacted_at' => now(),
                'redacted_by' => $request->user()->id,
                'redaction_reason' => $data['reason'],
                'path' => '',
                'is_customer_visible' => false,
            ]);
        }

        return back()->with('success', 'Attachment redacted.');
    }
}
