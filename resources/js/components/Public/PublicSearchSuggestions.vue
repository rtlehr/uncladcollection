<script setup lang="ts">
import {
    Clock3,
    FolderOpen,
    Hash,
    ImageIcon,
    Search,
    Tags,
    TrendingUp,
    UserRound,
} from '@lucide/vue';

import type { PublicSearchSuggestion } from '@/types/publicSearch';

withDefaults(defineProps<{
    suggestions: PublicSearchSuggestion[];
    visible: boolean;
    activeIndex?: number;
    loading?: boolean;
    listboxId?: string;
}>(), {
    activeIndex: -1,
    loading: false,
    listboxId: 'public-search-suggestions',
});

const emit = defineEmits<{
    select: [suggestion: PublicSearchSuggestion];
    activate: [index: number];
}>();

function suggestionIcon(type: PublicSearchSuggestion['type']) {
    switch (type) {
        case 'collection': return FolderOpen;
        case 'category': return Tags;
        case 'tag': return Hash;
        case 'creator':
        case 'photographer':
        case 'author': return UserRound;
        case 'asset': return ImageIcon;
        case 'recent': return Clock3;
        case 'popular': return TrendingUp;
        default: return Search;
    }
}
</script>

<template>
    <div
        v-if="visible"
        :id="listboxId"
        class="absolute inset-x-0 top-full z-40 mt-2 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-800 dark:bg-stone-900"
        role="listbox"
        aria-label="Search suggestions"
    >
        <div v-if="loading && !suggestions.length" class="px-4 py-4 text-sm text-stone-500" role="status">
            Loading suggestions…
        </div>

        <button
            v-for="(suggestion, index) in suggestions"
            :id="`${listboxId}-option-${index}`"
            :key="`${suggestion.type}-${suggestion.value}`"
            type="button"
            class="flex w-full items-center gap-3 border-b border-stone-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-stone-50 focus:bg-stone-50 focus:outline-none dark:border-stone-800 dark:hover:bg-stone-800 dark:focus:bg-stone-800"
            :class="index === activeIndex ? 'bg-stone-50 dark:bg-stone-800' : ''"
            role="option"
            :aria-selected="index === activeIndex"
            @mouseenter="emit('activate', index)"
            @mousedown.prevent="emit('select', suggestion)"
        >
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-500 dark:bg-stone-800">
                <component :is="suggestionIcon(suggestion.type)" class="h-4 w-4" />
            </span>

            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium">{{ suggestion.label }}</span>
                <span v-if="suggestion.meta" class="block truncate text-xs text-stone-500 dark:text-stone-400">
                    {{ suggestion.meta }}
                </span>
            </span>

            <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-400">
                {{ suggestion.type }}
            </span>
        </button>

        <div v-if="!loading && !suggestions.length" class="px-4 py-4 text-sm text-stone-500" role="status">
            Keep typing to search titles, categories, tags, collections, and creators.
        </div>
    </div>
</template>
