<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    computed,
    ref,
} from 'vue';

import PublicArticleCard from '@/components/Blog/PublicArticleCard.vue';
import PublicBlogFilters from '@/components/Blog/PublicBlogFilters.vue';
import PublicBlogHero from '@/components/Blog/PublicBlogHero.vue';
import PublicBlogPagination from '@/components/Blog/PublicBlogPagination.vue';
import PublicActiveFilters from '@/components/Public/PublicActiveFilters.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicResultSummary from '@/components/Public/PublicResultSummary.vue';

import type {
    BlogFilters,
    BlogPost,
    Category,
    PaginatedBlogPosts,
    Tag,
} from '@/types/blog';
import type {
    PublicActiveFilter,
    PublicSearchSuggestion,
} from '@/types/publicSearch';

const props = defineProps<{
    posts: PaginatedBlogPosts;
    featuredPosts: BlogPost[];
    categories: Category[];
    tags: Tag[];
    suggestions: PublicSearchSuggestion[];
    filters: BlogFilters;
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const tagId = ref(props.filters.tag_id ?? '');

const activeFilters = computed<PublicActiveFilter[]>(() => {
    const items: PublicActiveFilter[] = [];

    if (search.value) {
        items.push({
            key: 'search',
            label: `Search: ${search.value}`,
        });
    }

    const category = props.categories.find(
        (item) => String(item.id) === categoryId.value,
    );

    if (category) {
        items.push({
            key: 'category_id',
            label: `Category: ${category.name}`,
        });
    }

    const tag = props.tags.find(
        (item) => String(item.id) === tagId.value,
    );

    if (tag) {
        items.push({
            key: 'tag_id',
            label: `Tag: ${tag.name}`,
        });
    }

    return items;
});

const heroPost = computed(() =>
    props.featuredPosts[0] ?? props.posts.data[0] ?? null,
);

const secondaryFeaturedPosts = computed(() =>
    props.featuredPosts
        .filter((post) => post.id !== heroPost.value?.id)
        .slice(0, 2),
);

const regularPosts = computed(() =>
    props.posts.data.filter((post) =>
        post.id !== heroPost.value?.id
        && !secondaryFeaturedPosts.value.some(
            (featured) => featured.id === post.id,
        ),
    ),
);

function applyFilters(): void {
    router.get('/blog', {
        search: search.value || undefined,
        category_id: categoryId.value || undefined,
        tag_id: tagId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters(): void {
    search.value = '';
    categoryId.value = '';
    tagId.value = '';

    router.get('/blog', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function removeFilter(key: string): void {
    if (key === 'search') search.value = '';
    if (key === 'category_id') categoryId.value = '';
    if (key === 'tag_id') tagId.value = '';

    applyFilters();
}

function selectSuggestion(suggestion: PublicSearchSuggestion): void {
    if (suggestion.href) {
        router.visit(suggestion.href);
        return;
    }

    search.value = suggestion.value;
    applyFilters();
}
</script>

<template>
    <Head title="Stories" />

    <PublicPageLayout>
        <PublicBlogHero
            v-model="search"
            :total="posts.total"
            :suggestions="suggestions"
            @search="applyFilters"
            @suggestion="selectSuggestion"
        />

        <PublicBlogFilters
            v-model:category-id="categoryId"
            v-model:tag-id="tagId"
            :categories="categories"
            :tags="tags"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <main class="mx-auto max-w-[1440px] px-5 py-12 sm:px-8 lg:px-12 lg:py-16">
            <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <PublicResultSummary
                    :from="posts.from"
                    :to="posts.to"
                    :total="posts.total"
                    item-label="articles"
                    :filtered="activeFilters.length > 0"
                />

                <PublicActiveFilters
                    :items="activeFilters"
                    @remove="removeFilter"
                    @clear="resetFilters"
                />
            </div>

            <section v-if="heroPost && activeFilters.length === 0">
                <PublicArticleCard
                    :post="heroPost"
                    variant="hero"
                />
            </section>

            <section
                v-if="secondaryFeaturedPosts.length && activeFilters.length === 0"
                class="mt-8 grid gap-6 lg:grid-cols-2"
            >
                <PublicArticleCard
                    v-for="post in secondaryFeaturedPosts"
                    :key="post.id"
                    :post="post"
                    variant="horizontal"
                />
            </section>

            <section :class="activeFilters.length === 0 ? 'mt-16' : ''">
                <div
                    v-if="regularPosts.length"
                    class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                >
                    <PublicArticleCard
                        v-for="post in regularPosts"
                        :key="post.id"
                        :post="post"
                    />
                </div>

                <div
                    v-else
                    class="rounded-3xl border border-dashed border-stone-300 px-6 py-16 text-center dark:border-stone-700"
                >
                    <h2 class="text-xl font-semibold">
                        No articles matched your search
                    </h2>

                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                        Try a broader phrase, another category, or remove a filter.
                    </p>

                    <button
                        type="button"
                        class="mt-6 inline-flex h-11 items-center rounded-full border border-stone-300 px-5 text-sm font-semibold dark:border-stone-700"
                        @click="resetFilters"
                    >
                        Reset Filters
                    </button>
                </div>

                <PublicBlogPagination
                    class="mt-10"
                    :current-page="posts.current_page"
                    :last-page="posts.last_page"
                    :previous-url="posts.prev_page_url"
                    :next-url="posts.next_page_url"
                />
            </section>
        </main>
    </PublicPageLayout>
</template>
