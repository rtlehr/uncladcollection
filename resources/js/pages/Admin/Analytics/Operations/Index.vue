<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, BadgeDollarSign, CheckCircle2, Clock3, ShoppingCart } from '@lucide/vue';
import { reactive } from 'vue';
import AnalyticsEmptyState from '@/components/Analytics/AnalyticsEmptyState.vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ filters: any; report: any; fulfillmentStatuses: Array<{value:string;label:string}> }>();
const filters = reactive({ ...props.filters });
const apply = () => router.get('/admin/analytics/operations', filters, { preserveState: true, replace: true });
const exportUrl = () => '/admin/analytics/operations/export?' + new URLSearchParams(Object.entries(filters).filter(([, value]) => value !== '' && value != null) as [string, string][]).toString();
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format((cents || 0) / 100);
</script>

<template>
    <Head title="Marketplace Operations and Fulfillment Analytics" />
    <div class="analytics-report-page space-y-8 p-6">
        <AnalyticsHeader title="Marketplace Operations and Fulfillment Analytics" description="Monitor payment outcomes, fulfillment speed, refunds, stalled orders, and operational bottlenecks.">
            <template #actions><Button variant="outline" as-child><a :href="exportUrl()">Export CSV</a></Button></template>
        </AnalyticsHeader>

        <AnalyticsFilterPanel content-class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end" @submit="apply">
            <div class="grid gap-2"><Label>Search</Label><Input v-model="filters.search" placeholder="Order number or customer..." /></div>
            <div class="grid gap-2"><Label>Payment status</Label><select v-model="filters.status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">All statuses</option><option value="pending">Pending</option><option value="paid">Paid</option><option value="failed">Failed</option><option value="canceled">Canceled</option><option value="refunded">Refunded</option><option value="partially_refunded">Partially refunded</option></select></div>
            <div class="grid gap-2"><Label>Fulfillment status</Label><select v-model="filters.fulfillment_status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">All fulfillment</option><option v-for="status in fulfillmentStatuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></div>
            <div class="grid gap-2"><Label>Period</Label><select v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option></select></div>
            <Button type="submit">Apply filters</Button>
        </AnalyticsFilterPanel>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-7">
            <MetricCard label="Orders" :value="report.summary.orders"><template #icon><ShoppingCart class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Paid orders" :value="report.summary.paid_orders"><template #icon><CheckCircle2 class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Paid revenue" :value="money(report.summary.revenue_cents)"><template #icon><BadgeDollarSign class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Payment success" :value="`${report.summary.payment_success_percent}%`"><template #icon><CheckCircle2 class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Failed" :value="report.summary.failed_orders"><template #icon><AlertTriangle class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Needs attention" :value="report.summary.needs_attention"><template #icon><AlertTriangle class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Avg. fulfillment" :value="`${report.summary.average_fulfillment_hours} hrs`"><template #icon><Clock3 class="h-5 w-5" /></template></MetricCard>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <ShowSection title="Payment status" description="Order volume by payment outcome."><DistributionBars :items="report.payment_statuses" /></ShowSection>
            <ShowSection title="Fulfillment status" description="Current operational stage of measured orders."><DistributionBars :items="report.fulfillment_statuses" /></ShowSection>
            <ShowSection title="Payment providers" description="Order volume by payment source."><DistributionBars :items="report.providers" /></ShowSection>
        </div>

        <ShowSection title="Orders requiring attention" description="Failed, refunded, or paid orders still unfulfilled after 24 hours.">
            <div v-if="report.attention.length" class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm"><thead class="bg-muted/40 text-left"><tr><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Payment</th><th class="p-3">Fulfillment</th><th class="p-3">Age</th><th class="p-3">Total</th></tr></thead><tbody>
                    <tr v-for="row in report.attention" :key="row.order_id" class="border-t"><td class="p-3 font-medium"><Link :href="`/admin/analytics/operations/${row.order_id}?period=${filters.period}`" class="hover:underline">{{ row.order_number }}</Link></td><td class="p-3">{{ row.customer_email || 'Guest' }}</td><td class="p-3 capitalize">{{ row.status.replaceAll('_', ' ') }}</td><td class="p-3 capitalize">{{ row.fulfillment_status.replaceAll('_', ' ') }}</td><td class="p-3">{{ row.age_hours }} hrs</td><td class="p-3">{{ money(row.total_cents) }}</td></tr>
                </tbody></table>
            </div>
            <AnalyticsEmptyState v-else title="No operational exceptions" description="No orders currently match the manual-attention rules." />
        </ShowSection>

        <ShowSection title="Order operations" description="Payment and fulfillment state for all matching orders.">
            <div v-if="report.orders.length" class="overflow-x-auto rounded-lg border"><table class="w-full text-sm"><thead class="bg-muted/40 text-left"><tr><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Payment</th><th class="p-3">Fulfillment</th><th class="p-3">Provider</th><th class="p-3">Fulfillment time</th><th class="p-3">Total</th></tr></thead><tbody><tr v-for="row in report.orders" :key="row.order_id" class="border-t"><td class="p-3 font-medium"><Link :href="`/admin/analytics/operations/${row.order_id}?period=${filters.period}`" class="hover:underline">{{ row.order_number }}</Link></td><td class="p-3">{{ row.customer_email || 'Guest' }}</td><td class="p-3 capitalize">{{ row.status.replaceAll('_', ' ') }}</td><td class="p-3 capitalize">{{ row.fulfillment_status.replaceAll('_', ' ') }}</td><td class="p-3 capitalize">{{ row.payment_provider }}</td><td class="p-3">{{ row.fulfillment_hours == null ? '—' : `${row.fulfillment_hours} hrs` }}</td><td class="p-3">{{ money(row.total_cents) }}</td></tr></tbody></table></div>
            <AnalyticsEmptyState v-else title="No orders found" description="No marketplace orders matched the selected filters." />
        </ShowSection>
    </div>
</template>
