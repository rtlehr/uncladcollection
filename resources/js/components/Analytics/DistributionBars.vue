<script setup lang="ts">
import { computed } from 'vue';
import AnalyticsEmptyState from '@/components/Analytics/AnalyticsEmptyState.vue';
const props = defineProps<{ items: Array<{ label: string; revenue_cents: number; units: number }> }>();
const max = computed(() => Math.max(...props.items.map((item) => item.revenue_cents), 1));
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
</script>
<template>
    <div v-if="items.length" class="space-y-4">
        <div v-for="item in items" :key="item.label" class="space-y-1.5">
            <div class="flex items-center justify-between gap-4 text-sm"><span class="font-medium">{{ item.label }}</span><span class="text-muted-foreground">{{ money(item.revenue_cents) }} · {{ item.units }} units</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-muted" role="progressbar" :aria-label="`${item.label}: ${money(item.revenue_cents)}`" :aria-valuenow="item.revenue_cents" :aria-valuemax="max"><div class="h-full rounded-full bg-primary" :style="{ width: `${Math.max((item.revenue_cents / max) * 100, 2)}%` }" /></div>
        </div>
    </div>
    <AnalyticsEmptyState v-else compact title="No distribution data" description="No paid sales were recorded for this period." />
</template>
