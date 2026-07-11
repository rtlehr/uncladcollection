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
        context?: string;
    }>(),
    {
        label: undefined,
        tone: undefined,
        size: 'sm',
        context: 'Status',
    },
);

const config = computed(() => getStatusBadgeConfig(props.status));
const displayLabel = computed(() => props.label ?? config.value.label);
const displayTone = computed(() => props.tone ?? config.value.tone);

const toneClasses: Record<StatusBadgeTone, string> = {
    neutral: 'border-border bg-muted/70 text-muted-foreground',
    primary: 'border-primary/25 bg-primary/10 text-primary',
    success: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    warning: 'border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    danger: 'border-red-500/25 bg-red-500/10 text-red-700 dark:text-red-300',
    info: 'border-sky-500/25 bg-sky-500/10 text-sky-700 dark:text-sky-300',
};

const dotClasses: Record<StatusBadgeTone, string> = {
    neutral: 'bg-muted-foreground/70',
    primary: 'bg-primary',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-red-500',
    info: 'bg-sky-500',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center whitespace-nowrap rounded-full border font-semibold shadow-[0_1px_0_rgba(0,0,0,0.03)]',
            size === 'sm'
                ? 'gap-1.5 px-2.5 py-0.5 text-xs'
                : 'gap-2 px-3 py-1 text-sm',
            toneClasses[displayTone],
        ]"
    >
        <span class="sr-only">
            {{ context }}:
        </span>

        <span
            :class="[
                'rounded-full',
                size === 'sm' ? 'h-1.5 w-1.5' : 'h-2 w-2',
                dotClasses[displayTone],
            ]"
            aria-hidden="true"
        />

        {{ displayLabel }}
    </span>
</template>
