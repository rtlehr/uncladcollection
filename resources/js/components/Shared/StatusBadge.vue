<script setup lang="ts">
import { computed } from 'vue';

import {
    getStatusBadgeConfig,
    type StatusBadgeTone,
} from '@/lib/statusBadge';

const props = withDefaults(
    defineProps<{
        status: string;
        label?: string;
        tone?: StatusBadgeTone;
        size?: 'sm' | 'md';
    }>(),
    {
        label: undefined,
        tone: undefined,
        size: 'sm',
    },
);

const config = computed(() => getStatusBadgeConfig(props.status));
const displayLabel = computed(() => props.label ?? config.value.label);
const displayTone = computed(() => props.tone ?? config.value.tone);

const toneClasses: Record<StatusBadgeTone, string> = {
    neutral: 'border-border bg-muted text-muted-foreground',
    primary: 'border-primary/30 bg-primary/10 text-primary',
    success: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    warning: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    danger: 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300',
    info: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center whitespace-nowrap rounded-full border font-medium',
            size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-3 py-1 text-sm',
            toneClasses[displayTone],
        ]"
    >
        {{ displayLabel }}
    </span>
</template>
