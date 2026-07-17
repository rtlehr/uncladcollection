<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, RotateCcw, SearchX } from '@lucide/vue';

const emit = defineEmits<{
    reset: [];
}>();
</script>

<template>
    <div class="relative overflow-hidden rounded-[2rem] border border-dashed border-stone-300 bg-gradient-to-br from-stone-50 to-white px-6 py-16 text-center shadow-sm dark:border-stone-700 dark:from-stone-900 dark:to-stone-950">
        <div class="absolute left-1/2 top-0 h-36 w-72 -translate-x-1/2 rounded-full bg-[var(--brand-accent)]/10 blur-3xl" />

        <div class="relative">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-stone-200 bg-white text-[var(--brand-accent)] shadow-sm dark:border-stone-700 dark:bg-stone-900">
                <SearchX class="h-8 w-8" aria-hidden="true" />
            </div>

            <h2 class="mt-6 text-2xl font-semibold tracking-tight">
                No matching assets found
            </h2>

            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-stone-600 dark:text-stone-400">
                Try a broader search, remove one or more filters, or explore a curated collection for inspiration.
            </p>

            <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <button
                    type="button"
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] focus-visible:ring-offset-2"
                    @click="emit('reset')"
                >
                    <RotateCcw class="h-4 w-4" aria-hidden="true" />
                    Clear filters
                </button>

                <Link
                    href="/collections"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-stone-300 bg-white px-5 text-sm font-semibold text-stone-900 transition hover:-translate-y-0.5 hover:border-stone-400 hover:shadow-sm dark:border-stone-700 dark:bg-stone-900 dark:text-white"
                >
                    Browse collections
                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </div>
</template>
