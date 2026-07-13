<script setup lang="ts">
import { SearchX, RotateCcw } from '@lucide/vue';

const emit = defineEmits<{
    reset: [];
}>();
</script>

<template>
    <div class="rounded-3xl border border-dashed border-stone-300 bg-stone-50/60 px-6 py-16 text-center dark:border-stone-700 dark:bg-stone-900/40">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-[var(--brand-accent)] shadow-sm dark:bg-stone-900">
            <SearchX class="h-8 w-8" aria-hidden="true" />
        </div>

        <h2 class="mt-6 text-2xl font-semibold">
            No assets match your search
        </h2>

        <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-stone-600 dark:text-stone-400">
            Try removing a filter, using a broader search phrase, or returning to the complete marketplace.
        </p>

        <button
            type="button"
            class="mt-7 inline-flex h-11 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] focus-visible:ring-offset-2"
            @click="emit('reset')"
        >
            <RotateCcw class="h-4 w-4" aria-hidden="true" />
            Clear filters
        </button>
    </div>
</template>
