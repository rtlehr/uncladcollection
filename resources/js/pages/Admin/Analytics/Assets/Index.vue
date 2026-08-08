<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Eye, Images, MousePointerClick, ShoppingCart, TrendingUp } from '@lucide/vue';
import { computed, reactive } from 'vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AssetRow = { asset_id:number; title:string; asset_type:string; collection_name:string; views:number; favorites:number; cart_additions:number; units_sold:number; revenue_cents:number; downloads:number; view_to_cart_percent:number; view_to_purchase_percent:number; revenue_per_view_cents:number };
type Report = { summary:Record<string,number>; assets:AssetRow[]; media_types:Array<{label:string;revenue_cents:number;units:number}>; collections:Array<{label:string;revenue_cents:number;units:number}>; opportunities:Record<string,AssetRow[]> };
const props = defineProps<{ filters:Record<string,string|number|null>; report:Report; collections:Array<{id:number;name:string}> }>();
const filters = reactive({ ...props.filters });
const money=(c:number)=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(c/100);
const applyFilters=()=>router.get('/admin/analytics/assets',filters,{preserveState:true,replace:true});
const exportHref=computed(()=>`/admin/analytics/assets/export?${new URLSearchParams(Object.entries(filters).filter(([,v])=>v!==null).map(([k,v])=>[k,String(v)])).toString()}`);
</script>
<template>
<Head title="Asset Performance Analytics" />
<div class="analytics-report-page space-y-8 p-6">
<AnalyticsHeader title="Asset Performance Analytics" description="See which assets attract attention, convert customers, generate revenue, and drive downloads.">
<template #actions><Button as-child><a :href="exportHref"><Download class="mr-2 h-4 w-4"/>Export CSV</a></Button></template>
</AnalyticsHeader>
<AnalyticsFilterPanel content-class="grid gap-4 xl:grid-cols-[1.2fr_.8fr_.8fr_.8fr_.8fr_auto] xl:items-end" @submit="applyFilters">
<div class="grid gap-2"><Label>Search</Label><Input v-model="filters.search" placeholder="Asset title or slug" /></div>
<div class="grid gap-2"><Label>Media type</Label><select v-model="filters.asset_type" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="all">All types</option><option value="image">Image</option><option value="video">Video</option><option value="vector">Vector</option><option value="document">Document</option><option value="archive">Archive</option><option value="other">Other</option></select></div>
<div class="grid gap-2"><Label>Collection</Label><select v-model="filters.collection_id" class="h-10 rounded-md border bg-background px-3 text-sm"><option :value="null">All collections</option><option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option></select></div>
<div class="grid gap-2"><Label>Period</Label><select v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option></select></div>
<div class="grid gap-2"><Label>Start</Label><Input v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'" /></div><div class="grid gap-2"><Label>End</Label><Input v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'" /></div><Button type="submit">Apply</Button>
</AnalyticsFilterPanel>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard label="Asset views" :value="report.summary.views.toLocaleString()"><template #icon><Eye class="h-5 w-5"/></template></MetricCard><MetricCard label="Units sold" :value="report.summary.units_sold.toLocaleString()"><template #icon><ShoppingCart class="h-5 w-5"/></template></MetricCard><MetricCard label="Asset revenue" :value="money(report.summary.revenue_cents)" emphasized><template #icon><TrendingUp class="h-5 w-5"/></template></MetricCard><MetricCard label="Downloads" :value="report.summary.downloads.toLocaleString()"><template #icon><Download class="h-5 w-5"/></template></MetricCard></section>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard label="Assets measured" :value="report.summary.assets_measured.toLocaleString()"><template #icon><Images class="h-5 w-5"/></template></MetricCard><MetricCard label="Cart additions" :value="report.summary.cart_additions.toLocaleString()"><template #icon><MousePointerClick class="h-5 w-5"/></template></MetricCard><MetricCard label="View to cart" :value="`${report.summary.view_to_cart_percent}%`"/><MetricCard label="View to purchase" :value="`${report.summary.view_to_purchase_percent}%`"/></section>
<div class="grid gap-6 xl:grid-cols-2"><ShowSection title="Performance by media type"><DistributionBars :items="report.media_types" /></ShowSection><ShowSection title="Performance by collection"><DistributionBars :items="report.collections" /></ShowSection></div>
<ShowSection title="Asset performance" description="Period-specific engagement and sales, ordered by revenue."><div class="overflow-x-auto rounded-lg border"><table class="w-full text-sm"><thead class="bg-muted/50 text-left"><tr><th class="p-3">Asset</th><th class="p-3">Type</th><th class="p-3 text-right">Views</th><th class="p-3 text-right">Cart</th><th class="p-3 text-right">Units</th><th class="p-3 text-right">Conversion</th><th class="p-3 text-right">Revenue</th><th class="p-3 text-right">Downloads</th></tr></thead><tbody class="divide-y"><tr v-for="asset in report.assets" :key="asset.asset_id"><td class="p-3"><Link :href="`/admin/analytics/assets/${asset.asset_id}?period=${filters.period}&start_date=${filters.start_date}&end_date=${filters.end_date}`" class="font-medium hover:underline">{{ asset.title }}</Link><div class="text-xs text-muted-foreground">{{ asset.collection_name }}</div></td><td class="p-3 capitalize">{{ asset.asset_type }}</td><td class="p-3 text-right">{{ asset.views }}</td><td class="p-3 text-right">{{ asset.cart_additions }}</td><td class="p-3 text-right">{{ asset.units_sold }}</td><td class="p-3 text-right">{{ asset.view_to_purchase_percent }}%</td><td class="p-3 text-right">{{ money(asset.revenue_cents) }}</td><td class="p-3 text-right">{{ asset.downloads }}</td></tr><tr v-if="!report.assets.length"><td colspan="8" class="p-8 text-center text-muted-foreground">No assets match these filters.</td></tr></tbody></table></div></ShowSection>
<div class="grid gap-6 xl:grid-cols-2"><ShowSection title="High traffic, low conversion"><ul class="space-y-3"><li v-for="asset in report.opportunities.high_traffic_low_conversion" :key="asset.asset_id" class="flex justify-between rounded-lg border p-3"><Link :href="`/admin/analytics/assets/${asset.asset_id}`" class="font-medium hover:underline">{{ asset.title }}</Link><span class="text-muted-foreground">{{ asset.views }} views · {{ asset.view_to_purchase_percent }}%</span></li><li v-if="!report.opportunities.high_traffic_low_conversion.length" class="text-sm text-muted-foreground">No assets currently meet this opportunity threshold.</li></ul></ShowSection><ShowSection title="Published without active offerings"><ul class="space-y-3"><li v-for="asset in report.opportunities.published_without_offerings" :key="asset.asset_id" class="rounded-lg border p-3"><Link :href="`/admin/assets/${asset.asset_id}`" class="font-medium hover:underline">{{ asset.title }}</Link></li><li v-if="!report.opportunities.published_without_offerings.length" class="text-sm text-muted-foreground">All measured published assets have active offerings.</li></ul></ShowSection></div>
</div>
</template>
