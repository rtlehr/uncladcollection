<script setup lang="ts">
import { Search } from '@lucide/vue';

const search = defineModel<string>({ required: true });

defineProps<{
    total: number;
}>();

const emit = defineEmits<{
    search: [];
}>();
</script>

<template>
    <section class="relative overflow-hidden bg-[var(--brand-primary)] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,color-mix(in_srgb,var(--brand-accent)_22%,transparent),transparent_32%),radial-gradient(circle_at_86%_10%,color-mix(in_srgb,var(--brand-secondary)_38%,transparent),transparent_38%)]" />

        <div class="relative mx-auto max-w-[1440px] px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
            <div class="max-w-4xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-accent)]">
                    Stories, ideas, and experience
                </p>

                <h1 class="mt-5 text-4xl font-semibold leading-[1.06] tracking-[-0.04em] sm:text-6xl">
                    Thoughtful writing for a more natural way of living
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-8 text-white/75 sm:text-lg">
                    Explore personal experiences, practical guidance, advocacy, resort insights, and community perspectives from Unclad Collection.
                </p>
            </div>

            <div class="mt-9 max-w-3xl">
                <div class="flex items-center gap-2 rounded-full border border-white/25 bg-white p-2 shadow-2xl shadow-black/15 dark:bg-stone-900">
                    <Search class="ml-3 h-5 w-5 shrink-0 text-stone-400" />

                    <label for="blog-search" class="sr-only">
                        Search articles
                    </label>

                    <input
                        id="blog-search"
                        v-model="search"
                        type="search"
                        placeholder="Search articles, topics, and ideas..."
                        class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm text-stone-950 outline-none placeholder:text-stone-400 dark:text-white sm:text-base"
                        @keyup.enter="emit('search')"
                    />

                    <button
                        type="button"
                        class="inline-flex h-11 shrink-0 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:opacity-90"
                        @click="emit('search')"
                    >
                        Search
                    </button>
                </div>
            </div>

            <p class="mt-5 text-sm text-white/65">
                {{ Number(total ?? 0).toLocaleString() }} published articles
            </p>
        </div>
    </section>
</template>
