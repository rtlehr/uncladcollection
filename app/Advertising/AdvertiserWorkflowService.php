<?php

namespace App\Advertising;

use App\Models\Advertiser;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingInvoice;
use App\Models\SponsorshipLead;
use Illuminate\Support\Facades\Storage;

class AdvertiserWorkflowService
{
    public function summarize(Advertiser $advertiser): array
    {
        $advertiser->loadMissing([
            'memberships.user',
            'campaigns.placements',
            'campaigns.creatives.placements',
            'sponsorshipProposals',
        ]);

        $leads = SponsorshipLead::query()
            ->where('advertiser_id', $advertiser->id)
            ->latest()
            ->get();

        $invoices = AdvertisingInvoice::query()
            ->where('advertiser_id', $advertiser->id)
            ->latest()
            ->get();

        $campaigns = $advertiser->campaigns
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (AdvertisingCampaign $campaign) => $this->campaignSummary($campaign, $invoices))
            ->all();

        $stages = $this->advertiserStages($advertiser, $leads, $campaigns, $invoices);
        $nextAction = collect($stages)->first(fn (array $stage) => in_array($stage['state'], ['attention', 'current', 'waiting', 'not_started'], true));

        return [
            'stages' => $stages,
            'next_action' => $nextAction ? [
                'stage' => $nextAction['key'],
                'title' => $nextAction['next_action_title'] ?? $nextAction['label'],
                'description' => $nextAction['next_action_description'] ?? $nextAction['description'],
                'href' => $nextAction['href'] ?? null,
                'action_label' => $nextAction['action_label'] ?? null,
            ] : [
                'stage' => 'complete',
                'title' => 'No action required',
                'description' => 'The advertiser workflow is currently complete.',
                'href' => null,
                'action_label' => null,
            ],
            'campaigns' => $campaigns,
            'stats' => [
                'portal_members' => $advertiser->memberships->where('is_active', true)->count(),
                'proposals' => $advertiser->sponsorshipProposals->count(),
                'campaigns' => $advertiser->campaigns->count(),
                'active_campaigns' => $advertiser->campaigns->where('status', 'active')->count(),
                'open_balance_cents' => (int) $invoices->sum('balance_cents'),
            ],
        ];
    }

    public function compactSummary(Advertiser $advertiser): array
    {
        $summary = $this->summarize($advertiser);
        $next = $summary['next_action'];
        $attentionCount = collect($summary['stages'])->where('state', 'attention')->count();

        return [
            'current_stage' => $next['stage'],
            'current_stage_label' => collect($summary['stages'])->firstWhere('key', $next['stage'])['label'] ?? 'Complete',
            'next_action' => $next['title'],
            'attention_count' => $attentionCount,
            'health' => $attentionCount > 0 ? 'attention' : ($advertiser->status === 'inactive' ? 'inactive' : 'good'),
            'active_campaigns' => $summary['stats']['active_campaigns'],
        ];
    }

    private function advertiserStages(Advertiser $advertiser, $leads, array $campaigns, $invoices): array
    {
        $proposalStatuses = $advertiser->sponsorshipProposals->pluck('status');
        $activeLead = $leads->first(fn ($lead) => ! in_array($lead->stage, ['won', 'lost'], true)) ?? $leads->first();
        $activeProposal = $advertiser->sponsorshipProposals->sortByDesc('created_at')->first();
        $firstCampaign = collect($campaigns)->first();
        $hasCampaign = count($campaigns) > 0;
        $hasActiveCampaign = collect($campaigns)->contains(fn (array $campaign) => $campaign['status'] === 'active');
        $hasPausedCampaign = collect($campaigns)->contains(fn (array $campaign) => $campaign['status'] === 'paused');
        $hasReadyCampaign = collect($campaigns)->contains(fn (array $campaign) => $campaign['readiness']['ready']);
        $hasCampaignAttention = collect($campaigns)->contains(fn (array $campaign) => ! $campaign['readiness']['ready'] && $campaign['readiness']['blocking_count'] > 0);
        $allCampaignsCompleted = $hasCampaign && collect($campaigns)->every(fn (array $campaign) => in_array($campaign['status'], ['completed', 'canceled'], true));

        $clientComplete = filled($advertiser->contact_email) || filled($advertiser->billing_email);
        $portalComplete = $advertiser->memberships->where('is_active', true)->isNotEmpty();

        $stages = [];
        $stages[] = $this->stage(
            'client', 'Client Setup',
            $clientComplete ? 'complete' : 'attention',
            $clientComplete ? 'Advertiser contact details are in place.' : 'Add a primary contact or billing email.',
            "/admin/advertisers/{$advertiser->id}/edit",
            $clientComplete ? null : 'Complete client details',
            $clientComplete ? null : 'Add Contact Details'
        );

        $stages[] = $this->stage(
            'portal', 'Portal Access',
            $portalComplete ? 'complete' : 'attention',
            $portalComplete ? 'At least one active advertiser portal member exists.' : 'No active portal members have been assigned.',
            "/admin/advertisers/{$advertiser->id}/edit",
            $portalComplete ? null : 'Add advertiser portal members',
            $portalComplete ? null : 'Add Portal Member'
        );

        $leadState = $leads->isEmpty() ? ($hasCampaign || $proposalStatuses->isNotEmpty() ? 'complete' : 'not_started') : ($leads->contains(fn ($lead) => ! in_array($lead->stage, ['won', 'lost'], true)) ? 'current' : 'complete');
        $stages[] = $this->stage(
            'opportunity', 'Sales Opportunity', $leadState,
            $leads->isEmpty() ? 'No linked sales opportunity. This step is optional when creating campaigns directly.' : $leads->count().' linked sales '.str('opportunity')->plural($leads->count()).'.',
            $activeLead ? "/admin/sponsorship-leads/{$activeLead->id}" : "/admin/sponsorship-leads/create?advertiser_id={$advertiser->id}",
            $leadState === 'not_started' ? 'Create a sales opportunity for this advertiser' : ($leadState === 'current' ? 'Continue this advertiser’s sales opportunity' : null),
            $leadState === 'not_started' ? 'Create Opportunity' : ($leadState === 'current' ? 'Open Opportunity' : null)
        );

        $proposalState = match (true) {
            $proposalStatuses->contains('converted') => 'complete',
            $proposalStatuses->contains('accepted') => 'current',
            $proposalStatuses->contains('sent') => 'waiting',
            $proposalStatuses->contains('draft') => 'current',
            $hasCampaign => 'complete',
            default => 'not_started',
        };

        $proposalDescription = match ($activeProposal?->status) {
            'draft' => 'The proposal is a draft. Review it and mark it sent when it has been delivered to the advertiser.',
            'sent' => 'The proposal has been sent and is awaiting the advertiser’s decision.',
            'accepted' => 'The proposal is accepted. Convert it to create the campaign and billing records without re-entering the client details.',
            'converted' => 'The accepted proposal has been converted into the campaign workflow.',
            'declined' => 'The proposal was declined. Review the proposal or sales opportunity before proceeding.',
            default => $proposalState === 'complete' ? 'Proposal requirement is satisfied.' : 'Prepare and progress the commercial proposal.',
        };

        $proposalNextTitle = match ($activeProposal?->status) {
            'draft' => 'Send the proposal',
            'sent' => 'Record the advertiser decision',
            'accepted' => 'Convert the accepted proposal to a campaign',
            'declined' => 'Review the declined proposal',
            default => $proposalState === 'not_started' ? 'Create a proposal for this advertiser' : null,
        };

        $proposalActionLabel = match ($activeProposal?->status) {
            'draft', 'sent', 'accepted', 'declined' => 'Open Proposal',
            default => $proposalState === 'not_started' ? 'Create Proposal' : null,
        };

        $stages[] = $this->stage(
            'proposal', 'Proposal', $proposalState,
            $proposalDescription,
            $activeProposal ? "/admin/sponsorship-proposals/{$activeProposal->id}" : "/admin/sponsorship-proposals/create?advertiser_id={$advertiser->id}".($activeLead ? "&lead_id={$activeLead->id}" : ''),
            $proposalNextTitle,
            $proposalActionLabel
        );

        $campaignState = $hasCampaign ? 'complete' : 'not_started';
        $stages[] = $this->stage(
            'campaign', 'Campaign Setup', $campaignState,
            $hasCampaign ? count($campaigns).' campaign(s) are linked to this advertiser.' : 'No advertising campaign has been created yet.',
            $hasCampaign ? "/admin/advertisers/{$advertiser->id}" : "/admin/ad-campaigns/create?advertiser_id={$advertiser->id}",
            $hasCampaign ? null : 'Create the first advertising campaign',
            $hasCampaign ? null : 'Create Campaign'
        );

        $creativeState = ! $hasCampaign ? 'not_started' : ($hasCampaignAttention ? 'attention' : (collect($campaigns)->every(fn ($c) => $c['creative_counts']['total'] > 0 && $c['creative_counts']['approved'] === $c['creative_counts']['total']) ? 'complete' : 'current'));
        $stages[] = $this->stage(
            'creative', 'Creative Preparation', $creativeState,
            ! $hasCampaign ? 'Create a campaign before adding creatives.' : ($creativeState === 'complete' ? 'All current campaign creatives are approved.' : 'One or more campaigns still need creative work or approval.'),
            $hasCampaign ? $this->firstCampaignHref($campaigns, 'creatives') : null,
            $creativeState === 'complete' ? null : 'Complete creative preparation and approval',
            $creativeState === 'complete' ? null : 'Manage Creatives'
        );

        $approvalState = ! $hasCampaign ? 'not_started' : (collect($campaigns)->contains(fn ($c) => in_array($c['status'], ['draft', 'submitted', 'rejected'], true)) ? 'current' : 'complete');
        $stages[] = $this->stage(
            'approval', 'Campaign Approval', $approvalState,
            $approvalState === 'complete' ? 'Campaign approval requirements are satisfied.' : 'A campaign is waiting for submission, approval, or correction.',
            $hasCampaign ? $this->firstCampaignHref($campaigns) : null,
            $approvalState === 'complete' ? null : 'Review campaign approval status',
            $approvalState === 'complete' ? null : 'Review Campaign'
        );

        $billingState = ! $hasCampaign ? 'not_started' : ($invoices->isEmpty() ? 'current' : ($invoices->sum('balance_cents') > 0 ? 'waiting' : 'complete'));
        $stages[] = $this->stage(
            'billing', 'Billing', $billingState,
            $invoices->isEmpty() ? 'No invoice has been created.' : ($invoices->sum('balance_cents') > 0 ? 'An advertising balance remains outstanding.' : 'Advertising invoices are paid or have no balance.'),
            $invoices->isNotEmpty() ? "/admin/advertising-invoices?advertiser_id={$advertiser->id}" : "/admin/advertising-invoices/create?advertiser_id={$advertiser->id}".($firstCampaign ? "&campaign_id={$firstCampaign['id']}" : ''),
            $billingState === 'complete' ? null : ($invoices->isEmpty() ? 'Create an invoice using this advertiser and campaign details' : 'Review this advertiser’s outstanding billing'),
            $billingState === 'complete' ? null : ($invoices->isEmpty() ? 'Create Invoice' : 'Open Billing')
        );

        $launchState = ! $hasCampaign ? 'not_started' : ($hasActiveCampaign ? 'complete' : ($hasPausedCampaign ? 'waiting' : ($hasReadyCampaign ? 'current' : ($hasCampaignAttention ? 'attention' : 'waiting'))));
        $stages[] = $this->stage(
            'launch', 'Launch Readiness', $launchState,
            $hasActiveCampaign ? 'At least one campaign is live.' : ($hasPausedCampaign ? 'A campaign is paused and currently out of delivery.' : ($hasReadyCampaign ? 'A campaign passes launch readiness checks.' : 'Campaign readiness checks still need attention.')),
            $hasCampaign ? $this->firstCampaignHref($campaigns) : null,
            $hasActiveCampaign ? null : ($hasPausedCampaign ? 'Resume the paused campaign when delivery should continue' : ($hasReadyCampaign ? 'Schedule or activate the ready campaign' : 'Resolve campaign launch blockers')),
            $hasActiveCampaign ? null : ($hasPausedCampaign ? 'Open Campaign' : 'Check Readiness')
        );

        $performanceState = $hasActiveCampaign ? 'current' : ($hasPausedCampaign ? 'waiting' : ($allCampaignsCompleted ? 'complete' : 'not_started'));
        $stages[] = $this->stage(
            'performance', 'Live / Performance', $performanceState,
            $hasActiveCampaign ? 'Campaign performance can now be monitored.' : ($hasPausedCampaign ? 'Performance is retained while delivery is paused.' : ($allCampaignsCompleted ? 'Campaign activity is complete and ready for reporting.' : 'Performance begins after a campaign launches.')),
            '/admin/analytics/campaigns',
            $hasActiveCampaign ? 'Monitor campaign performance' : ($hasPausedCampaign ? 'Review paused campaign performance' : null),
            ($hasActiveCampaign || $hasPausedCampaign) ? 'View Analytics' : null
        );

        return $stages;
    }

    public function launchReadiness(AdvertisingCampaign $campaign, $campaignInvoices = null): array
    {
        $campaign->loadMissing(['advertiser', 'placements', 'creatives.placements']);
        $creatives = $campaign->creatives;
        $campaignInvoices ??= AdvertisingInvoice::query()
            ->where('advertising_campaign_id', $campaign->id)
            ->get();

        $checks = [];
        $checks[] = $this->check('advertiser_active', 'Advertiser is active', $campaign->advertiser?->status === 'active', true);
        $checks[] = $this->check('campaign_approved', 'Campaign is approved', in_array($campaign->status, ['approved', 'scheduled', 'active', 'paused', 'completed'], true), true);
        $checks[] = $this->check('dates_valid', 'Campaign dates are valid', $this->datesValid($campaign), true);
        $checks[] = $this->check('placements', 'At least one active placement is assigned', $campaign->placements->isNotEmpty() && $campaign->placements->every(fn ($placement) => (bool) $placement->is_active), true);
        $checks[] = $this->check('creatives', 'All creatives are approved', $creatives->isNotEmpty() && $creatives->every(fn ($creative) => $creative->status === 'approved'), true);
        $checks[] = $this->check('creative_files', 'All creative media files exist', $creatives->isNotEmpty() && $creatives->every(fn ($creative) => filled($creative->media_path) && Storage::disk('public')->exists($creative->media_path)), true);
        $checks[] = $this->check('destination_urls', 'All creatives have destination URLs', $creatives->isNotEmpty() && $creatives->every(fn ($creative) => filled($creative->destination_url)), true);
        $checks[] = $this->check('placement_compatibility', 'Creative dimensions match assigned placements', $creatives->isNotEmpty() && $creatives->every(fn ($creative) => $creative->is_placement_compatible), true);
        $checks[] = $this->check(
            'placement_creative_coverage',
            'Every campaign placement has an approved creative assigned',
            $campaign->placements->isNotEmpty() && $campaign->placements->every(
                fn ($placement) => $creatives->contains(
                    fn ($creative) => $creative->status === 'approved'
                        && $creative->placements->contains($placement->id)
                )
            ),
            true
        );
        $checks[] = $this->check('billing', 'Billing record exists', $campaignInvoices->isNotEmpty(), false);

        $blocking = collect($checks)->where('required', true)->where('passed', false)->count();

        return [
            'ready' => $blocking === 0,
            'blocking_count' => $blocking,
            'checks' => $checks,
        ];
    }

    private function campaignSummary(AdvertisingCampaign $campaign, $advertiserInvoices): array
    {
        $creatives = $campaign->creatives;
        $campaignInvoices = $advertiserInvoices->where('advertising_campaign_id', $campaign->id);
        $readiness = $this->launchReadiness($campaign, $campaignInvoices);

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'public_code' => $campaign->public_code,
            'status' => $campaign->status,
            'starts_at' => optional($campaign->starts_at)->toIso8601String(),
            'ends_at' => optional($campaign->ends_at)->toIso8601String(),
            'placements_count' => $campaign->placements->count(),
            'creative_counts' => [
                'total' => $creatives->count(),
                'approved' => $creatives->where('status', 'approved')->count(),
                'submitted' => $creatives->where('status', 'submitted')->count(),
                'rejected' => $creatives->where('status', 'rejected')->count(),
            ],
            'invoice_count' => $campaignInvoices->count(),
            'balance_cents' => (int) $campaignInvoices->sum('balance_cents'),
            'readiness' => $readiness,
            'href' => "/admin/ad-campaigns/{$campaign->id}",
            'creatives_href' => "/admin/ad-campaigns/{$campaign->id}/creatives",
        ];
    }

    private function datesValid(AdvertisingCampaign $campaign): bool
    {
        if ($campaign->starts_at && $campaign->ends_at && $campaign->ends_at->lt($campaign->starts_at)) {
            return false;
        }

        if ($campaign->ends_at && $campaign->ends_at->isPast() && ! in_array($campaign->status, ['completed', 'canceled'], true)) {
            return false;
        }

        return true;
    }

    private function firstCampaignHref(array $campaigns, ?string $target = null): ?string
    {
        $campaign = collect($campaigns)->first();
        if (! $campaign) return null;
        return $target === 'creatives' ? $campaign['creatives_href'] : $campaign['href'];
    }

    private function check(string $key, string $label, bool $passed, bool $required): array
    {
        return compact('key', 'label', 'passed', 'required');
    }

    private function stage(string $key, string $label, string $state, string $description, ?string $href = null, ?string $nextActionTitle = null, ?string $actionLabel = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'state' => $state,
            'description' => $description,
            'href' => $href,
            'next_action_title' => $nextActionTitle,
            'next_action_description' => $description,
            'action_label' => $actionLabel,
        ];
    }
}
