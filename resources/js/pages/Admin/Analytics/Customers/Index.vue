<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, ShoppingCart, UserRoundCheck, UserRoundPlus, Users } from '@lucide/vue';
import { reactive } from 'vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ filters:any; report:any }>();
const filters = reactive({ ...props.filters });
const money = (c:number) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(c/100);
const dateTime = (v:string|null) => v ? new Intl.DateTimeFormat('en-US',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)) : '—';
const apply = () => router.get('/admin/analytics/customers', filters, { preserveState:true, replace:true });
const exportHref = () => `/admin/analytics/customers/export?${new URLSearchParams(Object.entries(filters).filter(([,v])=>v!==null&&v!=='') as [string,string][]).toString()}`;
</script>

<template>
<Head title="Customer and Conversion Analytics" />
<div class="space-y-8 p-6">
<PageHeader title="Customer and Conversion Analytics" description="Understand who buys, how customers move through the funnel, and where revenue or re-engagement opportunities exist.">
  <template #actions><Button variant="outline" as-child><a :href="exportHref()"><Download class="mr-2 h-4 w-4"/>Export CSV</a></Button></template>
</PageHeader>

<form class="grid gap-4 rounded-xl border bg-background p-4 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end" @submit.prevent="apply">
<div class="grid gap-2"><Label>Period</Label><select v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option></select></div>
<div class="grid gap-2"><Label>Search</Label><Input v-model="filters.search" placeholder="Name, username, or email"/></div>
<div class="grid gap-2"><Label>Segment</Label><select v-model="filters.segment" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="all">All buyers</option><option value="first_time">First-time</option><option value="repeat">Repeat</option></select></div>
<div class="grid gap-2"><Label>Start date</Label><Input v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'"/></div>
<div class="grid gap-2"><Label>End date</Label><Input v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'"/></div>
<Button type="submit">Apply</Button>
</form>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
<MetricCard label="Buyers" :value="report.summary.buyers.toLocaleString()"><template #icon><Users class="h-5 w-5"/></template></MetricCard>
<MetricCard label="First-time customers" :value="report.summary.new_customers.toLocaleString()"><template #icon><UserRoundPlus class="h-5 w-5"/></template></MetricCard>
<MetricCard label="Repeat customers" :value="report.summary.repeat_customers.toLocaleString()" :trend="`${report.summary.repeat_customer_percent}% of buyers`"><template #icon><UserRoundCheck class="h-5 w-5"/></template></MetricCard>
<MetricCard label="Average customer value" :value="money(report.summary.average_customer_value_cents)" :trend="`${report.summary.orders_per_customer} orders per buyer`"><template #icon><ShoppingCart class="h-5 w-5"/></template></MetricCard>
</section>

<ShowSection title="Customer conversion funnel" description="Marketplace activity across the selected period. Each percentage compares with the prior stage.">
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6"><div v-for="(s,i) in report.funnel" :key="s.key" class="rounded-xl border p-4"><p class="text-xs font-semibold uppercase text-muted-foreground">Step {{ i+1 }}</p><p class="mt-2 font-medium">{{ s.label }}</p><p class="mt-2 text-2xl font-semibold">{{ s.value.toLocaleString() }}</p><p class="mt-1 text-xs text-muted-foreground">{{ i===0?'Starting activity':`${s.conversion_percent}% from prior` }}</p></div></div>
</ShowSection>

<div class="grid gap-6 xl:grid-cols-2">
<ShowSection title="First-time vs repeat revenue" description="Customer segments based on purchase history before the selected period."><DistributionBars :items="report.segments"/></ShowSection>
<ShowSection title="Abandoned cart opportunity" description="Active cart lines untouched for at least 24 hours."><div class="grid gap-3 sm:grid-cols-2"><div class="rounded-lg border p-4"><p class="text-sm text-muted-foreground">Cart lines</p><p class="mt-1 text-2xl font-semibold">{{ report.summary.abandoned_cart_lines }}</p></div><div class="rounded-lg border p-4"><p class="text-sm text-muted-foreground">Potential value</p><p class="mt-1 text-2xl font-semibold">{{ money(report.summary.abandoned_cart_value_cents) }}</p></div></div></ShowSection>
</div>

<ShowSection title="Customer performance" description="Buyers ranked by revenue in the selected period.">
<div class="overflow-x-auto rounded-lg border"><table class="w-full text-sm"><thead class="bg-muted/50 text-left"><tr><th class="p-3">Customer</th><th class="p-3">Segment</th><th class="p-3 text-right">Orders</th><th class="p-3 text-right">Revenue</th><th class="p-3 text-right">Lifetime value</th><th class="p-3 text-right">Downloads</th></tr></thead><tbody class="divide-y"><tr v-for="c in report.customers" :key="c.customer_id"><td class="p-3"><Link :href="`/admin/analytics/customers/${c.customer_id}?period=${filters.period}`" class="font-medium hover:underline">{{ c.name }}</Link><p class="text-xs text-muted-foreground">{{ c.email }}</p></td><td class="p-3 capitalize">{{ c.segment.replace('_',' ') }}</td><td class="p-3 text-right">{{ c.period_orders }}</td><td class="p-3 text-right font-medium">{{ money(c.period_revenue_cents) }}</td><td class="p-3 text-right">{{ money(c.lifetime_revenue_cents) }}</td><td class="p-3 text-right">{{ c.downloads }}</td></tr><tr v-if="!report.customers.length"><td colspan="6" class="p-8 text-center text-muted-foreground">No buyers matched this period.</td></tr></tbody></table></div>
</ShowSection>

<div class="grid gap-6 xl:grid-cols-2"><ShowSection title="License preferences" description="Revenue and customers by purchased license."><DistributionBars :items="report.license_preferences"/></ShowSection><ShowSection title="Media preferences" description="Revenue and customers by asset type."><DistributionBars :items="report.media_preferences"/></ShowSection></div>

<ShowSection title="Abandoned carts" description="Recent re-engagement candidates based on stale active cart lines."><div class="divide-y rounded-lg border"><div v-for="c in report.abandoned_carts" :key="c.cart_item_id" class="flex items-center justify-between gap-4 p-4"><div><Link v-if="c.customer_id" :href="`/admin/analytics/customers/${c.customer_id}`" class="font-medium hover:underline">{{ c.customer_name || c.customer_email }}</Link><p class="text-sm text-muted-foreground">{{ c.asset_title || 'Legacy item' }} · {{ dateTime(c.updated_at) }}</p></div><span class="font-semibold">{{ money(c.value_cents) }}</span></div><p v-if="!report.abandoned_carts.length" class="p-8 text-center text-sm text-muted-foreground">No stale carts were found.</p></div></ShowSection>
</div>
</template>
