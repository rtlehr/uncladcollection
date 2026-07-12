<script setup lang="ts">
import {
    FolderOpen,
    Hash,
    Search,
    Tags,
    UserRound,
} from '@lucide/vue';

import type { PublicSearchSuggestion } from '@/types/publicSearch';

defineProps<{
    suggestions: PublicSearchSuggestion[];
    visible: boolean;
}>();

const emit = defineEmits<{
    select: [suggestion: PublicSearchSuggestion];
}>();

function suggestionIcon(type: PublicSearchSuggestion['type']) {
    switch (type) {
        case 'collection':
            return FolderOpen;
        case 'category':
            return Tags;
        case 'tag':
            return Hash;
        case 'photographer':
        case 'author':
            return UserRound;
        default:
            return Search;
    }
}
</script>

<template>
    <div
        v-if="visible && suggestions.length"
        class="absolute inset-x-0 top-full z-40 mt-2 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-800 dark:bg-stone-900"
        role="listbox"
        aria-label="Search suggestions"
    >
        <button
            v-for="suggestion in suggestions"
            :key="`${suggestion.type}-${suggestion.value}`"
            type="button"
            class="flex w-full items-center gap-3 border-b border-stone-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-stone-50 dark:border-stone-800 dark:hover:bg-stone-800"
            role="option"
            @mousedown.prevent="emit('select', suggestion)"
        >
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-500 dark:bg-stone-800">
                <component
                    :is="suggestionIcon(suggestion.type)"
                    class="h-4 w-4"
                />
            </span>

            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium">
                    {{ suggestion.label }}
                </span>

                <span
                    v-if="suggestion.meta"
                    class="block truncate text-xs text-stone-500 dark:text-stone-400"
                >
                    {{ suggestion.meta }}
                </span>
            </span>

            <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-400">
                {{ suggestion.type }}
            </span>
        </button>
    </div>
</template>
