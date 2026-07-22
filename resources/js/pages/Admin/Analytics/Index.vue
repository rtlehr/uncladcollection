<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, Download, Eye, ShoppingCart, Users } from '@lucide/vue';
import { reactive } from 'vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AnalyticsMetrics, RevenueTrendPoint } from '@/types/analytics';

const props = defineProps<{
    filters: { period: string; start_date: string; end_date: string };
    metrics: AnalyticsMetrics;
    revenueTrend: RevenueTrendPoint[];
}>();

const filters = reactive({ ...props.filters });
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
const change = (key: string) => props.metrics[key]?.change_percent;
const applyFilters = () => router.get('/admin/analytics', filters, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Marketplace Intelligence" />
    <div class="space-y-8 p-6">
        <PageHeader title="Marketplace Intelligence" description="Measure marketplace performance using consistent reporting periods and comparison metrics." />

        <form class="grid gap-4 rounded-xl border bg-background p-4 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end" @submit.prevent="applyFilters">
            <div class="grid gap-2">
                <Label for="period">Reporting period</Label>
                <select id="period" v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option>
                </select>
            </div>
            <div class="grid gap-2"><Label for="start_date">Start date</Label><Input id="start_date" v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <div class="grid gap-2"><Label for="end_date">End date</Label><Input id="end_date" v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <Button type="submit">Apply</Button>
        </form>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Revenue" :value="money(metrics.revenue_cents.value)" :description="change('revenue_cents') === null ? 'No prior-period baseline' : `${change('revenue_cents')}% vs prior period`" emphasized><template #icon><BarChart3 class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Paid Orders" :value="metrics.paid_orders.value.toLocaleString()" :description="`${change('paid_orders') ?? 0}% vs prior period`"><template #icon><ShoppingCart class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Asset Views" :value="metrics.asset_views.value.toLocaleString()"><template #icon><Eye class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Downloads" :value="metrics.downloads.value.toLocaleString()"><template #icon><Download class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Average Order Value" :value="money(metrics.average_order_value_cents.value)" />
            <MetricCard label="Purchase Conversion" :value="`${metrics.purchase_conversion_percent.value}%`" />
            <MetricCard label="New Users" :value="metrics.new_users.value.toLocaleString()"><template #icon><Users class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Published Assets" :value="metrics.published_assets.value.toLocaleString()" />
        </section>

        <ShowSection title="Revenue trend data" description="The shared daily dataset that future chart components and exports will use.">
            <div v-if="revenueTrend.length" class="grid gap-2">
                <div v-for="point in revenueTrend" :key="point.date" class="grid grid-cols-3 gap-4 rounded-lg border px-4 py-3 text-sm">
                    <span>{{ point.date }}</span><span>{{ point.orders_count }} orders</span><span class="text-right font-medium">{{ money(point.revenue_cents) }}</span>
                </div>
            </div>
            <p v-else class="py-8 text-center text-sm text-muted-foreground">No paid-order activity was recorded for this period.</p>
        </ShowSection>
    </div>
</template>
