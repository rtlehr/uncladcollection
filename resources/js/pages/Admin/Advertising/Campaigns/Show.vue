<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import CampaignProgressTimeline from '@/components/Advertising/CampaignProgressTimeline.vue';
import WorkflowContextBanner from '@/components/Advertising/WorkflowContextBanner.vue';
import WorkflowNextStepCard from '@/components/Advertising/WorkflowNextStepCard.vue';
import { Button } from '@/components/ui/button';
import { appConfirm, appPrompt } from '@/lib/appDialog';

const props = defineProps<{
    campaign: any;
    workflowContext?: any;
    nextStep?: any;
    launchReadiness?: any;
    progressTimeline?: any[];
    rotationStatus?: any[];
    workflowHistory?: any[];
}>();

const page = usePage();

const submit = () => router.post(`/admin/ad-campaigns/${props.campaign.id}/submit`, {}, { preserveScroll: true });
const decide = async (decision: string) => router.post(
    `/admin/ad-campaigns/${props.campaign.id}/decision`,
    { decision, rejection_reason: decision === 'reject' ? (await appPrompt('Enter the reason this campaign is being rejected.', { title: 'Reject campaign', confirmLabel: 'Reject Campaign', destructive: true, placeholder: 'Rejection reason' })) || 'Rejected' : '' },
    { preserveScroll: true },
);

const lifecycle = async (action: 'pause' | 'resume' | 'complete') => {
    const messages = {
        pause: 'Pause this campaign? It will immediately stop being eligible for public ad delivery.',
        resume: 'Resume this campaign and return it to public ad delivery?',
        complete: 'Mark this campaign complete? It will stop delivering and the campaign will move to final reporting.',
    };

    if (!(await appConfirm(messages[action], { title: action === 'complete' ? 'Complete campaign?' : action === 'pause' ? 'Pause campaign?' : 'Resume campaign?', confirmLabel: action === 'complete' ? 'Mark Complete' : action === 'pause' ? 'Pause Campaign' : 'Resume Campaign', destructive: action !== 'resume' }))) return;

    router.post(`/admin/ad-campaigns/${props.campaign.id}/${action}`, {}, { preserveScroll: true });
};

const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : '—';
</script>

<template>
    <Head :title="campaign.name" />
    <div class="space-y-6 p-6">
        <div class="flex flex-wrap justify-between gap-3">
            <PageHeader :title="campaign.name" :description="`${campaign.public_code} · ${campaign.advertiser.name}`" />
            <div class="flex flex-wrap gap-2">
                <Link :href="`/admin/advertisers/${campaign.advertiser.id}`"><Button variant="outline">Client Workspace</Button></Link>
                <Link :href="`/admin/advertising-invoices/create?campaign_id=${campaign.id}&advertiser_id=${campaign.advertiser.id}`"><Button variant="outline">Create Invoice</Button></Link>
                <Link :href="`/admin/ad-campaigns/${campaign.id}/creatives`"><Button>Manage Creatives</Button></Link>
                <Link :href="`/admin/ad-campaigns/${campaign.id}/edit`"><Button variant="outline">Edit</Button></Link>
            </div>
        </div>

        <WorkflowContextBanner :context="workflowContext" label="Campaign workflow" />

        <div v-if="page.props.errors?.campaign" class="rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive">
            {{ page.props.errors.campaign }}
        </div>

        <WorkflowNextStepCard :step="nextStep" />
        <CampaignProgressTimeline :stages="progressTimeline" />

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">Status</p><p class="text-xl font-semibold capitalize">{{ campaign.status }}</p></div>
            <div class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">Placements</p><p class="text-xl font-semibold">{{ campaign.placements.length }}</p></div>
            <div class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">Creatives</p><p class="text-xl font-semibold">{{ campaign.creatives.length }}</p></div>
        </div>

        <div class="rounded-xl border p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Campaign operations</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Control the live campaign lifecycle without changing its approved setup.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="campaign.status === 'active'" variant="outline" @click="lifecycle('pause')">Pause Delivery</Button>
                    <Button v-if="campaign.status === 'paused'" @click="lifecycle('resume')">Resume Delivery</Button>
                    <Button v-if="['active', 'paused'].includes(campaign.status)" variant="outline" @click="lifecycle('complete')">Mark Complete</Button>
                </div>
            </div>
            <p v-if="campaign.status === 'paused'" class="mt-3 rounded-lg border border-amber-500/40 bg-amber-500/10 p-3 text-sm">
                This campaign is paused and is not currently eligible for public ad delivery.
            </p>
            <p v-if="campaign.status === 'completed'" class="mt-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 text-sm">
                This campaign is complete and has been removed from public ad delivery.
            </p>
        </div>

        <div class="rounded-xl border p-5">
            <h2 class="font-semibold">Approval workflow</h2>
            <p v-if="campaign.rejection_reason" class="mt-2 text-sm text-destructive">{{ campaign.rejection_reason }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <Button v-if="['draft','rejected'].includes(campaign.status)" @click="submit">Submit for approval</Button>
                <Button v-if="campaign.status === 'submitted'" @click="decide('approve')">Approve</Button>
                <Button v-if="campaign.status === 'submitted'" variant="destructive" @click="decide('reject')">Reject</Button>
            </div>
        </div>

        <div class="rounded-xl border p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Assigned placements</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Rotation weight controls this campaign's relative share when multiple eligible campaigns compete for the same placement.</p>
                </div>
                <Link :href="`/admin/ad-campaigns/${campaign.id}/edit`" class="text-sm underline">Edit weights</Link>
            </div>
            <ul class="mt-4 space-y-3">
                <li v-for="x in campaign.placements" :key="x.id ?? x.code ?? x.name" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
                    <div>
                        <p class="font-medium">{{ x.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ x.location }} / {{ x.format }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">Rotation weight</p>
                        <p class="text-lg font-semibold">{{ x.pivot?.priority ?? 50 }}</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="rounded-xl border p-5">
            <div>
                <h2 class="font-semibold">Rotation status</h2>
                <p class="mt-1 text-sm text-muted-foreground">Compares intended rotation weight with recorded ad impressions from the last 30 days. A small difference is normal, especially with low traffic.</p>
            </div>

            <div v-if="rotationStatus?.length" class="mt-4 space-y-5">
                <div v-for="placement in rotationStatus" :key="placement.placement_id" class="overflow-hidden rounded-lg border">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/20 px-4 py-3">
                        <div>
                            <p class="font-medium">{{ placement.placement_name }}</p>
                            <p class="text-xs text-muted-foreground">{{ placement.placement_code }}</p>
                        </div>
                        <p class="text-sm text-muted-foreground">{{ placement.total_impressions.toLocaleString() }} impressions / {{ placement.days }} days</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-muted-foreground">
                                <tr class="border-b">
                                    <th class="px-4 py-2 font-medium">Campaign</th>
                                    <th class="px-4 py-2 font-medium">Weight</th>
                                    <th class="px-4 py-2 font-medium">Intended</th>
                                    <th class="px-4 py-2 font-medium">Impressions</th>
                                    <th class="px-4 py-2 font-medium">Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in placement.campaigns" :key="row.campaign_id" class="border-b last:border-0" :class="row.is_current ? 'bg-primary/5' : ''">
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ row.campaign_name }} <span v-if="row.is_current" class="text-xs text-muted-foreground">(this campaign)</span></p>
                                        <p class="text-xs text-muted-foreground">{{ row.advertiser_name }} · {{ row.status }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ row.weight }}</td>
                                    <td class="px-4 py-3">{{ row.intended_share }}%</td>
                                    <td class="px-4 py-3">{{ row.impressions.toLocaleString() }}</td>
                                    <td class="px-4 py-3">{{ row.actual_share }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">Assign at least one placement to see rotation status.</p>
        </div>

        <div class="rounded-xl border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Workflow history</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Status changes recorded after this workflow-history feature was installed.</p>
                </div>
            </div>
            <div v-if="workflowHistory?.length" class="mt-4 divide-y rounded-lg border">
                <div v-for="entry in workflowHistory" :key="entry.id" class="grid gap-1 p-3 md:grid-cols-[180px_1fr_auto] md:items-center">
                    <p class="text-xs text-muted-foreground">{{ formatDate(entry.created_at) }}</p>
                    <div>
                        <p class="text-sm font-medium">{{ entry.description || 'Campaign status changed.' }}</p>
                        <p class="text-xs text-muted-foreground">
                            <span v-if="entry.old_value">{{ entry.old_value }}</span>
                            <span v-if="entry.old_value && entry.new_value"> → </span>
                            <span v-if="entry.new_value">{{ entry.new_value }}</span>
                        </p>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ entry.user_name }}</p>
                </div>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">No workflow history has been recorded yet. New status transitions will appear here.</p>
        </div>

        <div class="rounded-xl border p-5">
            <div class="flex items-center justify-between"><h2 class="font-semibold">Creative library</h2><Link :href="`/admin/ad-campaigns/${campaign.id}/creatives`" class="text-sm underline">View all</Link></div>
            <div v-if="campaign.creatives.length" class="mt-4 grid gap-3 md:grid-cols-3">
                <div v-for="x in campaign.creatives.slice(0,3)" :key="x.id" class="overflow-hidden rounded-lg border">
                    <img v-if="x.creative_type === 'image'" :src="x.media_url" :alt="x.alt_text || x.name" class="aspect-video w-full object-contain">
                    <div class="p-3 text-sm"><p class="font-medium">{{ x.name }}</p><p class="capitalize text-muted-foreground">{{ x.status }}</p></div>
                </div>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">No creatives have been added.</p>
        </div>
    </div>
</template>
