<script setup lang="ts">
import { computed } from 'vue';
import AnalyticsEmptyState from '@/components/Analytics/AnalyticsEmptyState.vue';

const props = defineProps<{
    points: Array<{ label: string; revenue_cents: number; orders_count: number }>;
}>();

const width = 900;
const height = 260;
const padding = 24;
const maxValue = computed(() => Math.max(...props.points.map((point) => point.revenue_cents), 1));
const coordinates = computed(() => props.points.map((point, index) => {
    const x = props.points.length <= 1 ? width / 2 : padding + (index * (width - padding * 2)) / (props.points.length - 1);
    const y = height - padding - (point.revenue_cents / maxValue.value) * (height - padding * 2);
    return { ...point, x, y };
}));
const polyline = computed(() => coordinates.value.map((point) => `${point.x},${point.y}`).join(' '));
const money = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(cents / 100);
</script>

<template>
    <div v-if="points.length" class="space-y-3">
        <div class="overflow-hidden rounded-lg border bg-muted/10 p-3">
            <svg :viewBox="`0 0 ${width} ${height}`" class="h-64 w-full" role="img" aria-label="Revenue trend over the selected reporting period">
                <line v-for="line in 4" :key="line" :x1="padding" :x2="width-padding" :y1="padding + ((line-1)*(height-padding*2)/3)" :y2="padding + ((line-1)*(height-padding*2)/3)" class="stroke-border" stroke-width="1" />
                <polyline v-if="points.length" :points="polyline" fill="none" class="stroke-primary" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                <g v-for="point in coordinates" :key="point.label">
                    <circle :cx="point.x" :cy="point.y" r="5" class="fill-background stroke-primary" stroke-width="3"><title>{{ point.label }}: {{ money(point.revenue_cents) }}, {{ point.orders_count }} orders</title></circle>
                </g>
            </svg>
        </div>
        <div class="flex justify-between text-xs text-muted-foreground" aria-hidden="true">
            <span>{{ points[0]?.label }}</span><span>{{ points[Math.floor(points.length / 2)]?.label }}</span><span>{{ points[points.length - 1]?.label }}</span>
        </div>
    </div>
    <AnalyticsEmptyState v-else compact title="No timeline activity" description="No trend data was recorded for the selected reporting period." />
</template>
