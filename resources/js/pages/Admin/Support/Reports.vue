<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';

const props = defineProps<{
    report: {
        period: { from: string; to: string };
        summary: Record<string, number | null>;
        by_status: Array<{ label: string; count: number }>;
        by_priority: Array<{ label: string; count: number }>;
        by_category: Array<{ id: number; name: string; count: number }>;
        by_assignee: Array<{ id: number; name: string; count: number }>;
    };
    filters: { period: string };
}>();

function changePeriod(event: Event) {
    router.get('/admin/support/reports', { period: (event.target as HTMLSelectElement).value }, { preserveState: true, replace: true });
}

function formatMinutes(value: number | null) {
    if (value === null) return '—';
    if (value < 60) return `${value} min`;
    return `${(value / 60).toFixed(1)} hr`;
}
</script>

<template>
    <Head title="Support Reports" />
    <div class="space-y-6 p-6">
        <PageHeader title="Support Reports" description="Review ticket volume, response time, resolution time, categories, and staff workload." />

        <div class="flex items-center gap-3">
            <label for="report-period" class="text-sm font-medium">Reporting period</label>
            <select id="report-period" :value="filters.period" class="h-10 rounded-md border bg-background px-3" @change="changePeriod">
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="365">Last 365 days</option>
            </select>
            <span class="text-sm text-muted-foreground">{{ report.period.from }} through {{ report.period.to }}</span>
        </div>

        <section aria-labelledby="summary-heading">
            <h2 id="summary-heading" class="sr-only">Support performance summary</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">Created</p><p class="mt-2 text-3xl font-semibold">{{ report.summary.created }}</p></div>
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">Resolved</p><p class="mt-2 text-3xl font-semibold">{{ report.summary.resolved }}</p></div>
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">Resolution rate</p><p class="mt-2 text-3xl font-semibold">{{ report.summary.resolution_rate_percent }}%</p></div>
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">First response</p><p class="mt-2 text-3xl font-semibold">{{ formatMinutes(report.summary.average_first_response_minutes) }}</p></div>
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">Resolution time</p><p class="mt-2 text-3xl font-semibold">{{ formatMinutes(report.summary.average_resolution_minutes) }}</p></div>
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">Current backlog</p><p class="mt-2 text-3xl font-semibold">{{ report.summary.backlog }}</p></div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-lg border bg-card" aria-labelledby="category-heading">
                <h2 id="category-heading" class="border-b p-4 font-semibold">Tickets by category</h2>
                <ul class="divide-y">
                    <li v-for="row in report.by_category" :key="row.id" class="flex justify-between p-4"><span>{{ row.name }}</span><strong>{{ row.count }}</strong></li>
                    <li v-if="!report.by_category.length" class="p-4 text-muted-foreground">No category data.</li>
                </ul>
            </section>
            <section class="rounded-lg border bg-card" aria-labelledby="assignee-heading">
                <h2 id="assignee-heading" class="border-b p-4 font-semibold">Assigned workload</h2>
                <ul class="divide-y">
                    <li v-for="row in report.by_assignee" :key="row.id" class="flex justify-between p-4"><span>{{ row.name }}</span><strong>{{ row.count }}</strong></li>
                    <li v-if="!report.by_assignee.length" class="p-4 text-muted-foreground">No assignee data.</li>
                </ul>
            </section>
            <section class="rounded-lg border bg-card" aria-labelledby="status-heading">
                <h2 id="status-heading" class="border-b p-4 font-semibold">Tickets by status</h2>
                <ul class="divide-y">
                    <li v-for="row in report.by_status" :key="row.label" class="flex justify-between p-4"><span class="capitalize">{{ row.label.replaceAll('_', ' ') }}</span><strong>{{ row.count }}</strong></li>
                </ul>
            </section>
            <section class="rounded-lg border bg-card" aria-labelledby="priority-heading">
                <h2 id="priority-heading" class="border-b p-4 font-semibold">Tickets by priority</h2>
                <ul class="divide-y">
                    <li v-for="row in report.by_priority" :key="row.label" class="flex justify-between p-4"><span class="capitalize">{{ row.label }}</span><strong>{{ row.count }}</strong></li>
                </ul>
            </section>
        </div>
    </div>
</template>
