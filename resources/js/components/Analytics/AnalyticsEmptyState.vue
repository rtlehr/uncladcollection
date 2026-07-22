<script setup lang="ts">
import { BarChart3 } from '@lucide/vue';

withDefaults(defineProps<{ title?: string; description?: string; compact?: boolean }>(), {
    title: 'No report data',
    description: 'No activity was recorded for the selected filters and reporting period.',
    compact: false,
});
</script>

<template>
    <div :class="['flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/10 text-center', compact ? 'px-4 py-8' : 'px-6 py-12']" role="status">
        <div class="rounded-full bg-muted p-3"><BarChart3 class="h-5 w-5 text-muted-foreground" aria-hidden="true" /></div>
        <h3 class="mt-3 text-sm font-semibold">{{ title }}</h3>
        <p class="mt-1 max-w-md text-sm text-muted-foreground">{{ description }}</p>
        <slot />
    </div>
</template>
