<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Check,
    Heart,
    Image as ImageIcon,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';

import PublicAdPlacement from '@/components/Advertising/PublicAdPlacement.vue';
import PublicCTA from '@/components/Public/PublicCTA.vue';
import PublicHomeHero from '@/components/Public/PublicHomeHero.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';
import PublicSectionHeading from '@/components/Public/PublicSectionHeading.vue';
import DiscoveryAssetGrid from '@/components/Discovery/DiscoveryAssetGrid.vue';

import type { RelatedPublicAsset } from '@/types/publicAsset';

import type {
    HomeArticle,
    HomeCollection,
    HomeHeroCampaign,
    HomeDiscoveryCollectionPlacement,
    HomeHeroImage,
    HomeImage,
    HomeStatistics,
    PublicSiteSettings,
} from '@/types/home';

const props = defineProps<{
    siteSettings: PublicSiteSettings;
    heroImage: HomeHeroImage | null;
    heroCampaign: HomeHeroCampaign | null;
    featuredCollections: HomeCollection[];
    discoveryCollectionPlacements: Record<string, HomeDiscoveryCollectionPlacement[]>;
    featuredImages: HomeImage[];
    recommendedAssets: RelatedPublicAsset[];
    trendingAssets: RelatedPublicAsset[];
    latestArticles: HomeArticle[];
    statistics: HomeStatistics;
}>();

const general = computed(() => props.siteSettings.general ?? {});
const homepage = computed(() => props.siteSettings.homepage ?? {});
const seo = computed(() => props.siteSettings.seo ?? {});
const primaryDiscoveryCollections = computed(() => props.discoveryCollectionPlacements.homepage_primary ?? []);
const secondaryDiscoveryCollections = computed(() => props.discoveryCollectionPlacements.homepage_secondary ?? []);

const siteName = computed(() =>
    stringSetting(general.value.site_name, 'Unclad Collection'),
);

const siteTagline = computed(() =>
    stringSetting(
        general.value.site_tagline,
        'Licensed imagery and thoughtful stories for the nudist community.',
    ),
);

function stringSetting(value: unknown, fallback: string): string {
    return typeof value === 'string' && value.trim()
        ? value
        : fallback;
}

function booleanSetting(value: unknown, fallback = true): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'string') {
        return value === 'true' || value === '1';
    }

    return fallback;
}

function articleImage(article: HomeArticle): string | null {
    return article.icon_image_url
        ?? article.header_image_url
        ?? article.featured_image_url;
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
</script>

<template>
    <Head>
        <link
            v-if="heroImage?.image_url"
            rel="preload"
            as="image"
            :href="heroImage.image_url"
            fetchpriority="high"
        />
    </Head>
    <Head>
        <title>{{ stringSetting(seo.default_meta_title, siteName) }}</title>
        <meta
            name="description"
            :content="stringSetting(seo.default_meta_description, siteTagline)"
        />
    </Head>

    <PublicPageLayout>
        <PublicHomeHero
            :eyebrow="stringSetting(homepage.hero_eyebrow, 'Authentic media. Thoughtful stories.')"
            :title="stringSetting(homepage.hero_title, 'A more natural way to represent nudist life.')"
            :description="stringSetting(homepage.hero_description, siteTagline)"
            :hero-image="heroImage"
            :campaign="heroCampaign"
            :primary-label="stringSetting(homepage.hero_primary_button_label, 'Browse Marketplace')"
            :primary-href="stringSetting(homepage.hero_primary_button_url, '/images')"
            :secondary-label="stringSetting(homepage.hero_secondary_button_label, 'Read Stories')"
            :secondary-href="stringSetting(homepage.hero_secondary_button_url, '/blog')"
        />

        <PublicAdPlacement placement="homepage-below-hero" class="mx-auto max-w-[1200px] px-5 py-6 sm:px-8 lg:px-12" />


        <section
            v-if="primaryDiscoveryCollections.length"
            class="border-b border-stone-200 bg-white py-16 dark:border-stone-800 dark:bg-stone-900 lg:py-24"
        >
            <div class="mx-auto max-w-[1440px] space-y-8 px-5 sm:px-8 lg:px-12">
                <article
                    v-for="placement in primaryDiscoveryCollections"
                    :key="placement.id"
                    class="grid overflow-hidden rounded-[2rem] border border-stone-200 bg-stone-950 text-white shadow-xl lg:grid-cols-[1.05fr_.95fr] dark:border-stone-800"
                >
                    <div class="flex flex-col justify-center p-8 sm:p-12 lg:p-16">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">{{ placement.eyebrow }}</div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">{{ placement.heading }}</h2>
                        <p v-if="placement.description" class="mt-5 max-w-2xl text-base leading-8 text-white/75">{{ placement.description }}</p>
                        <div class="mt-7 flex items-center gap-4">
                            <Link :href="placement.href" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-stone-100">
                                {{ placement.call_to_action }}
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Link>
                            <span class="text-sm text-white/60">{{ placement.collection.assets_count }} assets</span>
                        </div>
                    </div>
                    <div class="min-h-[280px] bg-stone-800 lg:min-h-[420px]">
                        <img
                            v-if="placement.collection.cover_image_url"
                            :src="placement.collection.cover_image_url"
                            :alt="`${placement.collection.name} collection`"
                            class="h-full w-full object-cover"
                        />
                    </div>
                </article>
            </div>
        </section>

        <section
            v-if="booleanSetting(homepage.show_statistics)"
            class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="mx-auto grid max-w-[1440px] divide-y divide-stone-200 px-5 sm:grid-cols-2 sm:divide-x sm:divide-y-0 sm:px-8 lg:grid-cols-4 lg:px-12 dark:divide-stone-800">
                <div class="px-4 py-8 sm:px-7">
                    <div class="text-3xl font-semibold tracking-tight">{{ formatNumber(statistics.images) }}+</div>
                    <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">Licensed assets</div>
                </div>
                <div class="px-4 py-8 sm:px-7">
                    <div class="text-3xl font-semibold tracking-tight">{{ formatNumber(statistics.collections) }}</div>
                    <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">Curated collections</div>
                </div>
                <div class="px-4 py-8 sm:px-7">
                    <div class="text-3xl font-semibold tracking-tight">{{ formatNumber(statistics.articles) }}</div>
                    <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">Community articles</div>
                </div>
                <div class="px-4 py-8 sm:px-7">
                    <div class="text-3xl font-semibold tracking-tight">{{ formatNumber(statistics.downloads) }}</div>
                    <div class="mt-1 text-sm text-stone-500 dark:text-stone-400">Licensed downloads</div>
                </div>
            </div>
        </section>

        <section class="public-deferred-section mx-auto max-w-[1440px] px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[0.78fr_1.22fr] lg:items-start">
                <PublicSectionHeading
                    eyebrow="Why Unclad Collection"
                    :title="stringSetting(homepage.why_title, 'Made for a community that deserves better representation')"
                    :description="stringSetting(homepage.why_description, 'A thoughtful source for natural, respectful digital media and stories.')"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-accent)_14%,transparent)] text-[var(--brand-accent)]">
                            <ShieldCheck class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold">Respectful by design</h3>
                        <p class="mt-2 text-sm leading-7 text-stone-600 dark:text-stone-400">
                            Media selected to represent nudism as normal, social, wholesome, and human.
                        </p>
                    </article>

                    <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-secondary)_14%,transparent)] text-[var(--brand-secondary)]">
                            <ImageIcon class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold">Purpose-built licensing</h3>
                        <p class="mt-2 text-sm leading-7 text-stone-600 dark:text-stone-400">
                            Clear options for digital publishing, organizations, clubs, resorts, and campaigns.
                        </p>
                    </article>

                    <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-primary)_12%,transparent)] text-[var(--brand-primary)] dark:text-stone-100">
                            <Users class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold">Community perspective</h3>
                        <p class="mt-2 text-sm leading-7 text-stone-600 dark:text-stone-400">
                            Creative media and stories shaped around the lived experiences of nudists.
                        </p>
                    </article>

                    <article class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-accent)_14%,transparent)] text-[var(--brand-accent)]">
                            <BookOpen class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold">More than stock media</h3>
                        <p class="mt-2 text-sm leading-7 text-stone-600 dark:text-stone-400">
                            Stories, guidance, advocacy, ideas, and resources alongside the marketplace.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section
            v-if="booleanSetting(homepage.show_featured_collections) && featuredCollections.length"
            class="bg-[color-mix(in_srgb,var(--brand-secondary)_7%,white)] py-20 dark:bg-[color-mix(in_srgb,var(--brand-secondary)_12%,#0c0a09)] lg:py-28"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <PublicSectionHeading
                        :eyebrow="stringSetting(homepage.collections_eyebrow, 'Curated collections')"
                        :title="stringSetting(homepage.collections_title, 'Explore media with purpose')"
                        :description="stringSetting(homepage.collections_description, 'Browse carefully organized collections created for editorial, educational, community, and marketing use.')"
                    />

                    <Link href="/images" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                        Browse the marketplace
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
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
                                v-if="collection.cover_image?.thumbnail_url || collection.cover_image?.icon_url"
                                :src="collection.cover_image.thumbnail_url ?? collection.cover_image.icon_url ?? ''"
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
                                {{ collection.images_count }} assets
                            </div>
                            <h3 class="mt-2 text-2xl font-semibold transition group-hover:text-[var(--brand-accent)]">
                                {{ collection.name }}
                            </h3>
                            <p class="mt-3 line-clamp-3 text-sm leading-7 text-stone-600 dark:text-stone-400">
                                {{ collection.description || 'Explore this curated collection.' }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <section
            v-if="booleanSetting(homepage.show_featured_images) && featuredImages.length"
            class="mx-auto max-w-[1440px] px-5 py-20 sm:px-8 lg:px-12 lg:py-28"
        >
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <PublicSectionHeading
                    :eyebrow="stringSetting(homepage.images_eyebrow, 'Marketplace')"
                    :title="stringSetting(homepage.images_title, 'Digital media that feels honest')"
                    :description="stringSetting(homepage.images_description, 'Discover natural, respectful photography, video, vectors, and creative resources for publications, campaigns, resorts, organizations, and personal projects.')"
                />

                <Link href="/images" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                    Explore the marketplace
                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>

            <div class="mt-10 grid auto-rows-[180px] grid-cols-2 gap-3 sm:auto-rows-[230px] lg:grid-cols-4 lg:gap-4">
                <Link
                    v-for="(image, index) in featuredImages"
                    :key="image.id"
                    :href="`/images/${image.slug}`"
                    :class="[
                        'group relative overflow-hidden rounded-3xl bg-stone-200 dark:bg-stone-800',
                        index === 0 ? 'col-span-2 row-span-2' : index === 3 ? 'row-span-2' : '',
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

                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent opacity-80 transition group-hover:opacity-100" />

                    <div class="absolute inset-x-0 bottom-0 p-4 text-white sm:p-5">
                        <h3 class="line-clamp-2 font-semibold">{{ image.title }}</h3>
                        <p class="mt-1 text-xs text-white/70">
                            {{ image.photographer ? `By ${image.photographer}` : image.collection?.name ?? siteName }}
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
            v-if="recommendedAssets.length"
            class="border-y border-stone-200 bg-stone-50 py-20 dark:border-stone-800 dark:bg-stone-950 lg:py-28"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <PublicSectionHeading
                        eyebrow="Selected for you"
                        title="Recommended for you"
                        description="A personalized mix based on the collections, subjects, formats, and assets you explore and save."
                    />
                    <Link href="/images?discovery_source=recommended_for_you" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                        Explore the marketplace
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>
                <DiscoveryAssetGrid class="mt-10" :assets="recommendedAssets.slice(0, 6)" />
            </div>
        </section>

        <section
            v-if="trendingAssets.length"
            class="border-y border-stone-200 bg-white py-20 dark:border-stone-800 dark:bg-stone-900 lg:py-28"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <PublicSectionHeading
                        eyebrow="Popular this week"
                        title="Trending in the marketplace"
                        description="Assets gaining attention through recent views, favorites, cart activity, purchases, and downloads."
                    />
                    <Link href="/images?sort=trending" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                        See all trending assets
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>

                <DiscoveryAssetGrid class="mt-10" :assets="trendingAssets.slice(0, 6)" />
            </div>
        </section>

        <section
            v-if="secondaryDiscoveryCollections.length"
            class="mx-auto max-w-[1440px] px-5 py-20 sm:px-8 lg:px-12 lg:py-28"
        >
            <PublicSectionHeading
                eyebrow="Explore more"
                title="Featured and seasonal collections"
                description="Timely, hand-selected collections chosen to help you discover the right media faster."
            />
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="placement in secondaryDiscoveryCollections"
                    :key="placement.id"
                    :href="placement.href"
                    class="group overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl dark:border-stone-800 dark:bg-stone-900"
                >
                    <div class="aspect-[16/10] overflow-hidden bg-stone-200 dark:bg-stone-800">
                        <img v-if="placement.collection.cover_image_url" :src="placement.collection.cover_image_url" :alt="`${placement.collection.name} collection`" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    </div>
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-wider text-[var(--brand-accent)]">{{ placement.eyebrow }}</div>
                        <h3 class="mt-2 text-2xl font-semibold">{{ placement.heading }}</h3>
                        <p class="mt-3 line-clamp-3 text-sm leading-7 text-stone-600 dark:text-stone-400">{{ placement.description }}</p>
                        <div class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">{{ placement.call_to_action }}<ArrowRight class="h-4 w-4" /></div>
                    </div>
                </Link>
            </div>
        </section>

        <section
            v-if="booleanSetting(homepage.show_latest_articles) && latestArticles.length"
            class="border-y border-stone-200 bg-white py-20 dark:border-stone-800 dark:bg-stone-900 lg:py-28"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <PublicSectionHeading
                        :eyebrow="stringSetting(homepage.articles_eyebrow, 'Ideas and experiences')"
                        :title="stringSetting(homepage.articles_title, 'Stories from the community')"
                        :description="stringSetting(homepage.articles_description, 'Read practical guidance, personal experiences, advocacy, and thoughtful perspectives on nudist life.')"
                    />

                    <Link href="/blog" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                        Read all stories
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
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
                            <div v-else class="flex aspect-[16/9] items-center justify-center">
                                <BookOpen class="h-8 w-8 text-stone-400" aria-hidden="true" />
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
                                <span v-if="article.categories?.[0]">•</span>
                                <span>{{ formatDate(article.published_at) }}</span>
                            </div>

                            <h3 class="mt-3 line-clamp-2 text-xl font-semibold transition group-hover:text-[var(--brand-accent)]">
                                {{ article.title }}
                            </h3>

                            <p v-if="article.excerpt" class="mt-3 line-clamp-3 text-sm leading-7 text-stone-600 dark:text-stone-400">
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

        <PublicCTA
            :title="stringSetting(homepage.cta_title, 'Find the right asset. Tell a better story.')"
            :description="stringSetting(homepage.cta_description, 'Create an account to save favorites, build your library, and stay connected with new assets and stories.')"
            :primary-label="stringSetting(homepage.cta_button_label, 'Create an Account')"
            primary-href="/register"
            secondary-label="Browse Marketplace"
            secondary-href="/images"
        />
    </PublicPageLayout>
</template>
