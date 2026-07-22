<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Models\SponsorshipProposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProposalController extends PortalController
{
    public function index(Request $request)
    {
        $advertiser = $this->advertiser($request);

        return Inertia::render('Advertiser/Proposals/Index', [
            'advertiser' => $advertiser,
            'membership' => $this->membership($request),
            'proposals' => $advertiser->sponsorshipProposals()
                ->with(['package', 'acceptance'])
                ->latest()
                ->get(),
        ]);
    }

    public function show(Request $request, SponsorshipProposal $proposal)
    {
        $this->authorizeProposal($request, $proposal);

        if ($proposal->status === 'sent' && $proposal->isExpired()) {
            $this->transition($request, $proposal, 'expired', 'Proposal expiration date passed.', 'system');
        }

        return Inertia::render('Advertiser/Proposals/Show', [
            'advertiser' => $this->advertiser($request),
            'membership' => $this->membership($request),
            'proposal' => $proposal->fresh()->load([
                'package', 'items.placement', 'acceptance', 'statusHistory.user', 'campaign', 'invoice',
            ]),
            'canRespond' => $this->canRespond($request, $proposal->fresh()),
        ]);
    }

    public function accept(Request $request, SponsorshipProposal $proposal)
    {
        $this->authorizeProposal($request, $proposal);
        abort_unless($this->canRespond($request, $proposal), 403);
        abort_if($proposal->isExpired(), 422, 'This proposal has expired.');

        $data = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_title' => 'nullable|string|max:255',
            'signer_email' => 'required|email|max:255',
            'signer_company' => 'nullable|string|max:255',
            'terms_acknowledged' => 'accepted',
        ]);

        DB::transaction(function () use ($request, $proposal, $data): void {
            $proposal->acceptance()->create([
                ...$data,
                'user_id' => $request->user()->id,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $this->transition($request, $proposal, 'accepted', null, 'advertiser_portal');
        });

        return back()->with('success', 'Proposal accepted. Our team will complete campaign setup and invoicing.');
    }

    public function decline(Request $request, SponsorshipProposal $proposal)
    {
        $this->authorizeProposal($request, $proposal);
        abort_unless($this->canRespond($request, $proposal), 403);
        abort_if($proposal->isExpired(), 422, 'This proposal has expired.');

        $data = $request->validate(['reason' => 'required|string|max:2000']);
        $this->transition($request, $proposal, 'declined', $data['reason'], 'advertiser_portal');

        return back()->with('success', 'Proposal declined. Your feedback has been recorded.');
    }

    private function authorizeProposal(Request $request, SponsorshipProposal $proposal): void
    {
        abort_unless($proposal->advertiser_id === $this->advertiser($request)->id, 404);
    }

    private function canRespond(Request $request, SponsorshipProposal $proposal): bool
    {
        return in_array($this->membership($request)->role, ['owner', 'campaign_manager'], true)
            && $proposal->canBeRespondedTo();
    }

    private function transition(Request $request, SponsorshipProposal $proposal, string $status, ?string $reason, string $source): void
    {
        $from = $proposal->status;
        $updates = ['status' => $status];
        if ($status === 'accepted') $updates['accepted_at'] = now();
        if ($status === 'declined') $updates['declined_at'] = now();
        $proposal->update($updates);
        $proposal->statusHistory()->create([
            'user_id' => $request->user()?->id,
            'from_status' => $from,
            'to_status' => $status,
            'reason' => $reason,
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
