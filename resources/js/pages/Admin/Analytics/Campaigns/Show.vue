<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import TrendChart from '@/components/Analytics/TrendChart.vue';
import { Button } from '@/components/ui/button';
const props=defineProps<{filters:any;report:any}>();
const money=(c:number)=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format((c||0)/100);
const timeline=props.report.timeline.map((p:any)=>({label:p.label,revenue_cents:p.revenue_cents,orders_count:p.orders}));
</script>
<template><Head :title="`${report.campaign.name} Analytics`"/><div class="space-y-8 p-6"><PageHeader :title="report.campaign.name" description="Campaign reach, engagement, and influenced marketplace performance."><template #actions><Button variant="outline" as-child><Link href="/admin/analytics/campaigns">Back to campaigns</Link></Button></template></PageHeader><section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard label="Impressions" :value="report.performance.impressions"/><MetricCard label="Clicks" :value="report.performance.clicks"/><MetricCard label="CTR" :value="`${report.performance.click_through_rate_percent}%`"/><MetricCard label="Influenced revenue" :value="money(report.performance.influenced_revenue_cents)" emphasized/></section><div class="grid gap-6 xl:grid-cols-[2fr_1fr]"><ShowSection title="Daily campaign activity" description="Impressions and clicks across the selected period."><TrendChart :points="timeline"/></ShowSection><ShowSection title="Campaign details"><dl class="space-y-3 text-sm"><div><dt class="text-muted-foreground">Media</dt><dd class="font-medium">{{ report.campaign.media_type }}</dd></div><div><dt class="text-muted-foreground">Status</dt><dd class="font-medium">{{ report.performance.status }}</dd></div><div><dt class="text-muted-foreground">Primary clicks</dt><dd>{{ report.performance.primary_clicks }}</dd></div><div><dt class="text-muted-foreground">Secondary clicks</dt><dd>{{ report.performance.secondary_clicks }}</dd></div><div><dt class="text-muted-foreground">Revenue per viewer</dt><dd>{{ money(report.performance.revenue_per_viewer_cents) }}</dd></div></dl></ShowSection></div></div></template>
