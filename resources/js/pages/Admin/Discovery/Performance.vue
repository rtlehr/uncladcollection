<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
defineOptions({ layout: AppLayout });
const props = defineProps<{ filters: Record<string, string>; report: { totals: Record<string, number>; sources: Array<Record<string, number | string>> } }>();
const period = ref(props.filters.period ?? '30_days');
function apply(): void {
 router.get('/admin/analytics/discovery', { period: period.value }, { preserveState: true }); 
}
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
</script>
<template>
    <Head title="Discovery Performance" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Marketplace intelligence</p><h1 class="text-3xl font-semibold tracking-tight">Discovery performance</h1><p class="mt-2 text-sm text-muted-foreground">Compare how search, recommendations, trending, recently viewed, and promoted collections contribute to engagement and conversion.</p></div><div class="flex gap-2"><select v-model="period" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="7_days">7 days</option><option value="30_days">30 days</option><option value="90_days">90 days</option><option value="year_to_date">Year to date</option></select><button class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground" @click="apply">Apply</button></div></div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5"><div v-for="(value, key) in report.totals" :key="key" class="rounded-xl border bg-card p-4"><div class="text-xs uppercase tracking-wide text-muted-foreground">{{ String(key).replaceAll('_', ' ') }}</div><div class="mt-2 text-2xl font-semibold">{{ key === 'revenue_cents' ? money(Number(value)) : Number(value).toLocaleString() }}</div></div></div>
        <div class="overflow-x-auto rounded-xl border bg-card"><table class="w-full text-sm"><thead class="border-b bg-muted/40 text-left"><tr><th class="p-3">Source</th><th class="p-3">Views</th><th class="p-3">Favorites</th><th class="p-3">Cart</th><th class="p-3">Orders</th><th class="p-3">Downloads</th><th class="p-3">Engagement</th><th class="p-3">Conversion</th><th class="p-3">Revenue</th></tr></thead><tbody><tr v-for="row in report.sources" :key="String(row.source)" class="border-b last:border-0"><td class="p-3 font-medium">{{ String(row.source).replaceAll('_', ' ') }}</td><td class="p-3">{{ row.views }}</td><td class="p-3">{{ row.favorites }}</td><td class="p-3">{{ row.cart_additions }}</td><td class="p-3">{{ row.orders }}</td><td class="p-3">{{ row.downloads }}</td><td class="p-3">{{ row.engagement_rate }}%</td><td class="p-3">{{ row.conversion_rate }}%</td><td class="p-3">{{ money(Number(row.revenue_cents)) }}</td></tr><tr v-if="!report.sources.length"><td colspan="9" class="p-10 text-center text-muted-foreground">No attributed discovery activity for this period.</td></tr></tbody></table></div>
    </div>
</template>
