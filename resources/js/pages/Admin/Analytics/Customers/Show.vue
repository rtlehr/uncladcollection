<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { reactive } from 'vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import TrendChart from '@/components/Analytics/TrendChart.vue';
import { Button } from '@/components/ui/button';
const props=defineProps<{filters:any;report:any}>(); const filters=reactive({...props.filters});
const money=(c:number)=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(c/100);
const apply=()=>router.get(`/admin/analytics/customers/${props.report.customer.id}`,filters,{preserveState:true,replace:true});
</script>
<template><Head :title="`${report.customer.name} Customer Analytics`"/><div class="analytics-report-page space-y-8 p-6"><AnalyticsHeader :title="report.customer.name" :description="report.customer.email"></AnalyticsHeader>
<form class="flex max-w-xl gap-3 rounded-xl border p-4" @submit.prevent="apply"><select v-model="filters.period" class="h-10 flex-1 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option></select><Button>Apply</Button></form>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard label="Period revenue" :value="money(report.performance?.period_revenue_cents || 0)"/><MetricCard label="Period orders" :value="String(report.performance?.period_orders || 0)"/><MetricCard label="Lifetime value" :value="money(report.performance?.lifetime_revenue_cents || 0)"/><MetricCard label="Lifetime orders" :value="String(report.performance?.lifetime_orders || 0)"/></section>
<ShowSection title="Customer activity trend" description="Views, cart additions, orders, and revenue during the selected period."><TrendChart :points="report.timeline.map((p:any)=>({...p,revenue_cents:p.revenue_cents,orders_count:p.orders}))"/></ShowSection>
<div class="grid gap-6 xl:grid-cols-2"><ShowSection title="Recent paid orders"><div class="divide-y rounded-lg border"><div v-for="o in report.orders" :key="o.id" class="flex justify-between p-4"><Link :href="`/admin/orders/${o.id}`" class="font-medium hover:underline">{{ o.order_number }}</Link><span>{{ money(o.total_cents) }}</span></div><p v-if="!report.orders.length" class="p-8 text-center text-muted-foreground">No paid orders.</p></div></ShowSection><ShowSection title="Active cart"><div class="divide-y rounded-lg border"><div v-for="i in report.active_cart" :key="i.id" class="flex justify-between p-4"><span>{{ i.asset_title || 'Cart item' }} × {{ i.quantity }}</span><span>{{ money(i.value_cents) }}</span></div><p v-if="!report.active_cart.length" class="p-8 text-center text-muted-foreground">No active cart items.</p></div></ShowSection></div>
</div></template>
