<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import PublicArticleCard from '@/components/Blog/PublicArticleCard.vue';
import CollectionHero from '@/components/Collections/CollectionHero.vue';
import CollectionToolbar from '@/components/Collections/CollectionToolbar.vue';
import RelatedCollectionCard from '@/components/Collections/RelatedCollectionCard.vue';
import GalleryEmpty from '@/components/Gallery/GalleryEmpty.vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';

import type {
    CollectionArticle,
    CollectionFilters,
    CollectionHeroImage,
    CollectionImages,
    CollectionStatistics,
    PublicCollection,
    RelatedCollection,
} from '@/types/collection';

const props = defineProps<{
    collection: PublicCollection;
    images: CollectionImages;
    heroImages: CollectionHeroImage[];
    statistics: CollectionStatistics;
    relatedCollections: RelatedCollection[];
    relatedArticles: CollectionArticle[];
    filters: CollectionFilters;
}>();

const search = ref(props.filters.search ?? '');
const sort = ref(props.filters.sort ?? 'curated');

function reload(): void {
    router.get(
        `/collections/${props.collection.slug}`,
        {
            search: search.value || undefined,
            sort: sort.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetFilters(): void {
    search.value = '';
    sort.value = 'curated';

    router.get(
        `/collections/${props.collection.slug}`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}
</script>

<template>
    <Head>
        <title>{{ collection.name }}</title>

        <meta
            name="description"
            :content="
                collection.description
                || `Explore the ${collection.name} image collection.`
            "
        />

        <meta property="og:type" content="website" />
        <meta property="og:title" :content="collection.name" />

        <meta
            property="og:description"
            :content="
                collection.description
                || `Explore the ${collection.name} image collection.`
            "
        />

        <meta
            v-if="heroImages[0]?.image_url"
            property="og:image"
            :content="heroImages[0].image_url!"
        />
    </Head>

    <PublicPageLayout>
        <CollectionHero
            :collection="collection"
            :images="heroImages"
            :statistics="statistics"
        />

        <CollectionToolbar
            v-model:search="search"
            v-model:sort="sort"
            @apply="reload"
            @reset="resetFilters"
        />

        <section class="mx-auto max-w-[1440px] px-5 py-12 sm:px-8 lg:px-12 lg:py-16">
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                        Curated gallery
                    </p>

                    <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Images in {{ collection.name }}
                    </h2>
                </div>

                <p class="text-sm text-stone-500 dark:text-stone-400">
                    Showing {{ images.from ?? 0 }}–{{ images.to ?? 0 }}
                    of {{ images.total.toLocaleString() }}
                </p>
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
            class="border-y border-stone-200 bg-white py-16 dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                        Stories and context
                    </p>

                    <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Related Articles
                    </h2>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-600 dark:text-stone-400">
                        Read community perspectives, practical guidance, and editorial content connected to this collection.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
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
            class="py-16"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                        Continue exploring
                    </p>

                    <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Related Collections
                    </h2>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
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
