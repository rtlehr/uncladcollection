<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import {
    computed,
    ref,
} from 'vue';

import PublicSearchSuggestions from '@/components/Public/PublicSearchSuggestions.vue';

import type { PublicSearchSuggestion } from '@/types/publicSearch';

const search = defineModel<string>({ required: true });

const props = defineProps<{
    total: number;
    suggestions: PublicSearchSuggestion[];
}>();

const emit = defineEmits<{
    search: [];
    suggestion: [suggestion: PublicSearchSuggestion];
}>();

const focused = ref(false);

const showSuggestions = computed(() =>
    focused.value
    && search.value.trim().length >= 2
    && props.suggestions.length > 0,
);

function selectSuggestion(suggestion: PublicSearchSuggestion): void {
    search.value = suggestion.value;
    focused.value = false;
    emit('suggestion', suggestion);
}
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
                    Search articles by topic, category, tag, or author.
                </p>
            </div>

            <div class="relative mt-9 max-w-3xl">
                <div class="flex items-center gap-2 rounded-full border border-white/25 bg-white p-2 shadow-2xl shadow-black/15 dark:bg-stone-900">
                    <Search class="ml-3 h-5 w-5 shrink-0 text-stone-400" />

                    <label for="blog-search" class="sr-only">
                        Search articles
                    </label>

                    <input
                        id="blog-search"
                        v-model="search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search articles, topics, and authors..."
                        class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm text-stone-950 outline-none placeholder:text-stone-400 dark:text-white sm:text-base"
                        @focus="focused = true"
                        @blur="focused = false"
                        @keyup.enter="emit('search')"
                        @keydown.esc="focused = false"
                    />

                    <button
                        v-if="search"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-800"
                        aria-label="Clear article search"
                        @click="search = ''; emit('search')"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-11 shrink-0 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white ring-1 ring-white/20"
                        @click="emit('search')"
                    >
                        Search
                    </button>
                </div>

                <PublicSearchSuggestions
                    :suggestions="suggestions"
                    :visible="showSuggestions"
                    @select="selectSuggestion"
                />
            </div>

            <p class="mt-5 text-sm text-white/65">
                {{ Number(total ?? 0).toLocaleString() }} published articles
            </p>
        </div>
    </section>
</template>
