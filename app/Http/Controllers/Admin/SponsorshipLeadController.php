<?php

namespace App\Http\Controllers\Admin;

use App\Advertising\AdvertisingWorkflowContextService;
use App\Http\Controllers\Controller;
use App\Models\{Advertiser, SponsorshipLead, User};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SponsorshipLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = SponsorshipLead::with(['advertiser', 'owner'])->latest();
        if ($request->filled('stage')) $query->where('stage', $request->stage);
        if ($request->filled('advertiser_id')) $query->where('advertiser_id', $request->integer('advertiser_id'));
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('company_name', 'like', '%'.$request->search.'%')->orWhere('contact_name', 'like', '%'.$request->search.'%'));
        }

        return Inertia::render('Admin/Sponsorship/Leads/Index', [
            'leads' => $query->get(),
            'filters' => $request->only(['stage', 'search', 'advertiser_id']),
        ]);
    }

    public function create(Request $request, AdvertisingWorkflowContextService $context)
    {
        $workflowContext = $context->fromRequest($request);
        $advertiser = $workflowContext['advertiser'];

        return Inertia::render('Admin/Sponsorship/Leads/Form', [
            'lead' => null,
            'advertisers' => Advertiser::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'workflowContext' => $workflowContext,
            'initialLead' => $advertiser ? [
                'advertiser_id' => $advertiser['id'],
                'company_name' => $advertiser['name'],
                'contact_name' => $advertiser['contact_name'],
                'contact_email' => $advertiser['contact_email'] ?: $advertiser['billing_email'],
                'contact_phone' => $advertiser['contact_phone'],
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $lead = SponsorshipLead::create(array_merge($this->data($request), ['uuid' => (string) Str::uuid()]));
        return to_route('admin.sponsorship-leads.show', $lead);
    }

    public function show(SponsorshipLead $sponsorshipLead)
    {
        return Inertia::render('Admin/Sponsorship/Leads/Show', ['lead' => $sponsorshipLead->load(['advertiser', 'owner', 'activities.user', 'proposals'])]);
    }

    public function edit(SponsorshipLead $sponsorshipLead, AdvertisingWorkflowContextService $context)
    {
        $sponsorshipLead->load('advertiser');
        return Inertia::render('Admin/Sponsorship/Leads/Form', [
            'lead' => $sponsorshipLead,
            'advertisers' => Advertiser::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'workflowContext' => $context->payload($sponsorshipLead->advertiser, $sponsorshipLead),
            'initialLead' => null,
        ]);
    }

    public function update(Request $request, SponsorshipLead $sponsorshipLead)
    {
        $data = $this->data($request);
        if (($data['stage'] ?? null) === 'won') $data['won_at'] = $sponsorshipLead->won_at ?: now();
        if (($data['stage'] ?? null) === 'lost') $data['lost_at'] = $sponsorshipLead->lost_at ?: now();
        $sponsorshipLead->update($data);
        return to_route('admin.sponsorship-leads.show', $sponsorshipLead);
    }

    public function activity(Request $request, SponsorshipLead $sponsorshipLead)
    {
        $data = $request->validate(['type' => 'required|in:note,email,call,meeting,task', 'subject' => 'required|string|max:255', 'details' => 'nullable|string', 'follow_up_at' => 'nullable|date']);
        $sponsorshipLead->activities()->create(array_merge($data, ['user_id' => $request->user()->id, 'occurred_at' => now()]));
        if (! empty($data['follow_up_at'])) $sponsorshipLead->update(['next_follow_up_at' => $data['follow_up_at']]);
        return back()->with('success', 'Sales activity recorded.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'advertiser_id' => 'nullable|exists:advertisers,id', 'assigned_to' => 'nullable|exists:users,id', 'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255', 'contact_email' => 'nullable|email|max:255', 'contact_phone' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:100', 'stage' => 'required|in:'.implode(',', SponsorshipLead::STAGES), 'estimated_value_cents' => 'required|integer|min:0',
            'probability' => 'required|integer|min:0|max:100', 'target_close_date' => 'nullable|date', 'next_follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string', 'lost_reason' => 'nullable|string|max:255',
        ]);
    }
}
