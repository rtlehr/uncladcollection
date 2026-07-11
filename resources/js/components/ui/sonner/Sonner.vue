<script lang="ts" setup>
import type { ToasterProps } from 'vue-sonner';
import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
    XIcon,
} from '@lucide/vue';
import { Toaster as Sonner } from 'vue-sonner';
import { cn } from '@/lib/utils';
import 'vue-sonner/style.css';

const props = withDefaults(defineProps<ToasterProps>(), {
    position: 'top-right',
    richColors: true,
    closeButton: true,
    duration: 4500,
    visibleToasts: 4,
});
</script>

<template>
    <Sonner
        :class="cn('toaster group', props.class)"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
            '--border-radius': '0.75rem',
        }"
        v-bind="props"
    >
        <template #success-icon><CircleCheckIcon class="size-4" /></template>
        <template #info-icon><InfoIcon class="size-4" /></template>
        <template #warning-icon><TriangleAlertIcon class="size-4" /></template>
        <template #error-icon><OctagonXIcon class="size-4" /></template>
        <template #loading-icon><Loader2Icon class="size-4 animate-spin" /></template>
        <template #close-icon><XIcon class="size-4" /></template>
    </Sonner>
</template>
