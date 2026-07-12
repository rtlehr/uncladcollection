<script setup lang="ts">
import { Images, RotateCcw } from '@lucide/vue';

const emit = defineEmits<{
    reset: [];
}>();
</script>

<template>
    <div class="public-empty-state public-rise-enter rounded-3xl border border-dashed border-stone-300 px-6 py-16 text-center dark:border-stone-700">
        <div class="public-soft-ring mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-[var(--brand-accent)] dark:bg-stone-900">
            <Images class="h-7 w-7" />
        </div>

        <h2 class="mt-5 text-xl font-semibold">
            No images matched your search
        </h2>

        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-400">
            Try a broader search term, remove one or more filters, or start again with the complete library.
        </p>

        <button
            type="button"
            class="mt-6 inline-flex h-11 items-center gap-2 rounded-full border border-stone-300 px-5 text-sm font-semibold dark:border-stone-700"
            @click="emit('reset')"
        >
            <RotateCcw class="h-4 w-4" />
            Reset filters
        </button>
    </div>
</template>
