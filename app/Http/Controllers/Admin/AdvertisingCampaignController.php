<?php

namespace App\Http\Controllers\Admin;

use App\Advertising\AdvertiserWorkflowService;
use App\Advertising\AdvertisingWorkflowContextService;
use App\Http\Controllers\Controller;
use App\Models\{AdPlacement, AdvertisingCampaign, Advertiser};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdvertisingCampaignController extends Controller
{
    private const OBJECTIVES = ['awareness', 'traffic', 'conversion', 'sponsorship'];

    public function index()
    {
        return Inertia::render('Admin/Advertising/Campaigns/Index', [
            'campaigns' => AdvertisingCampaign::with(['advertiser', 'placements'])->withCount('creatives')->latest()->get(),
        ]);
    }

    public function create(Request $request, AdvertisingWorkflowContextService $context)
    {
        return $this->form(null, $request->integer('advertiser_id') ?: null, $context->fromRequest($request));
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['uuid'] = (string) Str::uuid();
        $data['public_code'] = 'AD-'.now()->format('ym').'-'.Str::upper(Str::random(6));

        $campaign = AdvertisingCampaign::create($data);
        $campaign->placements()->sync($this->placementPivotData($request));

        return to_route('admin.ad-campaigns.show', $campaign)
            ->with('success', 'Advertising campaign created. Next step: prepare the campaign creatives.');
    }

    public function show(
        AdvertisingCampaign $adCampaign,
        AdvertisingWorkflowContextService $context,
        AdvertiserWorkflowService $workflow,
    ) {
        $campaign = $adCampaign->load(['advertiser', 'placements', 'creatives.placements', 'approver']);

        return Inertia::render('Admin/Advertising/Campaigns/Show', [
            'campaign' => $campaign,
            'workflowContext' => $context->payload($campaign->advertiser, null, $campaign),
            'nextStep' => $this->nextStep($campaign, $workflow),
            'launchReadiness' => $workflow->launchReadiness($campaign),
        ]);
    }

    public function edit(AdvertisingCampaign $adCampaign)
    {
        return $this->form($adCampaign);
    }

    public function update(Request $request, AdvertisingCampaign $adCampaign)
    {
        $adCampaign->update($this->data($request));
        $adCampaign->placements()->sync($this->placementPivotData($request, $adCampaign));

        return to_route('admin.ad-campaigns.show', $adCampaign)
            ->with('success', 'Advertising campaign updated. Continue with the next workflow step shown below.');
    }

    public function submit(AdvertisingCampaign $adCampaign)
    {
        abort_unless(in_array($adCampaign->status, ['draft', 'rejected'], true), 422);
        $adCampaign->update(['status' => 'submitted', 'submitted_at' => now(), 'rejection_reason' => null]);

        return back()->with('success', 'Campaign submitted. Next step: record the approval decision.');
    }

    public function approve(Request $request, AdvertisingCampaign $adCampaign)
    {
        $request->validate([
            'decision' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:decision,reject|nullable|string|max:2000',
        ]);

        if ($request->decision === 'approve') {
            $adCampaign->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'rejection_reason' => null,
            ]);
            $message = 'Campaign approved. Next step: complete launch-readiness checks and billing.';
        } else {
            $adCampaign->update([
                'status' => 'rejected',
                'approved_at' => null,
                'approved_by' => $request->user()->id,
                'rejection_reason' => $request->rejection_reason,
            ]);
            $message = 'Campaign rejected. Correct the campaign and resubmit it for approval.';
        }

        return back()->with('success', $message);
    }

    /**
     * Move an approved campaign into scheduled or active status.
     *
     * A future start date schedules the campaign. A blank start date or a start
     * date that has arrived activates it immediately. Calling this endpoint again
     * for a scheduled campaign on/after its start date activates it.
     */
    public function launch(AdvertisingCampaign $adCampaign, AdvertiserWorkflowService $workflow)
    {
        abort_unless(in_array($adCampaign->status, ['approved', 'scheduled'], true), 422);

        $readiness = $workflow->launchReadiness($adCampaign);

        if (! $readiness['ready']) {
            $failed = collect($readiness['checks'])
                ->where('required', true)
                ->where('passed', false)
                ->pluck('label')
                ->values()
                ->all();

            throw ValidationException::withMessages([
                'campaign' => 'Campaign is not launch ready: '.implode(', ', $failed).'.',
            ]);
        }

        $status = $adCampaign->starts_at?->isFuture() ? 'scheduled' : 'active';
        $adCampaign->update(['status' => $status]);

        if ($status === 'scheduled') {
            return back()->with('success', 'Campaign scheduled for '.$adCampaign->starts_at->format('M j, Y g:i A').'.');
        }

        return back()->with('success', 'Campaign is now active and eligible for ad delivery.');
    }

    private function form(?AdvertisingCampaign $campaign = null, ?int $selectedAdvertiserId = null, ?array $workflowContext = null)
    {
        if ($campaign) {
            $campaign->load(['placements', 'advertiser']);
            $workflowContext = (new AdvertisingWorkflowContextService())->payload($campaign->advertiser, null, $campaign);

            // Phase 2 proposal conversion stored a descriptive sentence in the
            // objective column. Normalize that legacy value in the form so the
            // next save repairs the record instead of silently failing validation.
            $campaign->setAttribute('objective', $this->normalizeObjective($campaign->objective));
        }

        return Inertia::render('Admin/Advertising/Campaigns/Form', [
            'campaign' => $campaign,
            'advertisers' => Advertiser::whereIn('status', ['active', 'prospect'])->orderBy('name')->get(['id', 'name']),
            'placements' => AdPlacement::where('is_active', true)->orderBy('name')->get(),
            'selectedAdvertiserId' => $selectedAdvertiserId,
            'workflowContext' => $workflowContext ?? ['active' => false, 'advertiser' => null, 'lead' => null, 'campaign' => null],
        ]);
    }

    private function data(Request $request): array
    {
        if ($request->filled('objective')) {
            $request->merge(['objective' => $this->normalizeObjective((string) $request->input('objective'))]);
        }

        return $request->validate([
            'advertiser_id' => 'required|exists:advertisers,id',
            'name' => 'required|string|max:255',
            'objective' => 'required|in:'.implode(',', self::OBJECTIVES),
            'pricing_model' => 'required|in:flat,cpm,cpc,sponsorship',
            'budget_cents' => 'required|integer|min:0',
            'contract_value_cents' => 'required|integer|min:0',
            'impression_goal' => 'nullable|integer|min:0',
            'click_goal' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'internal_notes' => 'nullable|string|max:5000',
            'placement_ids' => 'array',
            'placement_ids.*' => 'exists:ad_placements,id',
            'placement_priorities' => 'array',
            'placement_priorities.*' => 'integer|min:1|max:100',
        ]);
    }


    /**
     * Build campaign-placement pivot values without losing existing allocated
     * budget information. Rotation weight is stored in the existing `priority`
     * pivot column used by PublicAdDeliveryService.
     */
    private function placementPivotData(Request $request, ?AdvertisingCampaign $campaign = null): array
    {
        $priorities = $request->input('placement_priorities', []);
        $existing = $campaign
            ? $campaign->placements()->get()->keyBy('id')
            : collect();

        $pivot = [];

        foreach ($request->input('placement_ids', []) as $placementId) {
            $placementId = (int) $placementId;
            $current = $existing->get($placementId)?->pivot;
            $priority = (int) ($priorities[$placementId] ?? $current?->priority ?? 50);

            $values = [
                'priority' => max(1, min(100, $priority)),
            ];

            if ($current?->allocated_budget_cents !== null) {
                $values['allocated_budget_cents'] = (int) $current->allocated_budget_cents;
            }

            $pivot[$placementId] = $values;
        }

        return $pivot;
    }

    private function normalizeObjective(?string $objective): string
    {
        $objective = trim((string) $objective);

        if (in_array($objective, self::OBJECTIVES, true)) {
            return $objective;
        }

        if (Str::startsWith(Str::lower($objective), 'sponsorship proposal')) {
            return 'sponsorship';
        }

        return $objective;
    }

    private function nextStep(AdvertisingCampaign $campaign, AdvertiserWorkflowService $workflow): array
    {
        $creatives = $campaign->creatives;
        $allApproved = $creatives->isNotEmpty() && $creatives->every(fn ($creative) => $creative->status === 'approved');
        $allFilesExist = $creatives->isNotEmpty() && $creatives->every(fn ($creative) => filled($creative->media_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($creative->media_path));
        $allUrlsPresent = $creatives->isNotEmpty() && $creatives->every(fn ($creative) => filled($creative->destination_url));
        $allCompatible = $creatives->isNotEmpty() && $creatives->every(fn ($creative) => $creative->is_placement_compatible);
        $hasPlacements = $campaign->placements->isNotEmpty();
        $base = "/admin/ad-campaigns/{$campaign->id}";

        if (! $hasPlacements) {
            return [
                'eyebrow' => 'Campaign workflow',
                'title' => 'Assign campaign placements',
                'description' => 'This campaign does not yet have a placement. Add the positions where the advertiser will run before preparing final creatives.',
                'status' => 'attention',
                'action' => ['label' => 'Edit Campaign', 'href' => "{$base}/edit", 'method' => 'get'],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        if ($creatives->isEmpty()) {
            return [
                'eyebrow' => 'Campaign workflow · Creative preparation',
                'title' => 'Add the campaign creatives',
                'description' => 'The campaign is linked to the advertiser and placements. Add the required creative files and destination URLs next.',
                'status' => 'current',
                'action' => ['label' => 'Manage Creatives', 'href' => "{$base}/creatives", 'method' => 'get'],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        if (! $allApproved || ! $allFilesExist || ! $allUrlsPresent || ! $allCompatible) {
            $issues = [];
            if (! $allApproved) $issues[] = 'creative approval';
            if (! $allFilesExist) $issues[] = 'missing media files';
            if (! $allUrlsPresent) $issues[] = 'destination URLs';
            if (! $allCompatible) $issues[] = 'placement compatibility';

            return [
                'eyebrow' => 'Campaign workflow · Creative preparation',
                'title' => 'Complete creative readiness',
                'description' => 'Before campaign approval, resolve: '.implode(', ', $issues).'.',
                'status' => 'attention',
                'action' => ['label' => 'Manage Creatives', 'href' => "{$base}/creatives", 'method' => 'get'],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        if (in_array($campaign->status, ['draft', 'rejected'], true)) {
            return [
                'eyebrow' => 'Campaign workflow · Approval',
                'title' => 'Submit the campaign for approval',
                'description' => 'Placements and creatives are ready. Submit the campaign for final internal approval.',
                'status' => 'current',
                'action' => ['label' => 'Submit for Approval', 'href' => "{$base}/submit", 'method' => 'post', 'data' => []],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        if ($campaign->status === 'submitted') {
            return [
                'eyebrow' => 'Campaign workflow · Approval',
                'title' => 'Record the campaign approval decision',
                'description' => 'The campaign has been submitted and is waiting for approval.',
                'status' => 'waiting',
                'action' => ['label' => 'Approve Campaign', 'href' => "{$base}/decision", 'method' => 'post', 'data' => ['decision' => 'approve']],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        if (in_array($campaign->status, ['approved', 'scheduled'], true)) {
            $readiness = $workflow->launchReadiness($campaign);

            if (! $readiness['ready']) {
                return [
                    'eyebrow' => 'Campaign workflow · Launch readiness',
                    'title' => 'Resolve launch-readiness blockers',
                    'description' => $readiness['blocking_count'].' required launch check(s) still need attention before this campaign can run.',
                    'status' => 'attention',
                    'action' => ['label' => 'Open Client Workflow', 'href' => "/admin/advertisers/{$campaign->advertiser_id}", 'method' => 'get'],
                    'secondary' => ['label' => 'Edit Campaign', 'href' => "{$base}/edit"],
                ];
            }

            if ($campaign->starts_at?->isFuture()) {
                if ($campaign->status === 'scheduled') {
                    return [
                        'eyebrow' => 'Campaign workflow · Scheduled',
                        'title' => 'Campaign is scheduled',
                        'description' => 'This campaign is launch ready and scheduled for '.$campaign->starts_at->format('M j, Y g:i A').'. On or after that time, return here to activate it.',
                        'status' => 'waiting',
                        'action' => ['label' => 'Edit Schedule', 'href' => "{$base}/edit", 'method' => 'get'],
                        'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
                    ];
                }

                return [
                    'eyebrow' => 'Campaign workflow · Launch readiness',
                    'title' => 'Schedule the launch-ready campaign',
                    'description' => 'All required launch checks pass. The start date is in the future, so scheduling will hold the campaign until its start time.',
                    'status' => 'current',
                    'action' => ['label' => 'Schedule Campaign', 'href' => "{$base}/launch", 'method' => 'post', 'data' => []],
                    'secondary' => ['label' => 'Edit Schedule', 'href' => "{$base}/edit"],
                ];
            }

            return [
                'eyebrow' => 'Campaign workflow · Launch readiness',
                'title' => 'Activate the launch-ready campaign',
                'description' => 'All required launch checks pass and the campaign start time has arrived. Activate it to make it eligible for ad delivery.',
                'status' => 'current',
                'action' => ['label' => 'Activate Campaign', 'href' => "{$base}/launch", 'method' => 'post', 'data' => []],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        if ($campaign->status === 'active') {
            return [
                'eyebrow' => 'Campaign workflow · Live',
                'title' => 'Monitor campaign performance',
                'description' => 'This campaign is live. Continue with performance monitoring, billing, and advertiser reporting.',
                'status' => 'complete',
                'action' => ['label' => 'View Analytics', 'href' => '/admin/analytics/campaigns', 'method' => 'get'],
                'secondary' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}"],
            ];
        }

        return [
            'eyebrow' => 'Campaign workflow',
            'title' => 'Review campaign status',
            'description' => 'Review this campaign and the advertiser workspace for the next required action.',
            'status' => 'current',
            'action' => ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$campaign->advertiser_id}", 'method' => 'get'],
        ];
    }
}
