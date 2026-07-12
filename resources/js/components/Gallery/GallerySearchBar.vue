<script setup lang="ts">
import { Search, X } from '@lucide/vue';

const model = defineModel<string>({ required: true });

withDefaults(defineProps<{
    placeholder?: string;
}>(), {
    placeholder: 'Search images, categories, tags, or photographers...',
});

const emit = defineEmits<{
    search: [];
}>();
</script>

<template>
    <div
        class="flex items-center gap-2 rounded-full border border-white/25 bg-white p-2 shadow-2xl shadow-black/15 dark:border-stone-700 dark:bg-stone-900"
        role="search"
    >
        <Search class="ml-3 h-5 w-5 shrink-0 text-stone-400" aria-hidden="true" />

        <label for="gallery-search" class="sr-only">
            Search the image library
        </label>

        <input
            id="gallery-search"
            v-model="model"
            type="search"
            :placeholder="placeholder"
            class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none placeholder:text-stone-400 sm:text-base"
            @keyup.enter="emit('search')"
        />

        <button
            v-if="model"
            type="button"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-stone-500 transition hover:bg-stone-100 dark:hover:bg-stone-800"
            aria-label="Clear search"
            @click="model = ''"
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
</template>
