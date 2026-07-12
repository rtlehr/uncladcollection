<script setup lang="ts">
import { CircleCheck, Info, TriangleAlert } from '@lucide/vue';

withDefaults(defineProps<{
    title?: string | null;
    tone?: 'info' | 'success' | 'warning';
}>(), {
    title: null,
    tone: 'info',
});

const tones = {
    info: 'border-sky-500/25 bg-sky-500/10 text-sky-950 dark:text-sky-100',
    success: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-950 dark:text-emerald-100',
    warning: 'border-amber-500/25 bg-amber-500/10 text-amber-950 dark:text-amber-100',
};
</script>

<template>
    <aside :class="['rounded-2xl border p-5 sm:p-6', tones[tone]]">
        <div class="flex gap-4">
            <Info v-if="tone === 'info'" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
            <CircleCheck v-else-if="tone === 'success'" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
            <TriangleAlert v-else class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />

            <div class="min-w-0">
                <h3 v-if="title" class="font-semibold">{{ title }}</h3>
                <div class="prose prose-sm mt-2 max-w-none text-current prose-p:leading-7 dark:prose-invert">
                    <slot />
                </div>
            </div>
        </div>
    </aside>
</template>
