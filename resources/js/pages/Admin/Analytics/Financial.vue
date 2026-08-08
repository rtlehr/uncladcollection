<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BadgeDollarSign, Download, ReceiptText, RotateCcw, ShieldCheck, ShoppingCart } from '@lucide/vue';
import { computed, reactive } from 'vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Report = {
    summary: Record<string, number>;
    daily: Array<{ date: string; label: string; collected_cents: number; refunded_cents: number; net_cents: number }>;
    licenses: Array<{ label: string; units: number; revenue_cents: number }>;
    assets: Array<{ label: string; units: number; revenue_cents: number }>;
    providers: Array<{ label: string; orders: number; revenue_cents: number }>;
    orders: Array<{ id: number; order_number: string; customer: string; provider: string; total_cents: number; refunded_cents: number; paid_at: string | null }>;
    reconciliation: { paid_orders_without_payment_reference: number; refund_status_without_ledger: number; failed_financial_transactions: number; is_reconciled: boolean };
};

const props = defineProps<{ filters: { period: string; start_date: string; end_date: string }; report: Report }>();
const filters = reactive({ ...props.filters });
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
const dateTime = (value: string | null) => value ? new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';
const applyFilters = () => router.get('/admin/analytics/financial', filters, { preserveState: true, replace: true });
const exportHref = computed(() => `/admin/analytics/financial/export?${new URLSearchParams(filters as Record<string, string>).toString()}`);
</script>

<template>
    <Head title="Revenue & Financial Reporting" />
    <div class="analytics-report-page space-y-8 p-6">
        <AnalyticsHeader title="Revenue & Financial Reporting" description="Detailed sales, refund, tax, discount, and reconciliation reporting.">
            <template #actions>
                
                <Button as-child><a :href="exportHref"><Download class="mr-2 h-4 w-4" />Export CSV</a></Button>
            </template>
        </AnalyticsHeader>

        <AnalyticsFilterPanel content-class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end" @submit="applyFilters">
            <div class="grid gap-2"><Label for="period">Reporting period</Label><select id="period" v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option></select></div>
            <div class="grid gap-2"><Label for="start_date">Start date</Label><Input id="start_date" v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <div class="grid gap-2"><Label for="end_date">End date</Label><Input id="end_date" v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <Button type="submit">Apply period</Button>
        </AnalyticsFilterPanel>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Collected revenue" :value="money(report.summary.collected_revenue_cents)" emphasized><template #icon><BadgeDollarSign class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Refunds" :value="money(report.summary.refunds_cents)"><template #icon><RotateCcw class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Net revenue" :value="money(report.summary.net_revenue_cents)"><template #icon><ReceiptText class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Paid orders" :value="report.summary.paid_orders.toLocaleString()"><template #icon><ShoppingCart class="h-5 w-5" /></template></MetricCard>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Gross merchandise value" :value="money(report.summary.gross_sales_cents)" />
            <MetricCard label="Discounts" :value="money(report.summary.discounts_cents)" />
            <MetricCard label="Tax collected" :value="money(report.summary.tax_collected_cents)" />
            <MetricCard label="Average order value" :value="money(report.summary.average_order_value_cents)" />
        </section>

        <ShowSection title="Reconciliation health" description="Data-quality checks that identify financial records needing attention.">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border p-4"><div class="flex items-center gap-2 font-medium"><ShieldCheck class="h-4 w-4" />Status</div><p class="mt-2 text-2xl font-semibold">{{ report.reconciliation.is_reconciled ? 'Clear' : 'Review needed' }}</p></div>
                <div class="rounded-lg border p-4"><p class="text-sm text-muted-foreground">Paid orders without reference</p><p class="mt-2 text-2xl font-semibold">{{ report.reconciliation.paid_orders_without_payment_reference }}</p></div>
                <div class="rounded-lg border p-4"><p class="text-sm text-muted-foreground">Refund status without ledger</p><p class="mt-2 text-2xl font-semibold">{{ report.reconciliation.refund_status_without_ledger }}</p></div>
                <div class="rounded-lg border p-4"><p class="text-sm text-muted-foreground">Failed transactions</p><p class="mt-2 text-2xl font-semibold">{{ report.reconciliation.failed_financial_transactions }}</p></div>
            </div>
        </ShowSection>

        <div class="grid gap-6 xl:grid-cols-2">
            <ShowSection title="Revenue by license" description="Paid item revenue grouped by license snapshot."><DistributionBars :items="report.licenses" /></ShowSection>
            <ShowSection title="Revenue by asset" description="Top assets by paid item revenue."><DistributionBars :items="report.assets" /></ShowSection>
        </div>

        <ShowSection title="Order drill-down" description="The latest 100 paid orders in the selected period.">
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm"><thead class="bg-muted/50 text-left"><tr><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Provider</th><th class="p-3 text-right">Collected</th><th class="p-3 text-right">Refunded</th><th class="p-3">Paid</th></tr></thead>
                    <tbody class="divide-y"><tr v-for="order in report.orders" :key="order.id"><td class="p-3"><Link :href="`/admin/orders/${order.id}`" class="font-medium hover:underline">{{ order.order_number }}</Link></td><td class="p-3">{{ order.customer }}</td><td class="p-3 capitalize">{{ order.provider }}</td><td class="p-3 text-right">{{ money(order.total_cents) }}</td><td class="p-3 text-right">{{ money(order.refunded_cents) }}</td><td class="p-3">{{ dateTime(order.paid_at) }}</td></tr><tr v-if="!report.orders.length"><td colspan="6" class="p-8 text-center text-muted-foreground">No paid orders were recorded for this period.</td></tr></tbody>
                </table>
            </div>
        </ShowSection>
    </div>
</template>
