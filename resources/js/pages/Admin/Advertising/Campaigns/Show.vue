<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import WorkflowContextBanner from '@/components/Advertising/WorkflowContextBanner.vue';
import WorkflowNextStepCard from '@/components/Advertising/WorkflowNextStepCard.vue';
import { Button } from '@/components/ui/button';

const p=defineProps<{campaign:any; workflowContext?:any; nextStep?:any; launchReadiness?:any}>();
const page = usePage();
const submit=()=>router.post(`/admin/ad-campaigns/${p.campaign.id}/submit`,{}, {preserveScroll:true});
const decide=(decision:string)=>router.post(`/admin/ad-campaigns/${p.campaign.id}/decision`,{decision,rejection_reason:decision==='reject'?prompt('Reason for rejection')||'Rejected':''},{preserveScroll:true});
</script>

<template>
    <Head :title="campaign.name"/>
    <div class="space-y-6 p-6">
        <div class="flex flex-wrap justify-between gap-3">
            <PageHeader :title="campaign.name" :description="`${campaign.public_code} · ${campaign.advertiser.name}`"/>
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

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">Status</p><p class="text-xl font-semibold capitalize">{{campaign.status}}</p></div>
            <div class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">Placements</p><p class="text-xl font-semibold">{{campaign.placements.length}}</p></div>
            <div class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">Creatives</p><p class="text-xl font-semibold">{{campaign.creatives.length}}</p></div>
        </div>

        <div class="rounded-xl border p-5">
            <h2 class="font-semibold">Approval workflow</h2>
            <p v-if="campaign.rejection_reason" class="mt-2 text-sm text-destructive">{{campaign.rejection_reason}}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <Button v-if="['draft','rejected'].includes(campaign.status)" @click="submit">Submit for approval</Button>
                <Button v-if="campaign.status==='submitted'" @click="decide('approve')">Approve</Button>
                <Button v-if="campaign.status==='submitted'" variant="destructive" @click="decide('reject')">Reject</Button>
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
            <div class="flex items-center justify-between"><h2 class="font-semibold">Creative library</h2><Link :href="`/admin/ad-campaigns/${campaign.id}/creatives`" class="text-sm underline">View all</Link></div>
            <div v-if="campaign.creatives.length" class="mt-4 grid gap-3 md:grid-cols-3">
                <div v-for="x in campaign.creatives.slice(0,3)" :key="x.id" class="overflow-hidden rounded-lg border">
                    <img v-if="x.creative_type==='image'" :src="x.media_url" :alt="x.alt_text||x.name" class="aspect-video w-full object-contain">
                    <div class="p-3 text-sm"><p class="font-medium">{{x.name}}</p><p class="capitalize text-muted-foreground">{{x.status}}</p></div>
                </div>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">No creatives have been added.</p>
        </div>
    </div>
</template>
