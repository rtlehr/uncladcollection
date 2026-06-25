<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();

const isAuthenticated = computed(() => {
    return !!page.props.auth?.user;
});

const siteSettings = computed(() => {
    return page.props.siteSettings ?? {};
});

const primaryColor = computed(() => {
    return siteSettings.value.primary_color ?? '#f53003';
});

const secondaryColor = computed(() => {
    return siteSettings.value.secondary_color ?? '#1b1b18';
});

const siteTagline = computed(() => {
    return siteSettings.value.site_tagline ?? 'Licensed imagery for the nudist community.';
});

const contactEmail = computed(() => {
    return siteSettings.value.contact_email ?? '';
});

const xAccountUrl = computed(() => {
    return siteSettings.value.x_account_url ?? '';
});

interface BlogArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image_url: string | null;
    header_image_url: string | null;
    icon_image_url: string | null;
    published_at: string | null;
    is_featured: boolean;

    author?: {
        id: number;
        name: string;
    } | null;

    categories?: Array<{
        id: number;
        name: string;
    }>;
}

defineProps<{
    latestArticles: BlogArticle[];
}>();

function articleImage(article: BlogArticle): string | null {
    return article.featured_image_url
        ?? article.header_image_url
        ?? article.icon_image_url;
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

function articleHref(article: BlogArticle): string {
    return isAuthenticated.value
        ? `/blog/${article.slug}`
        : `/login?redirect=/blog/${article.slug}`;
}
</script>

<template>
    <Head title="Unclad Collection" />

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <header class="border-b bg-white/80 backdrop-blur dark:border-[#2a2a27] dark:bg-[#0a0a0a]/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-xl font-bold tracking-tight">
                    Unclad Collection
                </Link>

                <nav class="flex items-center gap-4 text-sm">
                    <Link href="/images" class="hover:text-[#f53003]">
                        Images
                    </Link>

                    <Link href="/blog" class="hover:text-[#f53003]">
                        Blog
                    </Link>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md border px-4 py-2 hover:bg-muted dark:border-[#3E3E3A]"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link :href="login()" class="hover:text-[#f53003]">
                            Log in
                        </Link>

                        <Link
                            :href="register()"
                            class="rounded-md bg-[#1b1b18] px-4 py-2 text-white hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A]"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto grid max-w-7xl gap-10 px-6 py-20 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="mb-4 text-sm font-semibold uppercase tracking-wide text-[#f53003]">
                        Licensed imagery for the nudist community
                    </p>

                    <h1 class="text-5xl font-bold tracking-tight sm:text-6xl">
                        Natural, respectful, community-focused photography.
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-8 text-[#706f6c] dark:text-[#A1A09A]">
                        {{ siteTagline }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            href="/images"
                            class="rounded-md bg-[#1b1b18] px-5 py-3 text-sm font-medium text-white hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A]"
                        >
                            Browse Images
                        </Link>

                        <Link
                            href="/blog"
                            class="rounded-md border px-5 py-3 text-sm font-medium hover:bg-white dark:border-[#3E3E3A] dark:hover:bg-[#161615]"
                        >
                            Read Articles
                        </Link>
                    </div>
                </div>

                <div class="rounded-3xl border bg-white p-4 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-[#f7efe8] to-[#e8d7c8] dark:from-[#2a201b] dark:to-[#161615]">
                        <div class="flex h-full items-center justify-center p-10 text-center">
                            <div>
                                <div class="mx-auto mb-5 h-24 w-24 rounded-full bg-[#f53003]/15"></div>

                                <h2 class="text-2xl font-bold">
                                    Featured Image Area
                                </h2>

                                <p class="mt-3 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    Later we can replace this with a featured image, collection, or rotating hero.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y bg-white py-12 dark:border-[#2a2a27] dark:bg-[#111110]">
                <div class="mx-auto grid max-w-7xl gap-6 px-6 md:grid-cols-4">
                    <div class="rounded-xl border p-6 dark:border-[#3E3E3A]">
                        <div class="text-3xl font-bold">Curated</div>
                        <div class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Image collections
                        </div>
                    </div>

                    <div class="rounded-xl border p-6 dark:border-[#3E3E3A]">
                        <div class="text-3xl font-bold">Licensed</div>
                        <div class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Digital downloads
                        </div>
                    </div>

                    <div class="rounded-xl border p-6 dark:border-[#3E3E3A]">
                        <div class="text-3xl font-bold">Articles</div>
                        <div class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Community resources
                        </div>
                    </div>

                    <div class="rounded-xl border p-6 dark:border-[#3E3E3A]">
                        <div class="text-3xl font-bold">Members</div>
                        <div class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Favorites and purchases
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-6 py-16">
                <div class="mb-8 flex items-end justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-[#f53003]">
                            Explore
                        </p>

                        <h2 class="mt-2 text-3xl font-bold">
                            Featured Collections
                        </h2>

                        <p class="mt-3 max-w-2xl text-[#706f6c] dark:text-[#A1A09A]">
                            Placeholder cards for curated image collections. We can wire these to real collections later.
                        </p>
                    </div>

                    <Link href="/images" class="text-sm font-medium text-[#f53003] hover:underline">
                        Browse all →
                    </Link>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <div class="aspect-[16/10] rounded-xl bg-[#f7efe8] dark:bg-[#2a201b]"></div>
                        <h3 class="mt-4 text-xl font-bold">Resort Lifestyle</h3>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Comfortable, welcoming visual content for naturist destinations.
                        </p>
                    </div>

                    <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <div class="aspect-[16/10] rounded-xl bg-[#f7efe8] dark:bg-[#2a201b]"></div>
                        <h3 class="mt-4 text-xl font-bold">Community & Events</h3>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Visuals that represent safe, social, body-positive community life.
                        </p>
                    </div>

                    <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <div class="aspect-[16/10] rounded-xl bg-[#f7efe8] dark:bg-[#2a201b]"></div>
                        <h3 class="mt-4 text-xl font-bold">Nature & Wellness</h3>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Calm, natural imagery for articles, marketing, and member content.
                        </p>
                    </div>
                </div>
            </section>

            <section class="border-y bg-white py-16 dark:border-[#2a2a27] dark:bg-[#111110]">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="mb-8 flex items-end justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-[#f53003]">
                                Image Library
                            </p>

                            <h2 class="mt-2 text-3xl font-bold">
                                Featured Images
                            </h2>

                            <p class="mt-3 max-w-2xl text-[#706f6c] dark:text-[#A1A09A]">
                                Placeholder area for featured or newest images. Later we can connect this to active images from the database.
                            </p>
                        </div>

                        <Link href="/images" class="text-sm font-medium text-[#f53003] hover:underline">
                            View images →
                        </Link>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="item in 4"
                            :key="item"
                            class="overflow-hidden rounded-2xl border bg-[#f7efe8] shadow-sm dark:border-[#3E3E3A] dark:bg-[#2a201b]"
                        >
                            <div class="aspect-[4/5]"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="latestArticles.length"
                class="mx-auto max-w-7xl px-6 py-16"
            >
                <div class="mb-8 flex items-end justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-[#f53003]">
                            From the Blog
                        </p>

                        <h2 class="mt-2 text-3xl font-bold">
                            Latest Articles
                        </h2>

                        <p class="mt-3 max-w-2xl text-[#706f6c] dark:text-[#A1A09A]">
                            Read the newest stories, updates, and photography insights from Unclad Collection.
                        </p>
                    </div>

                    <Link
                        href="/blog"
                        class="hidden text-sm font-medium text-[#f53003] hover:underline sm:inline"
                    >
                        View all →
                    </Link>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <Link
                        v-for="article in latestArticles"
                        :key="article.id"
                        :href="articleHref(article)"
                        class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <div class="overflow-hidden bg-[#f7efe8] dark:bg-[#2a201b]">
                            <img
                                v-if="articleImage(article)"
                                :src="articleImage(article)!"
                                :alt="article.title"
                                class="aspect-[16/9] w-full object-cover transition duration-300 group-hover:scale-105"
                            />

                            <div
                                v-else
                                class="flex aspect-[16/9] items-center justify-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                No image
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="mb-3 flex flex-wrap gap-2">
                                <span
                                    v-if="article.is_featured"
                                    class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-semibold text-[#f53003]"
                                >
                                    Featured
                                </span>

                                <span
                                    v-for="category in article.categories?.slice(0, 2)"
                                    :key="category.id"
                                    class="rounded-full bg-[#f7efe8] px-2.5 py-1 text-xs dark:bg-[#2a201b]"
                                >
                                    {{ category.name }}
                                </span>
                            </div>

                            <h3 class="line-clamp-2 text-xl font-bold group-hover:text-[#f53003]">
                                {{ article.title }}
                            </h3>

                            <p
                                v-if="article.excerpt"
                                class="mt-3 line-clamp-3 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ article.excerpt }}
                            </p>

                            <div class="mt-5 flex items-center justify-between text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                <span>
                                    {{ article.author?.name ?? 'Unclad Collection' }}
                                </span>

                                <span>
                                    {{ formatDate(article.published_at) }}
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div class="mt-8 text-center sm:hidden">
                    <Link
                        href="/blog"
                        class="text-sm font-medium text-[#f53003] hover:underline"
                    >
                        View all articles →
                    </Link>
                </div>
            </section>

            <section class="bg-[#1b1b18] py-16 text-white dark:bg-[#161615]">
                <div class="mx-auto max-w-4xl px-6 text-center">
                    <h2 class="text-3xl font-bold">
                        Start building your personal collection.
                    </h2>

                    <p class="mt-4 text-white/70">
                        Create an account to save favorites, purchase licensed downloads, and follow new articles.
                    </p>

                    <div class="mt-8 flex justify-center gap-3">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="register()"
                            class="rounded-md bg-white px-5 py-3 text-sm font-medium text-[#1b1b18] hover:bg-white/90"
                        >
                            Create Account
                        </Link>

                        <Link
                            href="/images"
                            class="rounded-md border border-white/30 px-5 py-3 text-sm font-medium hover:bg-white/10"
                        >
                            Browse Images
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>