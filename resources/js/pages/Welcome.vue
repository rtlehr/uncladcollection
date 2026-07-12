<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    AtSign,
    BookOpen,
    Camera,
    Check,
    Globe2,
    Heart,
    Image as ImageIcon,
    Menu,
    Mountain,
    Search,
    Users,
    Video,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

import { dashboard, login, register } from '@/routes';

import type {
    HomeArticle,
    HomeCollection,
    HomeHeroImage,
    HomeImage,
    HomeStatistics,
    PublicSiteSettings,
} from '@/types/home';

const props = defineProps<{
    siteSettings: PublicSiteSettings;
    heroImage: HomeHeroImage | null;
    featuredCollections: HomeCollection[];
    featuredImages: HomeImage[];
    latestArticles: HomeArticle[];
    statistics: HomeStatistics;
}>();

const page = usePage();
const mobileMenuOpen = ref(false);

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const general = computed(() => props.siteSettings.general ?? {});
const branding = computed(() => props.siteSettings.branding ?? {});
const homepage = computed(() => props.siteSettings.homepage ?? {});
const social = computed(() => props.siteSettings.social ?? {});
const seo = computed(() => props.siteSettings.seo ?? {});

const siteName = computed(() =>
    stringSetting(general.value.site_name, 'Unclad Collection'),
);

const siteTagline = computed(() =>
    stringSetting(
        general.value.site_tagline,
        'Licensed imagery and thoughtful stories for the nudist community.',
    ),
);

const logoUrl = computed(() =>
    stringSetting(branding.value.site_logo, ''),
);

const primaryColor = computed(() =>
    stringSetting(branding.value.primary_color, '#1E2A38'),
);

const secondaryColor = computed(() =>
    stringSetting(branding.value.secondary_color, '#50634D'),
);

const accentColor = computed(() =>
    stringSetting(branding.value.accent_color, '#D9824B'),
);

const pageStyle = computed(() => ({
    '--brand-primary': primaryColor.value,
    '--brand-secondary': secondaryColor.value,
    '--brand-accent': accentColor.value,
}));

const socialLinks = computed(() => [
    {
        label: 'Instagram',
        href: stringSetting(social.value.instagram_url, ''),
        icon: Camera,
    },
    {
        label: 'Facebook',
        href: stringSetting(social.value.facebook_url, ''),
        icon: Globe2,
    },
    {
        label: 'YouTube',
        href: stringSetting(social.value.youtube_url, ''),
        icon: Video,
    },
    {
        label: 'X',
        href: stringSetting(social.value.x_account_url, ''),
        icon: AtSign,
    },
].filter((item) => item.href));

function stringSetting(
    value: unknown,
    fallback: string,
): string {
    return typeof value === 'string' && value.trim()
        ? value
        : fallback;
}

function booleanSetting(
    value: unknown,
    fallback = true,
): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'string') {
        return value === 'true' || value === '1';
    }

    return fallback;
}

function articleImage(article: HomeArticle): string | null {
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

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}

function collectionHref(collection: HomeCollection): string {
    return `/images?collection_id=${collection.id}`;
}

function closeMobileMenu(): void {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <Head>
        <title>
            {{ stringSetting(seo.default_meta_title, siteName) }}
        </title>

        <meta
            name="description"
            :content="
                stringSetting(
                    seo.default_meta_description,
                    siteTagline,
                )
            "
        />
    </Head>

    <div
        :style="pageStyle"
        class="min-h-screen bg-stone-50 text-stone-950 dark:bg-stone-950 dark:text-stone-50"
    >
        <a
            href="#main-content"
            class="fixed left-4 top-3 z-[100] -translate-y-20 rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white transition-transform focus:translate-y-0"
        >
            Skip to content
        </a>

        <header class="sticky top-0 z-50 border-b border-stone-200/80 bg-stone-50/90 backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/90">
            <div class="mx-auto flex h-18 max-w-[1440px] items-center justify-between px-5 sm:px-8 lg:px-12">
                <Link
                    href="/"
                    class="flex min-w-0 items-center gap-3"
                    aria-label="Unclad Collection home"
                >
                    <img
                        v-if="logoUrl"
                        :src="logoUrl"
                        :alt="`${siteName} logo`"
                        class="h-10 max-w-52 object-contain"
                    />

                    <span
                        v-else
                        class="text-xl font-semibold tracking-tight"
                    >
                        {{ siteName }}
                    </span>
                </Link>

                <nav
                    class="hidden items-center gap-8 text-sm font-medium lg:flex"
                    aria-label="Primary navigation"
                >
                    <Link
                        href="/images"
                        class="transition hover:text-[var(--brand-accent)]"
                    >
                        Images
                    </Link>

                    <Link
                        href="/blog"
                        class="transition hover:text-[var(--brand-accent)]"
                    >
                        Stories
                    </Link>

                    <Link
                        v-if="isAuthenticated"
                        href="/favorites"
                        class="transition hover:text-[var(--brand-accent)]"
                    >
                        Favorites
                    </Link>
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="inline-flex h-10 items-center rounded-full border border-stone-300 px-5 text-sm font-medium transition hover:bg-white dark:border-stone-700 dark:hover:bg-stone-900"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="login()"
                            class="px-3 py-2 text-sm font-medium transition hover:text-[var(--brand-accent)]"
                        >
                            Log in
                        </Link>

                        <Link
                            :href="register()"
                            class="inline-flex h-10 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-medium text-white transition hover:opacity-90"
                        >
                            Join the community
                        </Link>
                    </template>
                </div>

                <button
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-stone-300 lg:hidden dark:border-stone-700"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-navigation"
                    aria-label="Toggle navigation"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <X v-if="mobileMenuOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
            </div>

            <div
                v-if="mobileMenuOpen"
                id="mobile-navigation"
                class="border-t border-stone-200 bg-stone-50 px-5 py-5 lg:hidden dark:border-stone-800 dark:bg-stone-950"
            >
                <nav class="grid gap-2" aria-label="Mobile navigation">
                    <Link
                        href="/images"
                        class="rounded-lg px-3 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                        @click="closeMobileMenu"
                    >
                        Images
                    </Link>

                    <Link
                        href="/blog"
                        class="rounded-lg px-3 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                        @click="closeMobileMenu"
                    >
                        Stories
                    </Link>

                    <Link
                        v-if="isAuthenticated"
                        href="/favorites"
                        class="rounded-lg px-3 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                        @click="closeMobileMenu"
                    >
                        Favorites
                    </Link>

                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="mt-2 rounded-full bg-[var(--brand-primary)] px-5 py-3 text-center font-medium text-white"
                        @click="closeMobileMenu"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg px-3 py-3 font-medium"
                            @click="closeMobileMenu"
                        >
                            Log in
                        </Link>

                        <Link
                            :href="register()"
                            class="rounded-full bg-[var(--brand-primary)] px-5 py-3 text-center font-medium text-white"
                            @click="closeMobileMenu"
                        >
                            Join the community
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main id="main-content">
            <section class="relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,color-mix(in_srgb,var(--brand-accent)_14%,transparent),transparent_32%),radial-gradient(circle_at_90%_10%,color-mix(in_srgb,var(--brand-secondary)_16%,transparent),transparent_34%)]" />

                <div class="relative mx-auto grid max-w-[1440px] gap-12 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[0.92fr_1.08fr] lg:items-center lg:px-12 lg:py-24">
                    <div class="max-w-2xl">
                        <p class="mb-5 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                            {{
                                stringSetting(
                                    homepage.hero_eyebrow,
                                    'Authentic imagery. Thoughtful stories.',
                                )
                            }}
                        </p>

                        <h1 class="text-4xl font-semibold leading-[1.06] tracking-[-0.035em] sm:text-6xl lg:text-7xl">
                            {{
                                stringSetting(
                                    homepage.hero_title,
                                    'A more natural way to represent nudist life.',
                                )
                            }}
                        </h1>

                        <p class="mt-6 max-w-xl text-base leading-8 text-stone-600 sm:text-lg dark:text-stone-300">
                            {{
                                stringSetting(
                                    homepage.hero_description,
                                    siteTagline,
                                )
                            }}
                        </p>

                        <form
                            action="/images"
                            method="get"
                            class="mt-8 flex max-w-xl items-center gap-2 rounded-full border border-stone-300 bg-white p-2 shadow-lg shadow-stone-900/5 dark:border-stone-700 dark:bg-stone-900"
                            role="search"
                        >
                            <Search
                                class="ml-3 h-5 w-5 shrink-0 text-stone-400"
                                aria-hidden="true"
                            />

                            <label for="hero-search" class="sr-only">
                                Search images
                            </label>

                            <input
                                id="hero-search"
                                name="search"
                                type="search"
                                placeholder="Search the image library..."
                                class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none placeholder:text-stone-400"
                            />

                            <button
                                type="submit"
                                class="inline-flex h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-medium text-white transition hover:opacity-90"
                            >
                                Search
                            </button>
                        </form>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                :href="
                                    stringSetting(
                                        homepage.hero_primary_button_url,
                                        '/images',
                                    )
                                "
                                class="inline-flex h-12 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-6 text-sm font-medium text-white transition hover:-translate-y-0.5 hover:opacity-90"
                            >
                                {{
                                    stringSetting(
                                        homepage.hero_primary_button_label,
                                        'Browse Images',
                                    )
                                }}

                                <ArrowRight class="h-4 w-4" />
                            </Link>

                            <Link
                                :href="
                                    stringSetting(
                                        homepage.hero_secondary_button_url,
                                        '/blog',
                                    )
                                "
                                class="inline-flex h-12 items-center rounded-full border border-stone-300 bg-white/70 px-6 text-sm font-medium transition hover:bg-white dark:border-stone-700 dark:bg-stone-900/70 dark:hover:bg-stone-900"
                            >
                                {{
                                    stringSetting(
                                        homepage.hero_secondary_button_label,
                                        'Read the Blog',
                                    )
                                }}
                            </Link>
                        </div>
                    </div>

                    <Link
                        v-if="heroImage?.image_url"
                        :href="`/images/${heroImage.slug}`"
                        class="group relative overflow-hidden rounded-[2rem] bg-stone-200 shadow-2xl shadow-stone-900/15 dark:bg-stone-800"
                    >
                        <img
                            :src="heroImage.image_url"
                            :alt="heroImage.title"
                            fetchpriority="high"
                            decoding="async"
                            class="aspect-[4/3] h-full w-full object-cover transition duration-700 group-hover:scale-[1.025]"
                        />

                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent p-6 pt-24 text-white sm:p-8">
                            <p class="text-xs font-semibold uppercase tracking-widest text-white/70">
                                Featured image
                            </p>

                            <h2 class="mt-2 text-2xl font-semibold">
                                {{ heroImage.title }}
                            </h2>

                            <p
                                v-if="heroImage.photographer"
                                class="mt-1 text-sm text-white/75"
                            >
                                Photography by {{ heroImage.photographer }}
                            </p>
                        </div>
                    </Link>

                    <div
                        v-else
                        class="relative flex aspect-[4/3] items-end overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,color-mix(in_srgb,var(--brand-secondary)_75%,white),color-mix(in_srgb,var(--brand-primary)_85%,black))] p-8 text-white shadow-2xl"
                    >
                        <div>
                            <Mountain class="h-10 w-10" />
                            <h2 class="mt-5 text-3xl font-semibold">
                                Natural. Respectful. Real.
                            </h2>
                            <p class="mt-3 max-w-md text-white/75">
                                Select a hero image from Site Settings after adding images to the library.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="booleanSetting(homepage.show_statistics)"
                class="border-y border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="mx-auto grid max-w-[1440px] divide-y divide-stone-200 px-5 sm:grid-cols-2 sm:divide-x sm:divide-y-0 sm:px-8 lg:grid-cols-4 lg:px-12 dark:divide-stone-800">
                    <div class="px-4 py-8 sm:px-7">
                        <div class="text-3xl font-semibold">
                            {{ formatNumber(statistics.images) }}+
                        </div>
                        <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Licensed images
                        </div>
                    </div>

                    <div class="px-4 py-8 sm:px-7">
                        <div class="text-3xl font-semibold">
                            {{ formatNumber(statistics.collections) }}
                        </div>
                        <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Curated collections
                        </div>
                    </div>

                    <div class="px-4 py-8 sm:px-7">
                        <div class="text-3xl font-semibold">
                            {{ formatNumber(statistics.articles) }}
                        </div>
                        <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Community articles
                        </div>
                    </div>

                    <div class="px-4 py-8 sm:px-7">
                        <div class="text-3xl font-semibold">
                            {{ formatNumber(statistics.downloads) }}
                        </div>
                        <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Licensed downloads
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-[1440px] px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
                <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                            Why Unclad Collection
                        </p>

                        <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">
                            {{
                                stringSetting(
                                    homepage.why_title,
                                    'Made for a community that deserves better representation',
                                )
                            }}
                        </h2>

                        <p class="mt-5 text-base leading-8 text-stone-600 dark:text-stone-300">
                            {{
                                stringSetting(
                                    homepage.why_description,
                                    'A thoughtful source for natural, respectful imagery and writing.',
                                )
                            }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-accent)_14%,transparent)] text-[var(--brand-accent)]">
                                <Check class="h-5 w-5" />
                            </div>

                            <h3 class="mt-5 text-lg font-semibold">
                                Respectful by design
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
                                Imagery selected to represent nudism as normal, social, wholesome, and human.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-secondary)_14%,transparent)] text-[var(--brand-secondary)]">
                                <ImageIcon class="h-5 w-5" />
                            </div>

                            <h3 class="mt-5 text-lg font-semibold">
                                Purpose-built licensing
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
                                Clear options for digital publishing, organizations, clubs, resorts, and campaigns.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-primary)_12%,transparent)] text-[var(--brand-primary)] dark:text-stone-200">
                                <Users class="h-5 w-5" />
                            </div>

                            <h3 class="mt-5 text-lg font-semibold">
                                Community perspective
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
                                Photography and writing shaped around the lived experiences of nudists.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-accent)_14%,transparent)] text-[var(--brand-accent)]">
                                <BookOpen class="h-5 w-5" />
                            </div>

                            <h3 class="mt-5 text-lg font-semibold">
                                More than stock photos
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
                                Stories, guidance, advocacy, ideas, and resources alongside the image library.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="
                    booleanSetting(homepage.show_featured_collections)
                    && featuredCollections.length
                "
                class="bg-[color-mix(in_srgb,var(--brand-secondary)_7%,white)] py-20 dark:bg-[color-mix(in_srgb,var(--brand-secondary)_12%,#0c0a09)] lg:py-28"
            >
                <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                                {{
                                    stringSetting(
                                        homepage.collections_eyebrow,
                                        'Curated collections',
                                    )
                                }}
                            </p>

                            <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">
                                {{
                                    stringSetting(
                                        homepage.collections_title,
                                        'Explore imagery with purpose',
                                    )
                                }}
                            </h2>

                            <p class="mt-4 text-base leading-7 text-stone-600 dark:text-stone-300">
                                {{
                                    stringSetting(
                                        homepage.collections_description,
                                        'Browse carefully organized collections.',
                                    )
                                }}
                            </p>
                        </div>

                        <Link
                            href="/images"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]"
                        >
                            Browse all images
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div class="mt-10 grid gap-6 lg:grid-cols-3">
                        <Link
                            v-for="collection in featuredCollections"
                            :key="collection.id"
                            :href="collectionHref(collection)"
                            class="group overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-stone-800 dark:bg-stone-900"
                        >
                            <div class="overflow-hidden bg-stone-200 dark:bg-stone-800">
                                <img
                                    v-if="
                                        collection.cover_image?.thumbnail_url
                                        || collection.cover_image?.icon_url
                                    "
                                    :src="
                                        collection.cover_image.thumbnail_url
                                        ?? collection.cover_image.icon_url
                                        ?? ''
                                    "
                                    :alt="collection.cover_image.title"
                                    loading="lazy"
                                    decoding="async"
                                    class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105"
                                />

                                <div
                                    v-else
                                    class="aspect-[16/10] bg-[linear-gradient(135deg,color-mix(in_srgb,var(--brand-secondary)_55%,white),color-mix(in_srgb,var(--brand-primary)_75%,black))]"
                                />
                            </div>

                            <div class="p-6">
                                <div class="text-xs font-semibold uppercase tracking-wider text-stone-500">
                                    {{ collection.images_count }} images
                                </div>

                                <h3 class="mt-2 text-2xl font-semibold transition group-hover:text-[var(--brand-accent)]">
                                    {{ collection.name }}
                                </h3>

                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-stone-600 dark:text-stone-400">
                                    {{ collection.description || 'Explore this curated image collection.' }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <section
                v-if="
                    booleanSetting(homepage.show_featured_images)
                    && featuredImages.length
                "
                class="mx-auto max-w-[1440px] px-5 py-20 sm:px-8 lg:px-12 lg:py-28"
            >
                <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                            {{
                                stringSetting(
                                    homepage.images_eyebrow,
                                    'Image library',
                                )
                            }}
                        </p>

                        <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">
                            {{
                                stringSetting(
                                    homepage.images_title,
                                    'Photography that feels honest',
                                )
                            }}
                        </h2>

                        <p class="mt-4 text-base leading-7 text-stone-600 dark:text-stone-300">
                            {{
                                stringSetting(
                                    homepage.images_description,
                                    'Discover natural, respectful imagery.',
                                )
                            }}
                        </p>
                    </div>

                    <Link
                        href="/images"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]"
                    >
                        Explore the library
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div class="mt-10 grid auto-rows-[180px] grid-cols-2 gap-3 sm:auto-rows-[230px] lg:grid-cols-4 lg:gap-4">
                    <Link
                        v-for="(image, index) in featuredImages"
                        :key="image.id"
                        :href="`/images/${image.slug}`"
                        :class="[
                            'group relative overflow-hidden rounded-2xl bg-stone-200 dark:bg-stone-800',
                            index === 0
                                ? 'col-span-2 row-span-2'
                                : index === 3
                                    ? 'row-span-2'
                                    : '',
                        ]"
                    >
                        <img
                            v-if="image.thumbnail_url || image.icon_url"
                            :src="image.thumbnail_url ?? image.icon_url ?? ''"
                            :alt="image.title"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        />

                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-80 transition group-hover:opacity-100" />

                        <div class="absolute inset-x-0 bottom-0 p-4 text-white sm:p-5">
                            <h3 class="line-clamp-2 font-semibold">
                                {{ image.title }}
                            </h3>

                            <p class="mt-1 text-xs text-white/70">
                                {{
                                    image.photographer
                                        ? `By ${image.photographer}`
                                        : image.collection?.name ?? 'Unclad Collection'
                                }}
                            </p>
                        </div>

                        <span
                            v-if="image.is_ai_generated"
                            class="absolute right-3 top-3 rounded-full bg-black/55 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur"
                        >
                            AI
                        </span>
                    </Link>
                </div>
            </section>

            <section
                v-if="
                    booleanSetting(homepage.show_latest_articles)
                    && latestArticles.length
                "
                class="border-y border-stone-200 bg-white py-20 dark:border-stone-800 dark:bg-stone-900 lg:py-28"
            >
                <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                                {{
                                    stringSetting(
                                        homepage.articles_eyebrow,
                                        'Ideas and experiences',
                                    )
                                }}
                            </p>

                            <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">
                                {{
                                    stringSetting(
                                        homepage.articles_title,
                                        'Stories from the community',
                                    )
                                }}
                            </h2>

                            <p class="mt-4 text-base leading-7 text-stone-600 dark:text-stone-300">
                                {{
                                    stringSetting(
                                        homepage.articles_description,
                                        'Read the latest stories and perspectives.',
                                    )
                                }}
                            </p>
                        </div>

                        <Link
                            href="/blog"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]"
                        >
                            Read all articles
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div class="mt-10 grid gap-6 lg:grid-cols-3">
                        <Link
                            v-for="article in latestArticles"
                            :key="article.id"
                            :href="`/blog/${article.slug}`"
                            class="group overflow-hidden rounded-3xl border border-stone-200 bg-stone-50 transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-stone-800 dark:bg-stone-950"
                        >
                            <div class="overflow-hidden bg-stone-200 dark:bg-stone-800">
                                <img
                                    v-if="articleImage(article)"
                                    :src="articleImage(article) ?? ''"
                                    :alt="article.title"
                                    loading="lazy"
                                    decoding="async"
                                    class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105"
                                />

                                <div
                                    v-else
                                    class="flex aspect-[16/9] items-center justify-center"
                                >
                                    <BookOpen class="h-8 w-8 text-stone-400" />
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-stone-500">
                                    <span
                                        v-if="article.categories?.[0]"
                                        class="font-semibold uppercase tracking-wide text-[var(--brand-accent)]"
                                    >
                                        {{ article.categories[0].name }}
                                    </span>

                                    <span v-if="article.categories?.[0]">
                                        •
                                    </span>

                                    <span>
                                        {{ formatDate(article.published_at) }}
                                    </span>
                                </div>

                                <h3 class="mt-3 line-clamp-2 text-xl font-semibold transition group-hover:text-[var(--brand-accent)]">
                                    {{ article.title }}
                                </h3>

                                <p
                                    v-if="article.excerpt"
                                    class="mt-3 line-clamp-3 text-sm leading-6 text-stone-600 dark:text-stone-400"
                                >
                                    {{ article.excerpt }}
                                </p>

                                <div class="mt-5 text-sm font-medium">
                                    {{ article.author?.name ?? siteName }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
                <div class="mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-[var(--brand-primary)] px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-16">
                    <Heart class="mx-auto h-8 w-8 text-[var(--brand-accent)]" />

                    <h2 class="mx-auto mt-5 max-w-3xl text-3xl font-semibold tracking-tight sm:text-5xl">
                        {{
                            stringSetting(
                                homepage.cta_title,
                                'Find the right image. Tell a better story.',
                            )
                        }}
                    </h2>

                    <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-white/75">
                        {{
                            stringSetting(
                                homepage.cta_description,
                                'Create an account to save favorites and purchase licensed downloads.',
                            )
                        }}
                    </p>

                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <Link
                            v-if="!isAuthenticated"
                            :href="register()"
                            class="inline-flex h-12 items-center rounded-full bg-white px-6 text-sm font-semibold text-[var(--brand-primary)] transition hover:-translate-y-0.5"
                        >
                            {{
                                stringSetting(
                                    homepage.cta_button_label,
                                    'Create an Account',
                                )
                            }}
                        </Link>

                        <Link
                            href="/images"
                            class="inline-flex h-12 items-center rounded-full border border-white/30 px-6 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Browse Images
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto grid max-w-[1440px] gap-10 px-5 py-12 sm:px-8 md:grid-cols-2 lg:grid-cols-4 lg:px-12">
                <div class="lg:col-span-2">
                    <img
                        v-if="logoUrl"
                        :src="logoUrl"
                        :alt="`${siteName} logo`"
                        class="h-10 max-w-52 object-contain"
                    />

                    <div v-else class="text-xl font-semibold">
                        {{ siteName }}
                    </div>

                    <p class="mt-4 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-400">
                        {{ siteTagline }}
                    </p>

                    <div
                        v-if="socialLinks.length"
                        class="mt-6 flex gap-2"
                    >
                        <a
                            v-for="item in socialLinks"
                            :key="item.label"
                            :href="item.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-stone-300 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
                            :aria-label="item.label"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold">
                        Explore
                    </h2>

                    <nav class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-400">
                        <Link href="/images" class="hover:text-[var(--brand-accent)]">
                            Image library
                        </Link>
                        <Link href="/blog" class="hover:text-[var(--brand-accent)]">
                            Stories
                        </Link>
                        <Link v-if="isAuthenticated" href="/favorites" class="hover:text-[var(--brand-accent)]">
                            Favorites
                        </Link>
                        <Link v-if="isAuthenticated" href="/purchases" class="hover:text-[var(--brand-accent)]">
                            Purchases
                        </Link>
                    </nav>
                </div>

                <div>
                    <h2 class="text-sm font-semibold">
                        Account
                    </h2>

                    <nav class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-400">
                        <Link
                            v-if="isAuthenticated"
                            :href="dashboard()"
                            class="hover:text-[var(--brand-accent)]"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link :href="login()" class="hover:text-[var(--brand-accent)]">
                                Log in
                            </Link>
                            <Link :href="register()" class="hover:text-[var(--brand-accent)]">
                                Create account
                            </Link>
                        </template>

                        <a
                            v-if="general.contact_email"
                            :href="`mailto:${general.contact_email}`"
                            class="hover:text-[var(--brand-accent)]"
                        >
                            Contact
                        </a>
                    </nav>
                </div>
            </div>

            <div class="border-t border-stone-200 dark:border-stone-800">
                <div class="mx-auto flex max-w-[1440px] flex-col gap-2 px-5 py-6 text-xs text-stone-500 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-12">
                    <span>
                        {{
                            stringSetting(
                                branding.footer_text,
                                `© ${siteName}. All rights reserved.`,
                            )
                        }}
                    </span>

                    <span>
                        Natural imagery. Thoughtful representation.
                    </span>
                </div>
            </div>
        </footer>
    </div>
</template>
