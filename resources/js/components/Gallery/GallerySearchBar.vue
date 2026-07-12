<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import {
    computed,
    ref,
} from 'vue';

import PublicSearchSuggestions from '@/components/Public/PublicSearchSuggestions.vue';

import type { PublicSearchSuggestion } from '@/types/publicSearch';

const model = defineModel<string>({ required: true });

const props = withDefaults(defineProps<{
    placeholder?: string;
    suggestions?: PublicSearchSuggestion[];
}>(), {
    placeholder: 'Search images, categories, tags, collections, or photographers...',
    suggestions: () => [],
});

const emit = defineEmits<{
    search: [];
    suggestion: [suggestion: PublicSearchSuggestion];
}>();

const focused = ref(false);

const showSuggestions = computed(() =>
    focused.value
    && model.value.trim().length >= 2
    && props.suggestions.length > 0,
);

function selectSuggestion(suggestion: PublicSearchSuggestion): void {
    model.value = suggestion.value;
    focused.value = false;
    emit('suggestion', suggestion);
}
</script>

<template>
    <div class="relative">
        <div
            class="flex items-center gap-2 rounded-full border border-white/25 bg-white p-2 shadow-2xl shadow-black/15 dark:border-stone-700 dark:bg-stone-900"
            role="search"
        >
            <Search class="ml-3 h-5 w-5 shrink-0 text-stone-400" />

            <label for="gallery-search" class="sr-only">
                Search the image library
            </label>

            <input
                id="gallery-search"
                v-model="model"
                type="search"
                autocomplete="off"
                :placeholder="placeholder"
                class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm text-stone-950 outline-none placeholder:text-stone-400 dark:text-white sm:text-base"
                @focus="focused = true"
                @blur="focused = false"
                @keyup.enter="emit('search')"
                @keydown.esc="focused = false"
            />

            <button
                v-if="model"
                type="button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-stone-500 transition hover:bg-stone-100 dark:hover:bg-stone-800"
                aria-label="Clear search"
                @click="model = ''; emit('search')"
            >
                <X class="h-4 w-4" />
            </button>

            <button
                type="button"
                class="inline-flex h-11 shrink-0 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90"
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
</template>
