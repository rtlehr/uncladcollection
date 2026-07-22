<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity, BadgeDollarSign, Download, Eye, KeyRound, PackageCheck, ShoppingCart, TrendingUp, Users } from '@lucide/vue';
import { computed, reactive } from 'vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import TrendChart from '@/components/Analytics/TrendChart.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExecutiveDashboard } from '@/types/analytics';

const props = defineProps<{
    filters: { period: string; start_date: string; end_date: string };
    dashboard: ExecutiveDashboard;
}>();

const filters = reactive({ ...props.filters });
const metrics = computed(() => props.dashboard.summary);
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
const wholeMoney = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(cents / 100);
const dateTime = (value: string | null) => value ? new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '';
const trendText = (key: string) => {
    const change = metrics.value[key]?.change_percent;
    if (change === null || change === undefined) return 'No prior-period baseline';
    return `${change > 0 ? '+' : ''}${change}% vs prior period`;
};
const trendTone = (key: string) => {
    const change = metrics.value[key]?.change_percent;
    if (change === null || change === 0) return 'neutral';
    return change > 0 ? 'positive' : 'negative';
};
const applyFilters = () => router.get('/admin/analytics', filters, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Marketplace Intelligence" />
    <div class="space-y-8 p-6">
        <PageHeader title="Marketplace Intelligence" description="A clear executive view of revenue, customer movement, marketplace health, and content performance.">
            <template #actions><div class="flex flex-wrap gap-2"><Button variant="outline" as-child><Link href="/admin/analytics/assets">Asset performance</Link></Button><Button variant="outline" as-child><Link href="/admin/analytics/customers">Customer analytics</Link></Button><Button variant="outline" as-child><Link href="/admin/analytics/blog">Content analytics</Link></Button><Button variant="outline" as-child><Link href="/admin/analytics/campaigns">Campaign analytics</Link></Button><Button variant="outline" as-child><Link href="/admin/analytics/financial">Financial reporting</Link></Button></div></template>
        </PageHeader>

        <form class="grid gap-4 rounded-xl border bg-background p-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end" @submit.prevent="applyFilters">
            <div class="grid gap-2">
                <Label for="period">Reporting period</Label>
                <select id="period" v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option>
                </select>
            </div>
            <div class="grid gap-2"><Label for="start_date">Start date</Label><Input id="start_date" v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <div class="grid gap-2"><Label for="end_date">End date</Label><Input id="end_date" v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <Button type="submit">Apply period</Button>
        </form>

        <section class="grid gap-4 xl:grid-cols-4">
            <MetricCard label="Revenue" :value="money(metrics.revenue_cents.value)" :trend="trendText('revenue_cents')" :trend-tone="trendTone('revenue_cents')" emphasized size="lg" class="xl:col-span-2"><template #icon><BadgeDollarSign class="h-6 w-6" /></template></MetricCard>
            <MetricCard label="Paid orders" :value="metrics.paid_orders.value.toLocaleString()" :trend="trendText('paid_orders')" :trend-tone="trendTone('paid_orders')" size="lg"><template #icon><ShoppingCart class="h-6 w-6" /></template></MetricCard>
            <MetricCard label="Average order value" :value="money(metrics.average_order_value_cents.value)" :trend="trendText('average_order_value_cents')" :trend-tone="trendTone('average_order_value_cents')" size="lg"><template #icon><TrendingUp class="h-6 w-6" /></template></MetricCard>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Purchase conversion" :value="`${metrics.purchase_conversion_percent.value}%`" :trend="trendText('purchase_conversion_percent')" :trend-tone="trendTone('purchase_conversion_percent')"><template #icon><Activity class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Asset views" :value="metrics.asset_views.value.toLocaleString()" :trend="trendText('asset_views')" :trend-tone="trendTone('asset_views')"><template #icon><Eye class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Downloads" :value="metrics.downloads.value.toLocaleString()" :trend="trendText('downloads')" :trend-tone="trendTone('downloads')"><template #icon><Download class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="New users" :value="metrics.new_users.value.toLocaleString()" :trend="trendText('new_users')" :trend-tone="trendTone('new_users')"><template #icon><Users class="h-5 w-5" /></template></MetricCard>
        </section>

        <div class="grid gap-6 2xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <ShowSection title="Revenue trend" description="Daily paid revenue across the selected reporting period."><TrendChart :points="dashboard.revenue_trend" /></ShowSection>
            <ShowSection title="Marketplace health" description="Current catalog readiness and transaction quality.">
                <dl class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
                    <div class="rounded-lg border p-4"><dt class="text-sm text-muted-foreground">Published assets</dt><dd class="mt-1 text-2xl font-semibold">{{ dashboard.marketplace_health.published_assets }}</dd></div>
                    <div class="rounded-lg border p-4"><dt class="text-sm text-muted-foreground">Active offerings</dt><dd class="mt-1 text-2xl font-semibold">{{ dashboard.marketplace_health.active_offerings }}</dd></div>
                    <div class="rounded-lg border p-4"><dt class="text-sm text-muted-foreground">Failed order rate</dt><dd class="mt-1 text-2xl font-semibold">{{ dashboard.marketplace_health.failed_order_rate_percent }}%</dd></div>
                    <div class="rounded-lg border p-4"><dt class="text-sm text-muted-foreground">Refund rate</dt><dd class="mt-1 text-2xl font-semibold">{{ dashboard.marketplace_health.refund_rate_percent }}%</dd></div>
                </dl>
            </ShowSection>
        </div>

        <ShowSection title="Customer conversion funnel" description="How customers move from browsing to a completed purchase.">
            <div class="grid gap-4 md:grid-cols-4">
                <div v-for="(stage, index) in dashboard.conversion_funnel" :key="stage.key" class="relative rounded-xl border bg-card p-5">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Step {{ index + 1 }}</div><div class="mt-2 text-sm font-medium">{{ stage.label }}</div><div class="mt-2 text-3xl font-semibold">{{ stage.value.toLocaleString() }}</div><div class="mt-2 text-xs text-muted-foreground">{{ index === 0 ? 'Funnel starting point' : `${stage.conversion_percent}% from prior step` }}</div>
                </div>
            </div>
        </ShowSection>

        <div class="grid gap-6 xl:grid-cols-2">
            <ShowSection title="Revenue by license" description="Which usage rights are generating paid revenue."><DistributionBars :items="dashboard.license_mix" /></ShowSection>
            <ShowSection title="Revenue by media type" description="Paid revenue split across images, video, and other asset types."><DistributionBars :items="dashboard.media_mix" /></ShowSection>
        </div>

        <div class="grid gap-6 2xl:grid-cols-2">
            <ShowSection title="Top-performing assets" description="Assets ranked by paid revenue in this period.">
                <div v-if="dashboard.top_assets.length" class="divide-y rounded-lg border">
                    <div v-for="(asset, index) in dashboard.top_assets" :key="asset.asset_id" class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-4 p-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-semibold">{{ index + 1 }}</span>
                        <div class="min-w-0"><Link :href="`/admin/assets/${asset.asset_id}`" class="font-medium hover:underline">{{ asset.title }}</Link><p class="text-sm text-muted-foreground">{{ asset.units }} units sold</p></div>
                        <span class="font-semibold">{{ wholeMoney(asset.revenue_cents) }}</span>
                    </div>
                </div><p v-else class="py-10 text-center text-sm text-muted-foreground">No asset sales were recorded for this period.</p>
            </ShowSection>

            <ShowSection title="Recent marketplace activity" description="The newest orders and customer downloads in this period.">
                <div v-if="dashboard.recent_activity.length" class="divide-y rounded-lg border">
                    <Link v-for="activity in dashboard.recent_activity" :key="`${activity.type}-${activity.occurred_at}-${activity.description}`" :href="activity.href" class="flex items-start gap-3 p-4 hover:bg-muted/30">
                        <div class="mt-0.5 rounded-lg bg-muted p-2"><PackageCheck v-if="activity.type === 'order'" class="h-4 w-4" /><Download v-else class="h-4 w-4" /></div>
                        <div class="min-w-0 flex-1"><div class="flex justify-between gap-3"><p class="font-medium">{{ activity.title }}</p><span v-if="activity.amount_cents !== null" class="font-semibold">{{ money(activity.amount_cents) }}</span></div><p class="truncate text-sm text-muted-foreground">{{ activity.description }}</p><p class="mt-1 text-xs text-muted-foreground">{{ dateTime(activity.occurred_at) }}</p></div>
                    </Link>
                </div><p v-else class="py-10 text-center text-sm text-muted-foreground">No recent activity was recorded for this period.</p>
            </ShowSection>
        </div>
    </div>
</template>
