<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        title: string;
        description?: string | null;
        eyebrow?: string;
    }>(),
    {
        description: null,
        eyebrow: 'Marketplace Intelligence',
    },
);

const page = usePage();

const items = [
    { label: 'Overview', href: '/admin/analytics', match: (url: string) => url === '/admin/analytics' },
    { label: 'Financial', href: '/admin/analytics/financial', match: (url: string) => url.startsWith('/admin/analytics/financial') },
    { label: 'Assets', href: '/admin/analytics/assets', match: (url: string) => url.startsWith('/admin/analytics/assets') },
    { label: 'Customers', href: '/admin/analytics/customers', match: (url: string) => url.startsWith('/admin/analytics/customers') },
    { label: 'Content', href: '/admin/analytics/blog', match: (url: string) => url.startsWith('/admin/analytics/blog') },
    { label: 'Campaigns', href: '/admin/analytics/campaigns', match: (url: string) => url.startsWith('/admin/analytics/campaigns') },
    { label: 'Search', href: '/admin/analytics/search', match: (url: string) => url.startsWith('/admin/analytics/search') },
    { label: 'Downloads', href: '/admin/analytics/downloads', match: (url: string) => url.startsWith('/admin/analytics/downloads') },
];

const currentUrl = computed(() => page.url.split('?')[0].replace(/\/$/, '') || '/');
const printReport = () => window.print();
</script>

<template>
    <div class="space-y-5">
        <PageHeader :eyebrow="eyebrow" :title="title" :description="description">
            <template #actions>
                <div class="analytics-header-actions flex flex-wrap items-center gap-2">
                    <slot name="actions" />
                    <Button type="button" variant="outline" @click="printReport">
                        <Printer class="mr-2 h-4 w-4" aria-hidden="true" />Print
                    </Button>
                </div>
            </template>
        </PageHeader>

        <nav aria-label="Analytics reports" class="analytics-report-nav border-b">
            <div class="-mb-px flex gap-1 overflow-x-auto pb-px" role="list">
                <Link
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    :aria-current="item.match(currentUrl) ? 'page' : undefined"
                    :class="[
                        'shrink-0 rounded-t-md border-b-2 px-3 py-2.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                        item.match(currentUrl)
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-transparent text-muted-foreground hover:border-border hover:bg-muted/50 hover:text-foreground',
                    ]"
                >
                    {{ item.label }}
                </Link>
            </div>
        </nav>
    </div>
</template>


<style>
@media print {
    .analytics-report-nav,
    .analytics-header-actions,
    .analytics-filter-panel { display: none !important; }
    .analytics-report-page { padding: 0 !important; }
    body { background: white !important; }
    table { break-inside: auto; }
    tr { break-inside: avoid; }
}
</style>
