<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Option = {
    id: number;
    name: string;
};

type ImageCard = {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    is_ai_generated: boolean;
    favorites_count: number;
    downloads_count: number;
    purchases_count: number;
    views_count: number;
    collection: Option | null;
    categories: Option[];
    tags: Option[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedImages = {
    data: ImageCard[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    images: PaginatedImages;
    collections: Option[];
    categories: Option[];
    tags: Option[];
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
    router.get('/images', {
        search: search.value,
        category_id: categoryId.value,
        tag_id: tagId.value,
        collection_id: collectionId.value,
        ai_generated: aiGenerated.value,
        sort: sort.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    categoryId.value = '';
    tagId.value = '';
    collectionId.value = '';
    aiGenerated.value = '';
    sort.value = 'newest';

    router.get('/images', {}, {
        preserveState: true,
        replace: true,
    });
}

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}
</script>

<template>
    <Head title="Images" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">Image Library</h1>

            <p class="text-sm text-muted-foreground">
                Search and browse images available from Unclad Collection.
            </p>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
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
        </div>

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
            <Link
                v-for="image in images.data"
                :key="image.id"
                :href="`/images/${image.slug}`"
                class="group overflow-hidden rounded-lg border bg-card shadow-sm transition hover:shadow-md"
            >
                <div class="aspect-square bg-muted">
                    <img
                        v-if="image.thumbnail_url"
                        :src="image.thumbnail_url"
                        :alt="image.title"
                        class="h-full w-full object-cover transition group-hover:scale-105"
                    />

                    <div
                        v-else
                        class="flex h-full items-center justify-center text-sm text-muted-foreground"
                    >
                        No preview
                    </div>
                </div>

                <div class="space-y-3 p-4">
                    <div>
                        <h2 class="line-clamp-1 font-semibold">
                            {{ image.title }}
                        </h2>

                        <p class="line-clamp-1 text-xs text-muted-foreground">
                            {{ image.collection?.name ?? 'Unassigned' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-if="image.is_ai_generated"
                            class="rounded-full border px-2 py-0.5 text-xs"
                        >
                            AI
                        </span>

                        <span
                            v-for="category in image.categories.slice(0, 2)"
                            :key="category.id"
                            class="rounded-full border px-2 py-0.5 text-xs"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>{{ formatNumber(image.views_count) }} views</span>
                        <span>{{ formatNumber(image.favorites_count) }} favorites</span>
                    </div>
                </div>
            </Link>
        </div>

        <div
            v-else
            class="rounded-lg border bg-card p-12 text-center text-muted-foreground"
        >
            No images matched your search.
        </div>

        <div
            v-if="images.links.length > 3"
            class="flex flex-wrap justify-center gap-2"
        >
            <Link
                v-for="link in images.links"
                :key="link.label"
                :href="link.url ?? '#'"
                preserve-scroll
                class="rounded-md border px-3 py-2 text-sm"
                :class="[
                    link.active ? 'bg-primary text-primary-foreground' : '',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>