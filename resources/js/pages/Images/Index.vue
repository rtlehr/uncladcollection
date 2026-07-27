<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import GalleryEmpty from '@/components/Gallery/GalleryEmpty.vue';
import GalleryFilters from '@/components/Gallery/GalleryFilters.vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import GalleryHero from '@/components/Gallery/GalleryHero.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import PublicAdPlacement from '@/components/Advertising/PublicAdPlacement.vue';
import PublicActiveFilters from '@/components/Public/PublicActiveFilters.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';
import PublicResultSummary from '@/components/Public/PublicResultSummary.vue';
import type { GalleryFilters as GalleryFilterState, GalleryOption, GallerySelectOption, PaginatedGalleryAssets } from '@/types/gallery';
import type { PublicActiveFilter, PublicSearchSuggestion } from '@/types/publicSearch';

const props = defineProps<{
    assets: PaginatedGalleryAssets;
    collections: GalleryOption[];
    categories: GalleryOption[];
    tags: GalleryOption[];
    assetTypes: GallerySelectOption[];
    formats: GallerySelectOption[];
    suggestions: PublicSearchSuggestion[];
    filters: GalleryFilterState;
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const tagId = ref(props.filters.tag_id ?? '');
const collectionId = ref(props.filters.collection_id ?? '');
const aiGenerated = ref(props.filters.ai_generated ?? '');
const assetType = ref(props.filters.asset_type ?? '');
const format = ref(props.filters.format ?? '');
const orientation = ref(props.filters.orientation ?? '');
const minWidth = ref(props.filters.min_width ?? '');
const minHeight = ref(props.filters.min_height ?? '');
const sort = ref(props.filters.sort ?? (search.value ? 'relevance' : 'newest'));

const activeFilters = computed<PublicActiveFilter[]>(() => {
    const items: PublicActiveFilter[] = [];
    if (search.value) items.push({ key: 'search', label: `Search: ${search.value}` });
    const category = props.categories.find((item) => String(item.id) === categoryId.value);
    if (category) items.push({ key: 'category_id', label: `Category: ${category.name}` });
    const collection = props.collections.find((item) => String(item.id) === collectionId.value);
    if (collection) items.push({ key: 'collection_id', label: `Collection: ${collection.name}` });
    const tag = props.tags.find((item) => String(item.id) === tagId.value);
    if (tag) items.push({ key: 'tag_id', label: `Tag: ${tag.name}` });
    const type = props.assetTypes.find((item) => item.value === assetType.value);
    if (type) items.push({ key: 'asset_type', label: `Type: ${type.label}` });
    const selectedFormat = props.formats.find((item) => item.value === format.value);
    if (selectedFormat) items.push({ key: 'format', label: `Format: ${selectedFormat.label}` });
    if (aiGenerated.value === '1') items.push({ key: 'ai_generated', label: 'AI Generated' });
    if (aiGenerated.value === '0') items.push({ key: 'ai_generated', label: 'Photography Only' });
    if (orientation.value) items.push({ key: 'orientation', label: `Orientation: ${orientation.value}` });
    if (minWidth.value) items.push({ key: 'min_width', label: `Minimum width: ${minWidth.value}px` });
    if (minHeight.value) items.push({ key: 'min_height', label: `Minimum height: ${minHeight.value}px` });
    return items;
});

function queryPayload() {
    return {
        search: search.value || undefined,
        category_id: categoryId.value || undefined,
        tag_id: tagId.value || undefined,
        collection_id: collectionId.value || undefined,
        ai_generated: aiGenerated.value || undefined,
        asset_type: assetType.value || undefined,
        format: format.value || undefined,
        orientation: orientation.value || undefined,
        min_width: minWidth.value || undefined,
        min_height: minHeight.value || undefined,
        sort: sort.value || undefined,
    };
}

function reload(): void {
    router.get('/images', queryPayload(), { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters(): void {
    search.value = ''; categoryId.value = ''; tagId.value = ''; collectionId.value = ''; aiGenerated.value = ''; assetType.value = ''; format.value = ''; orientation.value = ''; minWidth.value = ''; minHeight.value = ''; sort.value = 'newest';
    router.get('/images', {}, { preserveState: true, preserveScroll: true, replace: true });
}

function removeFilter(key: string): void {
    if (key === 'search') search.value = '';
    if (key === 'category_id') categoryId.value = '';
    if (key === 'tag_id') tagId.value = '';
    if (key === 'collection_id') collectionId.value = '';
    if (key === 'ai_generated') aiGenerated.value = '';
    if (key === 'asset_type') assetType.value = '';
    if (key === 'format') format.value = '';
    if (key === 'orientation') orientation.value = '';
    if (key === 'min_width') minWidth.value = '';
    if (key === 'min_height') minHeight.value = '';
    reload();
}

function selectSuggestion(suggestion: PublicSearchSuggestion): void {
    if (suggestion.href) {
        const url = new URL(suggestion.href, window.location.origin);
        url.searchParams.set('suggestion_type', suggestion.type);
        router.visit(`${url.pathname}${url.search}`);
        return;
    }

    search.value = suggestion.value;
    router.get('/images', {
        ...queryPayload(),
        suggestion_type: suggestion.type,
    }, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <PublicSeoHead
        title="Digital Asset Library"
        description="Browse authentic, respectful, licensed naturist and nudist lifestyle images, vectors, video, and downloadable media."
        canonical-path="/images"
        :robots="activeFilters.length > 0 || assets.current_page > 1 ? 'noindex, follow, max-image-preview:large' : 'index, follow, max-image-preview:large'"
    />
    <StructuredData :breadcrumbs="[{ name: 'Home', url: '/' }, { name: 'Assets', url: '/images' }]" />

    <PublicPageLayout>
        <GalleryHero v-model:search="search" :total="assets.total" :suggestions="suggestions" @search="reload" @suggestion="selectSuggestion" />
        <GalleryFilters
            v-model:category-id="categoryId"
            v-model:tag-id="tagId"
            v-model:collection-id="collectionId"
            v-model:ai-generated="aiGenerated"
            v-model:asset-type="assetType"
            v-model:format="format"
            v-model:orientation="orientation"
            v-model:min-width="minWidth"
            v-model:min-height="minHeight"
            v-model:sort="sort"
            :collections="collections"
            :categories="categories"
            :tags="tags"
            :asset-types="assetTypes"
            :formats="formats"
            @apply="reload"
            @reset="resetFilters"
        />

        <section class="mx-auto max-w-[1440px] px-4 py-8 sm:px-8 sm:py-10 lg:px-12 lg:py-14">
            <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <PublicResultSummary :from="assets.from" :to="assets.to" :total="assets.total" item-label="assets" :filtered="activeFilters.length > 0" />
                <PublicActiveFilters :items="activeFilters" @remove="removeFilter" @clear="resetFilters" />
            </div>
            <PublicAdPlacement placement="asset-gallery-inline" class="mb-8" />
            <GalleryGrid v-if="assets.data.length" :assets="assets.data" />
            <GalleryEmpty v-else @reset="resetFilters" />
            <PublicPagination class="mt-10" :pagination="assets" />
        </section>
    </PublicPageLayout>
</template>
