<script setup lang="ts">
import {
    Search,
    X,
} from '@lucide/vue';
import {
    computed,
    ref,
} from 'vue';

import PublicSearchSuggestions from '@/components/Public/PublicSearchSuggestions.vue';

import type { PublicSearchSuggestion } from '@/types/publicSearch';

const search = defineModel<string>('search', { required: true });
const sort = defineModel<string>('sort', { required: true });

const props = withDefaults(defineProps<{
    suggestions?: PublicSearchSuggestion[];
}>(), {
    suggestions: () => [],
});

const emit = defineEmits<{
    apply: [];
    reset: [];
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
    <div class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto grid max-w-[1440px] gap-3 px-5 py-4 sm:px-8 md:grid-cols-[minmax(0,1fr)_220px_auto_auto] lg:px-12">
            <div class="relative">
                <div class="flex h-11 items-center gap-2 rounded-full border border-stone-300 px-4 dark:border-stone-700">
                    <Search class="h-4 w-4 shrink-0 text-stone-400" />

                    <label for="collection-search" class="sr-only">
                        Search this collection
                    </label>

                    <input
                        id="collection-search"
                        v-model="search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search this collection..."
                        class="min-w-0 flex-1 border-0 bg-transparent text-sm outline-none"
                        @focus="focused = true"
                        @blur="focused = false"
                        @keyup.enter="emit('apply')"
                        @keydown.esc="focused = false"
                    />
                </div>

                <PublicSearchSuggestions
                    :suggestions="suggestions"
                    :visible="showSuggestions"
                    @select="selectSuggestion"
                />
            </div>

            <select
                v-model="sort"
                class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                aria-label="Sort collection images"
                @change="emit('apply')"
            >
                <option value="curated">Curated Order</option>
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="most_viewed">Most Viewed</option>
                <option value="most_favorited">Most Favorited</option>
                <option value="most_downloaded">Most Downloaded</option>
            </select>

            <button
                type="button"
                class="inline-flex h-11 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                @click="emit('apply')"
            >
                Apply
            </button>

            <button
                type="button"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-full border border-stone-300 px-5 text-sm font-semibold dark:border-stone-700"
                @click="emit('reset')"
            >
                <X class="h-4 w-4" />
                Reset
            </button>
        </div>
    </div>
</template>
