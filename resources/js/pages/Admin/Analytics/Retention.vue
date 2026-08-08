<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BellRing, Download, Heart, RefreshCcw, Users } from '@lucide/vue';
import { reactive } from 'vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import MetricCard from '@/components/Shared/MetricCard.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ filters: Record<string, string>; report: any }>();
const filters = reactive({ ...props.filters });
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
const applyFilters = () => router.get('/admin/analytics/retention', filters, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Customer Retention" />
    <div class="space-y-8 p-6">
        <AnalyticsHeader title="Customer Retention" description="Repeat purchases, wish-list conversion, notification engagement, and post-purchase activity." />
        <AnalyticsFilterPanel content-class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end" @submit="applyFilters">
            <div class="grid gap-2"><Label for="period">Reporting period</Label><select id="period" v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option></select></div>
            <div class="grid gap-2"><Label for="start_date">Start date</Label><Input id="start_date" v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <div class="grid gap-2"><Label for="end_date">End date</Label><Input id="end_date" v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'" /></div>
            <Button type="submit">Apply period</Button>
        </AnalyticsFilterPanel>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Repeat purchase rate" :value="`${report.summary.repeat_purchase_rate}%`" :trend="`${report.summary.repeat_buyers} repeat buyers`"><template #icon><RefreshCcw class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Wish-list conversion" :value="`${report.summary.wish_list_conversion_rate}%`" :trend="`${report.summary.wish_list_conversions} converted saves`"><template #icon><Heart class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Notification open rate" :value="`${report.summary.notification_open_rate}%`" :trend="`${report.summary.notifications_opened} opens`"><template #icon><BellRing class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Re-download customers" :value="report.summary.re_download_customers.toLocaleString()" :trend="`${report.summary.license_document_downloads} document downloads`"><template #icon><Download class="h-5 w-5" /></template></MetricCard>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Buyers" :value="report.summary.buyers.toLocaleString()"><template #icon><Users class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Account visits" :value="report.summary.account_visits.toLocaleString()"><template #icon><Users class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Wish-list saves" :value="report.summary.wish_list_saves.toLocaleString()"><template #icon><Heart class="h-5 w-5" /></template></MetricCard>
            <MetricCard label="Notifications generated" :value="report.summary.notifications_generated.toLocaleString()"><template #icon><BellRing class="h-5 w-5" /></template></MetricCard>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <ShowSection title="Top repeat buyers" description="Customers who purchased before this period and purchased again during it.">
                <div v-if="report.repeat_buyers.length" class="divide-y rounded-lg border">
                    <Link v-for="customer in report.repeat_buyers" :key="customer.id" :href="`/admin/analytics/customers/${customer.id}`" class="flex items-center justify-between gap-4 p-4 hover:bg-muted/30">
                        <div class="min-w-0"><p class="font-medium">{{ customer.name }}</p><p class="truncate text-sm text-muted-foreground">{{ customer.email }}</p></div>
                        <div class="text-right"><p class="font-semibold">{{ money(customer.revenue_cents) }}</p><p class="text-xs text-muted-foreground">{{ customer.orders_count }} orders</p></div>
                    </Link>
                </div><p v-else class="py-10 text-center text-sm text-muted-foreground">No repeat buyers were recorded for this period.</p>
            </ShowSection>

            <ShowSection title="Wish-list conversions" description="Saved assets that were later purchased by the same customer.">
                <div v-if="report.wish_list_conversions.length" class="divide-y rounded-lg border">
                    <div v-for="row in report.wish_list_conversions" :key="`${row.user_id}-${row.asset_id}`" class="flex items-center justify-between gap-4 p-4">
                        <div class="min-w-0"><p class="font-medium">{{ row.asset_title }}</p><p class="truncate text-sm text-muted-foreground">{{ row.name }} · {{ row.email }}</p></div><p class="font-semibold">{{ money(row.revenue_cents) }}</p>
                    </div>
                </div><p v-else class="py-10 text-center text-sm text-muted-foreground">No wish-list conversions were recorded for this period.</p>
            </ShowSection>
        </div>
    </div>
</template>
