<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AssetCard from '@/Components/Assets/AssetCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import type {
    AssetOption,
    PaginatedAssets,
} from '@/types/asset';

const props = defineProps<{
    images: PaginatedAssets;
    collections: AssetOption[];
    categories: AssetOption[];
    tags: AssetOption[];
    filters: {
        search: string;
        category_id: string;
        tag_id: string;
        collection_id: string;
        ai_generated: string;
        sort: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const tagId = ref(props.filters.tag_id ?? '');
const collectionId = ref(props.filters.collection_id ?? '');
const aiGenerated = ref(props.filters.ai_generated ?? '');
const sort = ref(props.filters.sort ?? 'newest');

function reload() {
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

function resetFilters() {
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
</script>

<template>
    <Head title="Images" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Image Library"
            description="Search and browse images available from Unclad Collection."
        />

        <section class="rounded-lg border bg-card p-6 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <Input
                        v-model="search"
                        placeholder="Search images, tags, categories..."
                        @keyup.enter="reload"
                    />
                </div>

                <select
                    v-model="categoryId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">All Categories</option>

                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>

                <select
                    v-model="tagId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">All Tags</option>

                    <option
                        v-for="tag in tags"
                        :key="tag.id"
                        :value="tag.id"
                    >
                        {{ tag.name }}
                    </option>
                </select>

                <select
                    v-model="collectionId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">All Collections</option>

                    <option
                        v-for="collection in collections"
                        :key="collection.id"
                        :value="collection.id"
                    >
                        {{ collection.name }}
                    </option>
                </select>

                <select
                    v-model="sort"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="most_viewed">Most Viewed</option>
                    <option value="most_favorited">Most Favorited</option>
                    <option value="most_downloaded">Most Downloaded</option>
                </select>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <select
                    v-model="aiGenerated"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">AI Generated: Any</option>
                    <option value="1">AI Generated Only</option>
                    <option value="0">Non-AI Only</option>
                </select>

                <Button type="button" @click="reload">
                    Search
                </Button>

                <Button type="button" variant="outline" @click="resetFilters">
                    Reset
                </Button>
            </div>
        </section>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <div>
                Showing
                <span class="font-medium">{{ images.from ?? 0 }}</span>
                -
                <span class="font-medium">{{ images.to ?? 0 }}</span>
                of
                <span class="font-medium">{{ images.total }}</span>
                images
            </div>
        </div>

        <div
            v-if="images.data.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <AssetCard
                v-for="image in images.data"
                :key="image.id"
                :asset="image"
            />
        </div>

        <EmptyState
            v-else
            title="No images matched your search"
            description="Try changing or resetting the current filters."
        >
            <template #actions>
                <Button type="button" variant="outline" @click="resetFilters">
                    Reset Filters
                </Button>
            </template>
        </EmptyState>

        <Pagination
            :links="images.links"
            :from="images.from"
            :to="images.to"
            :total="images.total"
            item-label="images"
            show-summary
        />
    </div>
</template>
