<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Category {
    id: number;
    name: string;
    slug?: string;
}

interface Tag {
    id: number;
    name: string;
    slug?: string;
}

interface Author {
    id: number;
    name: string;
}

interface BlogPost {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;

    featured_image_url: string | null;
    header_image_url: string | null;
    icon_image_url: string | null;

    published_at: string | null;
    is_featured: boolean;

    author: Author | null;
    categories: Category[];
    tags: Tag[];
}

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
        }
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
        }
    );
}

function formatDate(date: string | null): string {
    if (!date) {
        return '';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function postImage(post: BlogPost): string | null {
    return post.featured_image_url ?? post.header_image_url ?? post.icon_image_url;
}
</script>

<template>
    <Head title="Blog" />

    <div class="min-h-screen bg-background">
        <section class="border-b bg-muted/30">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-primary">
                        Articles & Stories
                    </p>

                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                        Unclad Collection Blog
                    </h1>

                    <p class="mt-4 text-lg leading-8 text-muted-foreground">
                        Photography insights, nudist lifestyle articles, community stories,
                        and updates from Unclad Collection.
                    </p>
                </div>
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
                        v-if="postImage(heroPost)"
                        :src="postImage(heroPost)!"
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
                <Link
                    v-for="post in secondaryFeaturedPosts"
                    :key="post.id"
                    :href="`/blog/${post.slug}`"
                    class="group grid overflow-hidden rounded-xl border bg-card shadow-sm transition hover:shadow-md sm:grid-cols-3"
                >
                    <div class="overflow-hidden bg-muted sm:col-span-1">
                        <img
                            v-if="postImage(post)"
                            :src="postImage(post)!"
                            :alt="post.title"
                            class="aspect-[4/3] h-full w-full object-cover transition duration-300 group-hover:scale-105"
                        />

                        <div
                            v-else
                            class="flex aspect-[4/3] h-full w-full items-center justify-center bg-muted text-sm text-muted-foreground"
                        >
                            No image
                        </div>
                    </div>

                    <div class="p-5 sm:col-span-2">
                        <div class="mb-2 text-xs font-semibold uppercase text-primary">
                            Featured
                        </div>

                        <h3 class="line-clamp-2 text-xl font-bold group-hover:text-primary">
                            {{ post.title }}
                        </h3>

                        <p
                            v-if="post.excerpt"
                            class="mt-2 line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ post.excerpt }}
                        </p>

                        <div class="mt-4 text-xs text-muted-foreground">
                            {{ formatDate(post.published_at) }}
                        </div>
                    </div>
                </Link>
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
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">
                            Latest Articles
                        </h2>

                        <p class="text-sm text-muted-foreground">
                            Browse the newest posts from Unclad Collection.
                        </p>
                    </div>
                </div>

                <div
                    v-if="regularPosts.length"
                    class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="post in regularPosts"
                        :key="post.id"
                        :href="`/blog/${post.slug}`"
                        class="group overflow-hidden rounded-xl border bg-card shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="overflow-hidden bg-muted">
                            <img
                                v-if="postImage(post)"
                                :src="postImage(post)!"
                                :alt="post.title"
                                class="aspect-[16/9] w-full object-cover transition duration-300 group-hover:scale-105"
                            />

                            <div
                                v-else
                                class="flex aspect-[16/9] w-full items-center justify-center bg-muted text-sm text-muted-foreground"
                            >
                                No image
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="mb-3 flex flex-wrap gap-2">
                                <span
                                    v-if="post.is_featured"
                                    class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary"
                                >
                                    Featured
                                </span>

                                <span
                                    v-for="category in post.categories.slice(0, 2)"
                                    :key="category.id"
                                    class="rounded-full bg-muted px-2.5 py-1 text-xs"
                                >
                                    {{ category.name }}
                                </span>
                            </div>

                            <h3 class="line-clamp-2 text-xl font-bold group-hover:text-primary">
                                {{ post.title }}
                            </h3>

                            <p
                                v-if="post.excerpt"
                                class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
                            >
                                {{ post.excerpt }}
                            </p>

                            <div class="mt-5 flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    {{ post.author?.name ?? 'Unclad Collection' }}
                                </span>

                                <span>
                                    {{ formatDate(post.published_at) }}
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div
                    v-else
                    class="rounded-xl border bg-card p-10 text-center text-muted-foreground"
                >
                    No blog posts found.
                </div>
            </section>

            <div
                v-if="posts.links?.length"
                class="flex flex-wrap justify-center gap-2 pt-4"
            >
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url || ''"
                    v-html="link.label"
                    class="rounded-md border px-3 py-2 text-sm transition hover:bg-muted"
                    :class="{
                        'bg-primary text-primary-foreground hover:bg-primary': link.active,
                        'pointer-events-none opacity-50': !link.url,
                    }"
                />
            </div>
        </main>
    </div>
</template>