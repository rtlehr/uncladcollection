<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import GalleryActiveFilters from '@/components/Gallery/GalleryActiveFilters.vue';
import GalleryEmpty from '@/components/Gallery/GalleryEmpty.vue';
import GalleryFilters from '@/components/Gallery/GalleryFilters.vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import GalleryHero from '@/components/Gallery/GalleryHero.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';

import type {
    GalleryFilters as GalleryFilterState,
    GalleryOption,
    PaginatedGalleryImages,
} from '@/types/gallery';

const props = defineProps<{
    images: PaginatedGalleryImages;
    collections: GalleryOption[];
    categories: GalleryOption[];
    tags: GalleryOption[];
    filters: GalleryFilterState;
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const tagId = ref(props.filters.tag_id ?? '');
const collectionId = ref(props.filters.collection_id ?? '');
const aiGenerated = ref(props.filters.ai_generated ?? '');
const sort = ref(props.filters.sort ?? 'newest');

const activeFilters = computed(() => {
    const items: Array<{ key: string; label: string }> = [];

    if (search.value) {
        items.push({
            key: 'search',
            label: `Search: ${search.value}`,
        });
    }

    if (categoryId.value) {
        const category = props.categories.find(
            (item) => String(item.id) === String(categoryId.value),
        );

        if (category) {
            items.push({
                key: 'category_id',
                label: `Category: ${category.name}`,
            });
        }
    }

    if (collectionId.value) {
        const collection = props.collections.find(
            (item) => String(item.id) === String(collectionId.value),
        );

        if (collection) {
            items.push({
                key: 'collection_id',
                label: `Collection: ${collection.name}`,
            });
        }
    }

    if (tagId.value) {
        const tag = props.tags.find(
            (item) => String(item.id) === String(tagId.value),
        );

        if (tag) {
            items.push({
                key: 'tag_id',
                label: `Tag: ${tag.name}`,
            });
        }
    }

    if (aiGenerated.value === '1') {
        items.push({
            key: 'ai_generated',
            label: 'AI Generated',
        });
    }

    if (aiGenerated.value === '0') {
        items.push({
            key: 'ai_generated',
            label: 'Photography Only',
        });
    }

    return items;
});

function reload(): void {
    router.get(
        '/images',
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            tag_id: tagId.value || undefined,
            collection_id: collectionId.value || undefined,
            ai_generated: aiGenerated.value || undefined,
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
    categoryId.value = '';
    tagId.value = '';
    collectionId.value = '';
    aiGenerated.value = '';
    sort.value = 'newest';

    router.get(
        '/images',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function removeFilter(key: string): void {
    switch (key) {
        case 'search':
            search.value = '';
            break;
        case 'category_id':
            categoryId.value = '';
            break;
        case 'tag_id':
            tagId.value = '';
            break;
        case 'collection_id':
            collectionId.value = '';
            break;
        case 'ai_generated':
            aiGenerated.value = '';
            break;
    }

    reload();
}
</script>

<template>
    <Head title="Image Library" />

    <PublicPageLayout>
        <GalleryHero
            v-model:search="search"
            :total="images.total"
            @search="reload"
        />

        <GalleryFilters
            v-model:category-id="categoryId"
            v-model:tag-id="tagId"
            v-model:collection-id="collectionId"
            v-model:ai-generated="aiGenerated"
            v-model:sort="sort"
            :collections="collections"
            :categories="categories"
            :tags="tags"
            @apply="reload"
            @reset="resetFilters"
        />

        <section class="mx-auto max-w-[1440px] px-5 py-10 sm:px-8 lg:px-12 lg:py-14">
            <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Showing {{ images.from ?? 0 }}–{{ images.to ?? 0 }} of
                        {{ images.total.toLocaleString() }} images
                    </p>

                    <h2 class="mt-1 text-2xl font-semibold tracking-tight">
                        {{
                            activeFilters.length
                                ? 'Filtered results'
                                : 'Discover the collection'
                        }}
                    </h2>
                </div>

                <GalleryActiveFilters
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
    </PublicPageLayout>
</template>
