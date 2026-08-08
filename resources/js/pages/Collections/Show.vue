<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    computed,
    ref,
} from 'vue';
import PublicArticleCard from '@/components/Blog/PublicArticleCard.vue';
import CollectionHero from '@/components/Collections/CollectionHero.vue';
import CollectionToolbar from '@/components/Collections/CollectionToolbar.vue';
import RelatedCollectionCard from '@/components/Collections/RelatedCollectionCard.vue';
import GalleryEmpty from '@/components/Gallery/GalleryEmpty.vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import PublicActiveFilters from '@/components/Public/PublicActiveFilters.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicResultSummary from '@/components/Public/PublicResultSummary.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';
import type {
    CollectionArticle,
    CollectionFilters,
    CollectionHeroImage,
    CollectionImages,
    CollectionStatistics,
    PublicCollection,
    RelatedCollection,
} from '@/types/collection';
import type {
    PublicActiveFilter,
    PublicSearchSuggestion,
} from '@/types/publicSearch';



















const props = defineProps<{
    collection: PublicCollection;
    images: CollectionImages;
    heroImages: CollectionHeroImage[];
    statistics: CollectionStatistics;
    relatedCollections: RelatedCollection[];
    relatedArticles: CollectionArticle[];
    suggestions: PublicSearchSuggestion[];
    filters: CollectionFilters;
}>();

const search = ref(props.filters.search ?? '');
const sort = ref(props.filters.sort ?? 'curated');

const activeFilters = computed<PublicActiveFilter[]>(() => {
    const items: PublicActiveFilter[] = [];

    if (search.value) {
        items.push({
            key: 'search',
            label: `Search: ${search.value}`,
        });
    }

    if (sort.value !== 'curated') {
        const labels: Record<string, string> = {
            newest: 'Newest',
            oldest: 'Oldest',
            most_viewed: 'Most Viewed',
            most_favorited: 'Most Favorited',
            most_downloaded: 'Most Downloaded',
        };

        items.push({
            key: 'sort',
            label: `Sort: ${labels[sort.value] ?? sort.value}`,
        });
    }

    return items;
});

function reload(): void {
    router.get(`/collections/${props.collection.slug}`, {
        search: search.value || undefined,
        sort: sort.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters(): void {
    search.value = '';
    sort.value = 'curated';

    router.get(`/collections/${props.collection.slug}`, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function removeFilter(key: string): void {
    if (key === 'search') {
search.value = '';
}

    if (key === 'sort') {
sort.value = 'curated';
}

    reload();
}

function selectSuggestion(suggestion: PublicSearchSuggestion): void {
    search.value = suggestion.value;
    reload();
}
</script>

<template>
    <PublicSeoHead
        :title="collection.name"
        :description="
            collection.description
            || `Explore the ${collection.name} image collection.`
        "
        :image="heroImages[0]?.image_url ?? null"
        :canonical-path="`/collections/${collection.slug}`"
        :robots="
            activeFilters.length > 0 || images.current_page > 1
                ? 'noindex, follow, max-image-preview:large'
                : 'index, follow, max-image-preview:large'
        "
/>


    <StructuredData

        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Marketplace', url: '/images' },
            { name: collection.name, url: `/collections/${collection.slug}` },
        ]"

        :image="heroImages[0]?.image_url ?? null"

    />

    <PublicPageLayout>
        <CollectionHero
            :collection="collection"
            :images="heroImages"
            :statistics="statistics"
        />

        <CollectionToolbar
            v-model:search="search"
            v-model:sort="sort"
            :suggestions="suggestions"
            @apply="reload"
            @reset="resetFilters"
            @suggestion="selectSuggestion"
        />

        <section class="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-12 sm:px-8 lg:px-12 lg:py-16">
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <PublicResultSummary
                    :from="images.from"
                    :to="images.to"
                    :total="images.total"
                    item-label="images"
                    :filtered="activeFilters.length > 0"
                />

                <PublicActiveFilters
                    :items="activeFilters"
                    @remove="removeFilter"
                    @clear="resetFilters"
                />
            </div>

            <GalleryGrid
                v-if="images.data.length"
                :images="images.data"
            />

            <GalleryEmpty
                v-else
                @reset="resetFilters"
            />

            <PublicPagination
                class="mt-10"
                :pagination="images"
            />
        </section>

        <section
            v-if="relatedArticles.length"
            class="public-deferred-section border-y border-stone-200 bg-white py-16 dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <h2 class="text-3xl font-semibold tracking-tight">
                    Related Stories
                </h2>

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <PublicArticleCard
                        v-for="article in relatedArticles"
                        :key="article.id"
                        :post="article"
                    />
                </div>
            </div>
        </section>

        <section
            v-if="relatedCollections.length"
            class="public-deferred-section py-16"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <h2 class="text-3xl font-semibold tracking-tight">
                    Related Collections
                </h2>

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <RelatedCollectionCard
                        v-for="relatedCollection in relatedCollections"
                        :key="relatedCollection.id"
                        :collection="relatedCollection"
                    />
                </div>
            </div>
        </section>
    </PublicPageLayout>
</template>
