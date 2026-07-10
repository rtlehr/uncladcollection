<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import BlogPostCard from '@/Components/Blog/BlogPostCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';

import { contentImage } from '@/lib/contentImages';
import { formatDate } from '@/lib/formatDate';

import type { BlogPost, Category, Tag } from '@/types/blog';

const props = defineProps<{
    posts: {
        data: BlogPost[];
        links: any[];
    };

    featuredPosts: BlogPost[];

    categories: Category[];
    tags: Tag[];

    filters: {
        search?: string;
        category_id?: number | null;
        tag_id?: number | null;
    };
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id?.toString() ?? '');
const tagId = ref(props.filters.tag_id?.toString() ?? '');

const heroPost = computed(() => props.featuredPosts[0] ?? props.posts.data[0] ?? null);

const secondaryFeaturedPosts = computed(() => {
    if (!heroPost.value) {
        return props.featuredPosts.slice(0, 2);
    }

    return props.featuredPosts
        .filter((post) => post.id !== heroPost.value?.id)
        .slice(0, 2);
});

const regularPosts = computed(() => {
    if (!heroPost.value) {
        return props.posts.data;
    }

    return props.posts.data.filter((post) => post.id !== heroPost.value?.id);
});

function applyFilters() {
    router.get(
        '/blog',
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            tag_id: tagId.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function clearFilters() {
    search.value = '';
    categoryId.value = '';
    tagId.value = '';

    router.get(
        '/blog',
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
    <Head title="Blog" />

    <div class="min-h-screen bg-background">
        <section class="border-b bg-muted/30">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <PageHeader
                    eyebrow="Articles & Stories"
                    title="Unclad Collection Blog"
                    description="Photography insights, nudist lifestyle articles, community stories, and updates from Unclad Collection."
                    align="center"
                />
            </div>
        </section>

        <main class="mx-auto max-w-7xl space-y-12 px-4 py-10 sm:px-6 lg:px-8">
            <section
                v-if="heroPost"
                class="grid overflow-hidden rounded-2xl border bg-card shadow-sm lg:grid-cols-5"
            >
                <Link
                    :href="`/blog/${heroPost.slug}`"
                    class="block overflow-hidden bg-muted lg:col-span-3"
                >
                    <img
                        v-if="contentImage(heroPost)"
                        :src="contentImage(heroPost)!"
                        :alt="heroPost.title"
                        class="aspect-[16/10] h-full w-full object-cover transition duration-300 hover:scale-105 lg:aspect-auto"
                    />

                    <div
                        v-else
                        class="flex aspect-[16/10] h-full w-full items-center justify-center bg-muted text-muted-foreground"
                    >
                        No image
                    </div>
                </Link>

                <div class="flex flex-col justify-center p-6 lg:col-span-2 lg:p-10">
                    <div class="mb-4 flex flex-wrap gap-2">
                        <span
                            v-if="heroPost.is_featured"
                            class="rounded-full bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground"
                        >
                            Featured
                        </span>

                        <span
                            v-for="category in heroPost.categories.slice(0, 2)"
                            :key="category.id"
                            class="rounded-full bg-muted px-3 py-1 text-xs font-medium"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <Link :href="`/blog/${heroPost.slug}`">
                        <h2 class="text-3xl font-bold tracking-tight hover:text-primary lg:text-4xl">
                            {{ heroPost.title }}
                        </h2>
                    </Link>

                    <p
                        v-if="heroPost.excerpt"
                        class="mt-4 line-clamp-4 text-base leading-7 text-muted-foreground"
                    >
                        {{ heroPost.excerpt }}
                    </p>

                    <div class="mt-6 flex items-center justify-between gap-4 text-sm text-muted-foreground">
                        <div>
                            <span class="font-medium text-foreground">
                                {{ heroPost.author?.name ?? 'Unclad Collection' }}
                            </span>

                            <span v-if="heroPost.published_at">
                                · {{ formatDate(heroPost.published_at) }}
                            </span>
                        </div>

                        <Link
                            :href="`/blog/${heroPost.slug}`"
                            class="font-medium text-primary hover:underline"
                        >
                            Read article →
                        </Link>
                    </div>
                </div>
            </section>

            <section
                v-if="secondaryFeaturedPosts.length"
                class="grid gap-6 md:grid-cols-2"
            >
                <BlogPostCard
                    v-for="post in secondaryFeaturedPosts"
                    :key="post.id"
                    :post="post"
                    variant="featured"
                    :show-author="false"
                />
            </section>

            <section class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-4">
                    <input
                        v-model="search"
                        placeholder="Search articles..."
                        class="h-10 rounded-md border bg-background px-3 text-sm md:col-span-2"
                        @keyup.enter="applyFilters"
                    />

                    <select
                        v-model="categoryId"
                        class="h-10 rounded-md border bg-background px-3 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">
                            All Categories
                        </option>

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
                        class="h-10 rounded-md border bg-background px-3 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">
                            All Tags
                        </option>

                        <option
                            v-for="tag in tags"
                            :key="tag.id"
                            :value="tag.id"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-md border px-4 py-2 text-sm hover:bg-muted"
                        @click="clearFilters"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                        @click="applyFilters"
                    >
                        Search
                    </button>
                </div>
            </section>

            <section>
                <SectionHeader
                    title="Latest Articles"
                    description="Browse the newest posts from Unclad Collection."
                />

                <div
                    v-if="regularPosts.length"
                    class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <BlogPostCard
                        v-for="post in regularPosts"
                        :key="post.id"
                        :post="post"
                    />
                </div>

                <EmptyState
                    v-else
                    title="No blog posts found"
                    description="Try changing your search, category, or tag filters."
                />
            </section>

            <Pagination
                :links="posts.links"
                item-label="articles"
            />
        </main>
    </div>
</template>
