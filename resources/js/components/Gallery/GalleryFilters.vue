<script setup lang="ts">
import { SlidersHorizontal, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import type { GalleryOption, GallerySelectOption } from '@/types/gallery';

const props = defineProps<{
    collections: GalleryOption[];
    categories: GalleryOption[];
    tags: GalleryOption[];
    assetTypes: GallerySelectOption[];
    formats: GallerySelectOption[];
    categoryId: string;
    tagId: string;
    collectionId: string;
    aiGenerated: string;
    assetType: string;
    format: string;
    orientation: string;
    minWidth: string;
    minHeight: string;
    sort: string;
}>();

const emit = defineEmits<{
    'update:categoryId': [value: string];
    'update:tagId': [value: string];
    'update:collectionId': [value: string];
    'update:aiGenerated': [value: string];
    'update:assetType': [value: string];
    'update:format': [value: string];
    'update:orientation': [value: string];
    'update:minWidth': [value: string];
    'update:minHeight': [value: string];
    'update:sort': [value: string];
    apply: [];
    reset: [];
}>();

const mobileOpen = ref(false);
const activeCount = computed(() => [props.categoryId, props.tagId, props.collectionId, props.aiGenerated, props.assetType, props.format, props.orientation, props.minWidth, props.minHeight].filter(Boolean).length);

function selectValue(event: Event): string {
    return (event.target as HTMLSelectElement).value;
}
</script>

<template>
    <div class="border-b border-stone-200 bg-white/95 backdrop-blur dark:border-stone-800 dark:bg-stone-900/95">
        <div class="mx-auto max-w-[1440px] px-4 py-4 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between gap-3 lg:hidden">
                <button
                    type="button"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] dark:border-stone-700 dark:hover:bg-stone-800"
                    :aria-expanded="mobileOpen"
                    aria-controls="gallery-filter-panel"
                    @click="mobileOpen = !mobileOpen"
                >
                    <SlidersHorizontal class="h-4 w-4" aria-hidden="true" />
                    Filters
                    <span v-if="activeCount" class="rounded-full bg-[var(--brand-accent)] px-2 py-0.5 text-xs text-white">
                        {{ activeCount }}
                    </span>
                </button>

                <select
                    :value="sort"
                    class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                    aria-label="Sort marketplace assets"
                    @change="emit('update:sort', selectValue($event)); emit('apply')"
                >
                    <option value="relevance">Most Relevant</option>
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
                        : 'hidden lg:grid lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-[repeat(9,minmax(0,1fr))_190px_auto_auto]',
                ]"
            >
                <label class="sr-only" for="filter-asset-type">Asset type</label>
                <select id="filter-asset-type" :value="assetType" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:assetType', selectValue($event))">
                    <option value="">All Asset Types</option>
                    <option v-for="option in assetTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>

                <label class="sr-only" for="filter-format">File format</label>
                <select id="filter-format" :value="format" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:format', selectValue($event))">
                    <option value="">All Formats</option>
                    <option v-for="option in formats" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>

                <label class="sr-only" for="filter-category">Category</label>
                <select id="filter-category" :value="categoryId" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:categoryId', selectValue($event))">
                    <option value="">All Categories</option>
                    <option v-for="option in categories" :key="option.id" :value="String(option.id)">{{ option.name }}</option>
                </select>

                <label class="sr-only" for="filter-collection">Collection</label>
                <select id="filter-collection" :value="collectionId" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:collectionId', selectValue($event))">
                    <option value="">All Collections</option>
                    <option v-for="option in collections" :key="option.id" :value="String(option.id)">{{ option.name }}</option>
                </select>

                <label class="sr-only" for="filter-tag">Tag</label>
                <select id="filter-tag" :value="tagId" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:tagId', selectValue($event))">
                    <option value="">All Tags</option>
                    <option v-for="option in tags" :key="option.id" :value="String(option.id)">{{ option.name }}</option>
                </select>


                <label class="sr-only" for="filter-orientation">Orientation</label>
                <select id="filter-orientation" :value="orientation" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:orientation', selectValue($event))">
                    <option value="">Any Orientation</option>
                    <option value="landscape">Landscape</option>
                    <option value="portrait">Portrait</option>
                    <option value="square">Square</option>
                </select>

                <label class="sr-only" for="filter-min-width">Minimum width in pixels</label>
                <input id="filter-min-width" :value="minWidth" type="number" min="1" max="100000" inputmode="numeric" placeholder="Min width (px)" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @input="emit('update:minWidth', ($event.target as HTMLInputElement).value)" />

                <label class="sr-only" for="filter-min-height">Minimum height in pixels</label>
                <input id="filter-min-height" :value="minHeight" type="number" min="1" max="100000" inputmode="numeric" placeholder="Min height (px)" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @input="emit('update:minHeight', ($event.target as HTMLInputElement).value)" />

                <label class="sr-only" for="filter-source">Content source</label>
                <select id="filter-source" :value="aiGenerated" class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700" @change="emit('update:aiGenerated', selectValue($event))">
                    <option value="">Any Source</option>
                    <option value="0">Photography Only</option>
                    <option value="1">AI Generated Only</option>
                </select>

                <label class="sr-only" for="filter-sort">Sort assets</label>
                <select id="filter-sort" :value="sort" class="hidden h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm xl:block dark:border-stone-700" @change="emit('update:sort', selectValue($event))">
                    <option value="relevance">Most Relevant</option>
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="most_viewed">Most Viewed</option>
                    <option value="most_favorited">Most Favorited</option>
                    <option value="most_downloaded">Most Downloaded</option>
                </select>

                <button type="button" class="inline-flex h-11 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] focus-visible:ring-offset-2" @click="emit('apply'); mobileOpen = false">
                    Apply filters
                </button>

                <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] dark:border-stone-700 dark:hover:bg-stone-800" @click="emit('reset'); mobileOpen = false">
                    <X class="h-4 w-4" aria-hidden="true" />
                    Clear
                </button>
            </div>
        </div>
    </div>
</template>
