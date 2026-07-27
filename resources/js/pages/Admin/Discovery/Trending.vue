<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Score = {
    id: number;
    rank: number;
    score: number;
    components: Record<string, number>;
    calculated_at: string;
    asset: {
        id: number;
        title: string;
        slug: string;
        asset_type: string;
        trending_boost: number;
        suppress_from_trending: boolean;
    };
};

const props = defineProps<{
    period: string;
    periods: Array<{ value: string; label: string }>;
    scores: { data: Score[]; links: Array<{ url: string | null; label: string; active: boolean }> };
}>();

function save(score: Score): void {
    router.patch(`/admin/discovery/trending/${score.asset.id}`, {
        trending_boost: score.asset.trending_boost,
        suppress_from_trending: score.asset.suppress_from_trending,
    }, { preserveScroll: true });
}

function rebuild(): void {
    router.post('/admin/discovery/trending/rebuild', { period: props.period }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Trending Assets" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Marketplace discovery</p>
                <h1 class="text-3xl font-semibold tracking-tight">Trending assets</h1>
                <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                    Inspect time-decayed rankings, add a limited editorial boost, or suppress an asset from trending sections.
                </p>
            </div>
            <button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground" type="button" @click="rebuild">
                Rebuild rankings
            </button>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link
                v-for="item in periods"
                :key="item.value"
                :href="`/admin/discovery/trending?period=${item.value}`"
                class="rounded-full border px-4 py-2 text-sm"
                :class="item.value === period ? 'bg-primary text-primary-foreground' : 'bg-background'"
            >
                {{ item.label }}
            </Link>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-card">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="p-3">Rank</th><th class="p-3">Asset</th><th class="p-3">Score</th>
                        <th class="p-3">Boost</th><th class="p-3">Suppressed</th><th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="score in scores.data" :key="score.id" class="border-b last:border-0">
                        <td class="p-3 font-semibold">#{{ score.rank }}</td>
                        <td class="p-3">
                            <a :href="`/assets/${score.asset.slug}`" target="_blank" class="font-medium hover:underline">{{ score.asset.title }}</a>
                            <div class="text-xs text-muted-foreground">{{ score.asset.asset_type }}</div>
                        </td>
                        <td class="p-3">{{ Number(score.score).toFixed(2) }}</td>
                        <td class="p-3"><input v-model.number="score.asset.trending_boost" type="number" min="-100" max="100" class="w-24 rounded-md border bg-background px-2 py-1.5" /></td>
                        <td class="p-3"><input v-model="score.asset.suppress_from_trending" type="checkbox" /></td>
                        <td class="p-3 text-right"><button type="button" class="rounded-md border px-3 py-1.5" @click="save(score)">Save</button></td>
                    </tr>
                    <tr v-if="!scores.data.length"><td colspan="6" class="p-10 text-center text-muted-foreground">No ranking exists yet. Select Rebuild rankings.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
