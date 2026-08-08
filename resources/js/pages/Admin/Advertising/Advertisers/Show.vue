<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    Check,
    CheckCircle2,
    Circle,
    Clock3,
    ExternalLink,
    FileWarning,
    Pencil,
    Rocket,
} from '@lucide/vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

interface WorkflowStage {
    key: string;
    label: string;
    state: 'complete' | 'current' | 'attention' | 'waiting' | 'not_started';
    description: string;
    href?: string | null;
}

interface ReadinessCheck {
    key: string;
    label: string;
    passed: boolean;
    required: boolean;
}

interface CampaignSummary {
    id: number;
    name: string;
    public_code: string;
    status: string;
    starts_at?: string | null;
    ends_at?: string | null;
    placements_count: number;
    creative_counts: { total: number; approved: number; submitted: number; rejected: number };
    invoice_count: number;
    balance_cents: number;
    readiness: { ready: boolean; blocking_count: number; checks: ReadinessCheck[] };
    href: string;
    creatives_href: string;
}

interface WorkflowPayload {
    stages: WorkflowStage[];
    next_action: {
        stage: string;
        title: string;
        description: string;
        href?: string | null;
        action_label?: string | null;
    };
    campaigns: CampaignSummary[];
    stats: {
        portal_members: number;
        proposals: number;
        campaigns: number;
        active_campaigns: number;
        open_balance_cents: number;
    };
}

const props = defineProps<{ advertiser: any; workflow: WorkflowPayload }>();

const money = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format((value || 0) / 100);

const dateLabel = (value?: string | null) => {
    if (!value) return 'Not set';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
};

const stateLabel = (state: WorkflowStage['state']) => ({
    complete: 'Complete',
    current: 'Current',
    attention: 'Attention',
    waiting: 'Waiting',
    not_started: 'Not started',
}[state]);

const stateClass = (state: WorkflowStage['state']) => {
    if (state === 'complete') return 'border-emerald-500/30 bg-emerald-500/10';
    if (state === 'attention') return 'border-amber-500/40 bg-amber-500/10';
    if (state === 'current') return 'border-primary/40 bg-primary/5';
    return 'border-border bg-background';
};

const stageIconClass = (state: WorkflowStage['state']) => {
    if (state === 'complete') return 'bg-emerald-600 text-white';
    if (state === 'attention') return 'bg-amber-500 text-white';
    if (state === 'current') return 'bg-primary text-primary-foreground';
    return 'bg-muted text-muted-foreground';
};

const currentCampaign = computed(() =>
    props.workflow.campaigns.find((campaign) => campaign.status === 'active') || props.workflow.campaigns[0] || null,
);
</script>

<template>
    <Head :title="`${advertiser.name} Advertising Workflow`" />

    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <Link href="/admin/advertisers" class="mb-3 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft class="size-4" /> Back to advertisers
                </Link>
                <PageHeader
                    :title="advertiser.name"
                    description="Advertising client workspace · setup, approvals, launch readiness, billing, and campaign progress"
                />
            </div>

            <div class="flex flex-wrap gap-2">
                <Link :href="`/admin/ad-campaigns/create?advertiser_id=${advertiser.id}`">
                    <Button><Rocket class="mr-2 size-4" /> Create Campaign</Button>
                </Link>
                <Link :href="`/admin/advertisers/${advertiser.id}/edit`">
                    <Button variant="outline"><Pencil class="mr-2 size-4" /> Edit Advertiser</Button>
                </Link>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Client Status</div>
                <div class="mt-1 text-lg font-semibold capitalize">{{ advertiser.status }}</div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Portal Members</div>
                <div class="mt-1 text-lg font-semibold">{{ workflow.stats.portal_members }}</div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Campaigns</div>
                <div class="mt-1 text-lg font-semibold">{{ workflow.stats.active_campaigns }} active / {{ workflow.stats.campaigns }} total</div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Proposals</div>
                <div class="mt-1 text-lg font-semibold">{{ workflow.stats.proposals }}</div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Open Balance</div>
                <div class="mt-1 text-lg font-semibold">{{ money(workflow.stats.open_balance_cents) }}</div>
            </div>
        </div>

        <section class="rounded-xl border border-primary/30 bg-primary/5 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-sm font-medium text-primary">Next Action</div>
                    <h2 class="mt-1 text-xl font-semibold">{{ workflow.next_action.title }}</h2>
                    <p class="mt-1 max-w-3xl text-sm text-muted-foreground">{{ workflow.next_action.description }}</p>
                </div>
                <Link v-if="workflow.next_action.href" :href="workflow.next_action.href">
                    <Button>
                        {{ workflow.next_action.action_label || 'Open' }}
                        <ArrowRight class="ml-2 size-4" />
                    </Button>
                </Link>
            </div>
        </section>

        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold">Client Advertising Workflow</h2>
                <p class="text-sm text-muted-foreground">These stages are calculated from the advertiser, proposal, campaign, creative, billing, and media records you already use.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <component
                    :is="stage.href ? Link : 'div'"
                    v-for="(stage, index) in workflow.stages"
                    :key="stage.key"
                    v-bind="stage.href ? { href: stage.href } : {}"
                    class="group rounded-xl border p-4 transition-colors"
                    :class="[stateClass(stage.state), stage.href ? 'hover:border-primary/50' : '']"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold" :class="stageIconClass(stage.state)">
                            <Check v-if="stage.state === 'complete'" class="size-4" />
                            <AlertTriangle v-else-if="stage.state === 'attention'" class="size-4" />
                            <Clock3 v-else-if="stage.state === 'waiting'" class="size-4" />
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium">{{ stage.label }}</h3>
                                <span class="text-xs text-muted-foreground">{{ stateLabel(stage.state) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">{{ stage.description }}</p>
                        </div>
                    </div>
                </component>
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Campaigns</h2>
                    <p class="text-sm text-muted-foreground">Each campaign gets its own launch-readiness check while remaining inside the advertiser workspace.</p>
                </div>
                <Link :href="`/admin/ad-campaigns/create?advertiser_id=${advertiser.id}`">
                    <Button variant="outline">Add Campaign</Button>
                </Link>
            </div>

            <div v-if="workflow.campaigns.length" class="space-y-4">
                <article v-for="campaign in workflow.campaigns" :key="campaign.id" class="rounded-xl border p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="campaign.href" class="text-lg font-semibold hover:underline">{{ campaign.name }}</Link>
                                <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium capitalize">{{ campaign.status }}</span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="campaign.readiness.ready ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/10 text-amber-700 dark:text-amber-300'"
                                >
                                    <CheckCircle2 v-if="campaign.readiness.ready" class="size-3.5" />
                                    <FileWarning v-else class="size-3.5" />
                                    {{ campaign.readiness.ready ? 'Launch ready' : `${campaign.readiness.blocking_count} blocker(s)` }}
                                </span>
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                {{ campaign.public_code }} · {{ dateLabel(campaign.starts_at) }} – {{ dateLabel(campaign.ends_at) }}
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Link :href="campaign.creatives_href"><Button size="sm" variant="outline">Creatives</Button></Link>
                            <Link :href="campaign.href"><Button size="sm">Open Campaign <ExternalLink class="ml-1 size-3.5" /></Button></Link>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-muted/40 p-3">
                            <div class="text-xs text-muted-foreground">Placements</div>
                            <div class="mt-1 font-medium">{{ campaign.placements_count }}</div>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <div class="text-xs text-muted-foreground">Creatives</div>
                            <div class="mt-1 font-medium">{{ campaign.creative_counts.approved }}/{{ campaign.creative_counts.total }} approved</div>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <div class="text-xs text-muted-foreground">Invoices</div>
                            <div class="mt-1 font-medium">{{ campaign.invoice_count }}</div>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <div class="text-xs text-muted-foreground">Balance</div>
                            <div class="mt-1 font-medium">{{ money(campaign.balance_cents) }}</div>
                        </div>
                    </div>

                    <details class="mt-4 rounded-lg border bg-muted/10 p-4" :open="campaign === currentCampaign && !campaign.readiness.ready">
                        <summary class="cursor-pointer font-medium">Launch Readiness Checklist</summary>
                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            <div v-for="check in campaign.readiness.checks" :key="check.key" class="flex items-start gap-2 text-sm">
                                <CheckCircle2 v-if="check.passed" class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                                <AlertTriangle v-else-if="check.required" class="mt-0.5 size-4 shrink-0 text-amber-600" />
                                <Circle v-else class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <span>
                                    {{ check.label }}
                                    <span v-if="!check.required" class="text-muted-foreground"> (informational)</span>
                                </span>
                            </div>
                        </div>
                    </details>
                </article>
            </div>

            <div v-else class="rounded-xl border border-dashed p-8 text-center">
                <Rocket class="mx-auto size-8 text-muted-foreground" />
                <h3 class="mt-3 font-medium">No campaigns yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">Create the first campaign and this workspace will begin tracking creative, approval, billing, and launch readiness automatically.</p>
                <Link :href="`/admin/ad-campaigns/create?advertiser_id=${advertiser.id}`" class="mt-4 inline-block">
                    <Button>Create Campaign</Button>
                </Link>
            </div>
        </section>
    </div>
</template>
