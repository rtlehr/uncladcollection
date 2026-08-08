<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, CheckCircle2, CirclePause, Plus, Users } from '@lucide/vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

interface WorkflowSummary {
    current_stage: string;
    current_stage_label: string;
    next_action: string;
    attention_count: number;
    health: 'good' | 'attention' | 'inactive';
    active_campaigns: number;
}

interface AdvertiserRow {
    id: number;
    name: string;
    status: string;
    contact_email?: string | null;
    billing_email?: string | null;
    campaigns_count: number;
    memberships_count: number;
    workflow: WorkflowSummary;
}

defineProps<{ advertisers: AdvertiserRow[] }>();

const statusClass = (status: string) => {
    if (status === 'active') return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    if (status === 'prospect') return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    return 'bg-muted text-muted-foreground';
};

const workflowClass = (health: WorkflowSummary['health']) => {
    if (health === 'attention') return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    if (health === 'inactive') return 'bg-muted text-muted-foreground';
    return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
};
</script>

<template>
    <Head title="Advertisers" />

    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <PageHeader
                title="Advertisers"
                description="Manage each advertising client from first setup through campaign launch and performance."
            />

            <Link href="/admin/advertisers/create">
                <Button><Plus class="mr-2 size-4" /> Add Advertiser</Button>
            </Link>
        </div>

        <div class="rounded-xl border bg-muted/20 p-4 text-sm text-muted-foreground">
            <span class="font-medium text-foreground">Client workflow:</span>
            open an advertiser to see portal setup, proposals, campaigns, creative approvals, billing, launch readiness, and the next action in one place.
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="p-3 text-left font-medium">Advertiser</th>
                            <th class="p-3 text-left font-medium">Client Status</th>
                            <th class="p-3 text-left font-medium">Current Workflow</th>
                            <th class="p-3 text-left font-medium">Campaigns</th>
                            <th class="p-3 text-left font-medium">Next Action</th>
                            <th class="p-3 text-left font-medium">Health</th>
                            <th class="p-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="advertiser in advertisers" :key="advertiser.id" class="border-t align-top">
                            <td class="p-3">
                                <Link :href="`/admin/advertisers/${advertiser.id}`" class="font-medium hover:underline">
                                    {{ advertiser.name }}
                                </Link>
                                <div class="mt-1 text-muted-foreground">
                                    {{ advertiser.contact_email || advertiser.billing_email || 'No contact email' }}
                                </div>
                            </td>

                            <td class="p-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="statusClass(advertiser.status)">
                                    {{ advertiser.status }}
                                </span>
                            </td>

                            <td class="p-3">
                                <div class="font-medium">{{ advertiser.workflow.current_stage_label }}</div>
                                <div class="mt-1 text-muted-foreground">{{ advertiser.memberships_count }} portal member(s)</div>
                            </td>

                            <td class="p-3">
                                <div class="font-medium">{{ advertiser.workflow.active_campaigns }} active</div>
                                <div class="mt-1 text-muted-foreground">{{ advertiser.campaigns_count }} total</div>
                            </td>

                            <td class="max-w-[280px] p-3 text-muted-foreground">
                                {{ advertiser.workflow.next_action }}
                            </td>

                            <td class="p-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="workflowClass(advertiser.workflow.health)">
                                    <AlertTriangle v-if="advertiser.workflow.health === 'attention'" class="size-3.5" />
                                    <CirclePause v-else-if="advertiser.workflow.health === 'inactive'" class="size-3.5" />
                                    <CheckCircle2 v-else class="size-3.5" />
                                    <template v-if="advertiser.workflow.health === 'attention'">
                                        {{ advertiser.workflow.attention_count }} item(s)
                                    </template>
                                    <template v-else-if="advertiser.workflow.health === 'inactive'">Inactive</template>
                                    <template v-else>On track</template>
                                </span>
                            </td>

                            <td class="p-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/admin/advertisers/${advertiser.id}`">
                                        <Button size="sm">Workflow <ArrowRight class="ml-1 size-4" /></Button>
                                    </Link>
                                    <Link :href="`/admin/advertisers/${advertiser.id}/edit`">
                                        <Button size="sm" variant="outline">Edit</Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="advertisers.length === 0">
                            <td colspan="7" class="p-10 text-center text-muted-foreground">
                                <Users class="mx-auto mb-3 size-8" />
                                No advertisers have been created yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
