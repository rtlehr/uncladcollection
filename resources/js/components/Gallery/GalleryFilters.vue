<script setup lang="ts">
import { SlidersHorizontal, X } from '@lucide/vue';
import {
    computed,
    ref,
} from 'vue';

import type { GalleryOption } from '@/types/gallery';

const props = defineProps<{
    collections: GalleryOption[];
    categories: GalleryOption[];
    tags: GalleryOption[];
    categoryId: string;
    tagId: string;
    collectionId: string;
    aiGenerated: string;
    sort: string;
}>();

const emit = defineEmits<{
    'update:categoryId': [value: string];
    'update:tagId': [value: string];
    'update:collectionId': [value: string];
    'update:aiGenerated': [value: string];
    'update:sort': [value: string];
    apply: [];
    reset: [];
}>();

const mobileOpen = ref(false);

const activeCount = computed(() => [
    props.categoryId,
    props.tagId,
    props.collectionId,
    props.aiGenerated,
].filter(Boolean).length);
</script>

<template>
    <div class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto max-w-[1440px] px-5 py-4 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between gap-3 lg:hidden">
                <button
                    type="button"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold dark:border-stone-700"
                    :aria-expanded="mobileOpen"
                    aria-controls="gallery-filter-panel"
                    @click="mobileOpen = !mobileOpen"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                    Filters

                    <span
                        v-if="activeCount"
                        class="rounded-full bg-[var(--brand-accent)] px-2 py-0.5 text-xs text-white"
                    >
                        {{ activeCount }}
                    </span>
                </button>

                <select
                    :value="sort"
                    class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                    aria-label="Sort images"
                    @change="emit('update:sort', ($event.target as HTMLSelectElement).value); emit('apply')"
                >
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="most_viewed">Most Viewed</option>
                    <option value="most_favorited">Most Favorited</option>
                    <option value="most_downloaded">Most Downloaded</option>
                </select>
            </div>

            <div
                id="gallery-filter-panel"
                :class="[
                    'gap-3',
                    mobileOpen
                        ? 'mt-4 grid'
                        : 'hidden lg:grid lg:grid-cols-[repeat(4,minmax(0,1fr))_220px_auto_auto]',
                ]"
            >
                <select
                    :value="categoryId"
                    class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                    aria-label="Filter by category"
                    @change="emit('update:categoryId', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">All Categories</option>
                    <option v-for="option in categories" :key="option.id" :value="String(option.id)">
                        {{ option.name }}
                    </option>
                </select>

                <select
                    :value="collectionId"
                    class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                    aria-label="Filter by collection"
                    @change="emit('update:collectionId', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">All Collections</option>
                    <option v-for="option in collections" :key="option.id" :value="String(option.id)">
                        {{ option.name }}
                    </option>
                </select>

                <select
                    :value="tagId"
                    class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                    aria-label="Filter by tag"
                    @change="emit('update:tagId', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">All Tags</option>
                    <option v-for="option in tags" :key="option.id" :value="String(option.id)">
                        {{ option.name }}
                    </option>
                </select>

                <select
                    :value="aiGenerated"
                    class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                    aria-label="Filter by image source"
                    @change="emit('update:aiGenerated', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Any Source</option>
                    <option value="0">Photography Only</option>
                    <option value="1">AI Generated Only</option>
                </select>

                <select
                    :value="sort"
                    class="hidden h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm lg:block dark:border-stone-700"
                    aria-label="Sort images"
                    @change="emit('update:sort', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="most_viewed">Most Viewed</option>
                    <option value="most_favorited">Most Favorited</option>
                    <option value="most_downloaded">Most Downloaded</option>
                </select>

                <button
                    type="button"
                    class="inline-flex h-11 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                    @click="emit('apply'); mobileOpen = false"
                >
                    Apply
                </button>

                <button
                    type="button"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold dark:border-stone-700"
                    @click="emit('reset'); mobileOpen = false"
                >
                    <X class="h-4 w-4" />
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>
