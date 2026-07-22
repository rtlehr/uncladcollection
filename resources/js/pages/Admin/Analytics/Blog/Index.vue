<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Download, Eye, MessageCircle, ShoppingBag, Users } from '@lucide/vue';
import { reactive } from 'vue';
import DistributionBars from '@/components/Analytics/DistributionBars.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import AnalyticsFilterPanel from '@/components/Analytics/AnalyticsFilterPanel.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ filters:any; report:any; authors:any[]; categories:any[] }>();
const filters = reactive({ ...props.filters });
const money = (c:number) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(c/100);
const apply = () => router.get('/admin/analytics/blog', filters, { preserveState:true, replace:true });
const exportHref = () => `/admin/analytics/blog/export?${new URLSearchParams(Object.entries(filters).filter(([,v])=>v!==null&&v!=='') as [string,string][]).toString()}`;
</script>

<template>
<Head title="Blog and Content Performance" />
<div class="analytics-report-page space-y-8 p-6">
<AnalyticsHeader title="Blog and Content Performance" description="Measure readership, engagement, and the marketplace activity associated with published content.">
<template #actions><Button variant="outline" as-child><a :href="exportHref()"><Download class="mr-2 h-4 w-4"/>Export CSV</a></Button></template>
</AnalyticsHeader>
<AnalyticsFilterPanel content-class="grid gap-4 lg:grid-cols-6 lg:items-end" @submit="apply">
<div class="grid gap-2"><Label>Period</Label><select v-model="filters.period" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="7_days">Last 7 days</option><option value="30_days">Last 30 days</option><option value="90_days">Last 90 days</option><option value="year_to_date">Year to date</option><option value="custom">Custom</option></select></div>
<div class="grid gap-2"><Label>Search</Label><Input v-model="filters.search" placeholder="Post title or excerpt"/></div>
<div class="grid gap-2"><Label>Author</Label><select v-model="filters.author_id" class="h-10 rounded-md border bg-background px-3 text-sm"><option :value="null">All authors</option><option v-for="a in authors" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
<div class="grid gap-2"><Label>Category</Label><select v-model="filters.category_id" class="h-10 rounded-md border bg-background px-3 text-sm"><option :value="null">All categories</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
<div class="grid gap-2"><Label>Start date</Label><Input v-model="filters.start_date" type="date" :disabled="filters.period !== 'custom'"/></div>
<div class="grid gap-2"><Label>End date</Label><Input v-model="filters.end_date" type="date" :disabled="filters.period !== 'custom'"/></div><Button type="submit">Apply</Button>
</AnalyticsFilterPanel>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
<MetricCard label="Published posts" :value="report.summary.published_posts.toLocaleString()"><template #icon><BookOpen class="h-5 w-5"/></template></MetricCard>
<MetricCard label="Post views" :value="report.summary.views.toLocaleString()"><template #icon><Eye class="h-5 w-5"/></template></MetricCard>
<MetricCard label="Unique readers" :value="report.summary.unique_readers.toLocaleString()"><template #icon><Users class="h-5 w-5"/></template></MetricCard>
<MetricCard label="Influenced revenue" :value="money(report.summary.influenced_revenue_cents)" :trend="`${report.summary.influenced_buyers} associated buyers`"><template #icon><ShoppingBag class="h-5 w-5"/></template></MetricCard>
</section>
<ShowSection title="Post performance" description="Published posts ranked by influenced revenue, readership, and engagement.">
<div class="overflow-x-auto rounded-lg border"><table class="w-full text-sm"><thead class="bg-muted/50 text-left"><tr><th class="p-3">Post</th><th class="p-3">Author</th><th class="p-3 text-right">Views</th><th class="p-3 text-right">Readers</th><th class="p-3 text-right">Comments</th><th class="p-3 text-right">Engagement</th><th class="p-3 text-right">Influenced revenue</th></tr></thead><tbody class="divide-y"><tr v-for="post in report.posts" :key="post.post_id"><td class="p-3"><Link :href="`/admin/analytics/blog/${post.slug}?period=${filters.period}`" class="font-medium hover:underline">{{ post.title }}</Link><p class="text-xs text-muted-foreground">{{ post.category_names.join(', ') || 'Uncategorized' }}</p></td><td class="p-3">{{ post.author_name }}</td><td class="p-3 text-right">{{ post.views }}</td><td class="p-3 text-right">{{ post.unique_readers }}</td><td class="p-3 text-right">{{ post.comments }}</td><td class="p-3 text-right">{{ post.engagement_rate_percent }}%</td><td class="p-3 text-right font-medium">{{ money(post.influenced_revenue_cents) }}</td></tr><tr v-if="!report.posts.length"><td colspan="7" class="p-8 text-center text-muted-foreground">No published posts matched these filters.</td></tr></tbody></table></div>
</ShowSection>
<div class="grid gap-6 xl:grid-cols-2"><ShowSection title="Author performance" description="Readership and associated revenue grouped by author."><DistributionBars :items="report.authors"/></ShowSection><ShowSection title="Category performance" description="Readership and associated revenue grouped by blog category."><DistributionBars :items="report.categories"/></ShowSection></div>
<div class="grid gap-6 xl:grid-cols-3"><ShowSection title="Conversion drivers" description="Posts associated with the most marketplace revenue."><div class="divide-y rounded-lg border"><div v-for="p in report.opportunities.conversion_drivers" :key="p.post_id" class="p-4"><Link :href="`/admin/analytics/blog/${p.slug}`" class="font-medium hover:underline">{{ p.title }}</Link><p class="text-sm text-muted-foreground">{{ money(p.influenced_revenue_cents) }} · {{ p.influenced_buyers }} buyers</p></div><p v-if="!report.opportunities.conversion_drivers.length" class="p-6 text-sm text-muted-foreground">No influenced purchases yet.</p></div></ShowSection><ShowSection title="High traffic, low engagement" description="Posts receiving views without corresponding interaction."><div class="divide-y rounded-lg border"><div v-for="p in report.opportunities.high_traffic_low_engagement" :key="p.post_id" class="p-4"><Link :href="`/admin/analytics/blog/${p.slug}`" class="font-medium hover:underline">{{ p.title }}</Link><p class="text-sm text-muted-foreground">{{ p.views }} views · {{ p.engagement_rate_percent }}% engagement</p></div><p v-if="!report.opportunities.high_traffic_low_engagement.length" class="p-6 text-sm text-muted-foreground">No posts currently match this condition.</p></div></ShowSection><ShowSection title="Content needing attention" description="Published posts without views in this reporting period."><div class="divide-y rounded-lg border"><div v-for="p in report.opportunities.stale_content" :key="p.post_id" class="p-4"><Link :href="`/admin/analytics/blog/${p.slug}`" class="font-medium hover:underline">{{ p.title }}</Link><p class="text-sm text-muted-foreground">No recorded views</p></div><p v-if="!report.opportunities.stale_content.length" class="p-6 text-sm text-muted-foreground">Every matching post received traffic.</p></div></ShowSection></div>
</div>
</template>
