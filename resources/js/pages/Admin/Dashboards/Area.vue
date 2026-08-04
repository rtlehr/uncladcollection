<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, Boxes, FileText, LayoutDashboard, Megaphone, Settings, ShieldCheck } from '@lucide/vue';
import AnalyticsHeader from '@/components/Analytics/AnalyticsHeader.vue';
import AdvertisingClientSetupGuide from '@/components/Advertising/AdvertisingClientSetupGuide.vue';
import DashboardLinkCard from '@/components/Dashboard/DashboardLinkCard.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';

const props = defineProps<{
    area: string;
    title: string;
    description: string;
    metrics: Array<{ label: string; value: number | string }>;
    links: Array<{ title: string; description: string; href: string }>;
    activity: Array<{ title: string; meta: string; href: string }>;
}>();

const icon = () => ({ assets: Boxes, blog: FileText, advertising: Megaphone, marketing: Activity, administration: ShieldCheck }[props.area] ?? LayoutDashboard);
</script>

<template>
    <Head :title="title" />
    <div class="analytics-report-page space-y-8 p-6">
        <AnalyticsHeader :title="title" :description="description" />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard v-for="(metric, index) in metrics" :key="metric.label" :label="metric.label" :value="Number.isInteger(metric.value) ? Number(metric.value).toLocaleString() : String(metric.value)" :emphasized="index === 0" size="lg">
                <template #icon><component :is="icon()" class="h-5 w-5" /></template>
            </MetricCard>
        </section>

        <div class="grid gap-6 2xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <ShowSection title="Workspace" description="Open the tools and reports used most often in this area.">
                    <div class="grid gap-4 md:grid-cols-2">
                        <DashboardLinkCard v-for="link in links" :key="`${link.title}-${link.href}`" v-bind="link" />
                    </div>
                </ShowSection>

                <ShowSection
                    v-if="area === 'advertising'"
                    title="Advertising client workflow"
                    description="A guided process for creating an advertiser and taking the engagement through delivery and reporting."
                >
                    <AdvertisingClientSetupGuide />
                </ShowSection>
            </div>

            <ShowSection title="Recent activity" description="The newest records in this workspace.">
                <div v-if="activity.length" class="divide-y rounded-lg border">
                    <Link v-for="item in activity" :key="`${item.title}-${item.href}`" :href="item.href" class="block p-4 hover:bg-muted/30">
                        <p class="font-medium">{{ item.title }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ item.meta }}</p>
                    </Link>
                </div>
                <div v-else class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">No recent activity is available.</div>
            </ShowSection>
        </div>
    </div>
</template>
