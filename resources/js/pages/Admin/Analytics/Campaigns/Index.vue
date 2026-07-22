<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity, BadgeDollarSign, Eye, MousePointerClick, Users } from '@lucide/vue';
import { reactive } from 'vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ filters: any; report: any }>();
const filters = reactive({ ...props.filters });
const money = (cents:number) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format((cents||0)/100);
const apply = () => router.get('/admin/analytics/campaigns', filters, { preserveState:true, replace:true });
const exportUrl = () => `/admin/analytics/campaigns/export?${new URLSearchParams(filters as Record<string,string>).toString()}`;
</script>
<template>
<Head title="Campaign Performance Analytics" />
<div class="analytics-report-page space-y-8 p-6">
<AnalyticsHeader title="Campaign Performance Analytics" description="Measure campaign reach, engagement, and marketplace influence.">
<template #actions><div class="flex gap-2"><Button variant="outline" as-child><a :href="exportUrl()">Export CSV</a></Button></div></template>
</AnalyticsHeader>
<AnalyticsFilterPanel content-class="grid gap-4 xl:grid-cols-[1.4fr_1fr_1fr_1fr_auto] xl:items-end" @submit="apply">
<div class="grid gap-2"><Label>Search</Label><Input v-model="filters.search" placeholder="Campaign or headline" /></div>
<div class="grid gap-2"><Label>Media type</Label><select v-model="filters.media_type" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">All types</option><option value="image">Image</option><option value="video">Video</option></select></div>
<div class="grid gap-2"><Label>Status</Label><select v-model="filters.status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
<div class="grid gap-2"><Label>Period</Label><select v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">7 days</option><option value="30_days">30 days</option><option value="90_days">90 days</option><option value="year_to_date">Year to date</option></select></div><Button type="submit">Apply</Button>
</AnalyticsFilterPanel>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6"><MetricCard label="Campaigns" :value="report.summary.campaigns"><template #icon><Activity class="h-5 w-5"/></template></MetricCard><MetricCard label="Impressions" :value="report.summary.impressions"><template #icon><Eye class="h-5 w-5"/></template></MetricCard><MetricCard label="Unique viewers" :value="report.summary.unique_viewers"><template #icon><Users class="h-5 w-5"/></template></MetricCard><MetricCard label="Clicks" :value="report.summary.clicks"><template #icon><MousePointerClick class="h-5 w-5"/></template></MetricCard><MetricCard label="CTR" :value="`${report.summary.click_through_rate_percent}%`"/><MetricCard label="Influenced revenue" :value="money(report.summary.influenced_revenue_cents)" emphasized><template #icon><BadgeDollarSign class="h-5 w-5"/></template></MetricCard></section>
<ShowSection title="Campaign performance" description="Campaigns ranked by influenced revenue, clicks, and impressions."><div class="overflow-x-auto rounded-lg border"><table class="w-full text-sm"><thead class="bg-muted/50 text-left"><tr><th class="p-3">Campaign</th><th class="p-3">Status</th><th class="p-3 text-right">Impressions</th><th class="p-3 text-right">Clicks</th><th class="p-3 text-right">CTR</th><th class="p-3 text-right">Orders</th><th class="p-3 text-right">Influenced revenue</th></tr></thead><tbody class="divide-y"><tr v-for="c in report.campaigns" :key="c.campaign_id"><td class="p-3"><Link :href="`/admin/analytics/campaigns/${c.campaign_id}?period=${filters.period}`" class="font-medium hover:underline">{{ c.name }}</Link><p class="text-xs text-muted-foreground">{{ c.media_type }} · {{ c.headline || 'No headline' }}</p></td><td class="p-3">{{ c.status }}</td><td class="p-3 text-right">{{ c.impressions }}</td><td class="p-3 text-right">{{ c.clicks }}</td><td class="p-3 text-right">{{ c.click_through_rate_percent }}%</td><td class="p-3 text-right">{{ c.influenced_orders }}</td><td class="p-3 text-right font-medium">{{ money(c.influenced_revenue_cents) }}</td></tr><tr v-if="!report.campaigns.length"><td colspan="7" class="p-8 text-center text-muted-foreground">No campaigns matched these filters.</td></tr></tbody></table></div></ShowSection>
<div class="grid gap-6 xl:grid-cols-2"><ShowSection title="Media-type performance" description="Influenced revenue and orders by campaign media."><DistributionBars :items="report.media_types"/></ShowSection><ShowSection title="Attribution note" description="How campaign influence is calculated."><p class="text-sm leading-6 text-muted-foreground">Orders are associated with registered users who viewed or clicked a campaign during the selected reporting period. This is an influence signal, not proof that the campaign directly caused the purchase.</p></ShowSection></div>
<div class="grid gap-6 xl:grid-cols-3"><ShowSection title="Conversion drivers"><div v-for="c in report.opportunities.conversion_drivers" :key="c.campaign_id" class="border-b p-3"><Link :href="`/admin/analytics/campaigns/${c.campaign_id}`" class="font-medium hover:underline">{{ c.name }}</Link><p class="text-sm text-muted-foreground">{{ money(c.influenced_revenue_cents) }}</p></div><p v-if="!report.opportunities.conversion_drivers.length" class="text-sm text-muted-foreground">No influenced revenue yet.</p></ShowSection><ShowSection title="High reach, low clicks"><div v-for="c in report.opportunities.high_reach_low_clicks" :key="c.campaign_id" class="border-b p-3">{{ c.name }}<p class="text-sm text-muted-foreground">{{ c.impressions }} impressions · {{ c.click_through_rate_percent }}% CTR</p></div><p v-if="!report.opportunities.high_reach_low_clicks.length" class="text-sm text-muted-foreground">No campaigns match this condition.</p></ShowSection><ShowSection title="No recent activity"><div v-for="c in report.opportunities.no_recent_activity" :key="c.campaign_id" class="border-b p-3">{{ c.name }}</div><p v-if="!report.opportunities.no_recent_activity.length" class="text-sm text-muted-foreground">Every campaign received activity.</p></ShowSection></div>
</div>
</template>
