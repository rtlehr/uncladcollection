<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    Eye,
    FolderOpen,
    Heart,
    Image as ImageIcon,
    Maximize2,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';

import MobilePurchaseBar from '@/components/Commerce/MobilePurchaseBar.vue';
import PurchasePanel from '@/components/Commerce/PurchasePanel.vue';
import GalleryCard from '@/components/Gallery/GalleryCard.vue';
import ImageLightbox from '@/components/Gallery/ImageLightbox.vue';
import ImageNavigation from '@/components/Gallery/ImageNavigation.vue';
import ImageShareActions from '@/components/Gallery/ImageShareActions.vue';
import RecentlyViewedImages from '@/components/Gallery/RecentlyViewedImages.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';

import type {
    GalleryImage,
    GalleryImageDetail,
    GalleryLicenseType,
    GalleryNavigationImage,
} from '@/types/gallery';

const props = defineProps<{
    imageRecord: GalleryImageDetail;
    relatedImages: GalleryImage[];
    licenseTypes: GalleryLicenseType[];
    previousImage: GalleryNavigationImage | null;
    nextImage: GalleryNavigationImage | null;
}>();

const page = usePage();

const favoriteProcessing = ref(false);
const favoriteState = ref(props.imageRecord.is_favorited);
const lightboxOpen = ref(false);

const isAuthenticated = computed(() =>
    Boolean((page.props.auth as any)?.user),
);

const previewUrl = computed(() =>
    props.imageRecord.high_res_url
    ?? props.imageRecord.thumbnail_url
    ?? props.imageRecord.icon_url
    ?? null,
);

const downloadSummary = computed(() => {
    const license = props.imageRecord.active_license;

    if (!license) {
        return null;
    }

    if (license.download_limit === null) {
        return `${license.downloads_used} used · Unlimited`;
    }

    return `${license.downloads_used} of ${license.download_limit} used`;
});

function toggleFavorite(): void {
    if (!isAuthenticated.value) {
        router.visit('/login');
        return;
    }

    if (favoriteProcessing.value) {
        return;
    }

    favoriteProcessing.value = true;

    const wasFavorited = favoriteState.value;
    favoriteState.value = !favoriteState.value;

    router[wasFavorited ? 'delete' : 'post'](
        `/images/${props.imageRecord.id}/favorite`,
        {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                favoriteState.value = wasFavorited;
            },
            onFinish: () => {
                favoriteProcessing.value = false;
            },
        } as any,
    );
}

function visitPrevious(): void {
    if (props.previousImage) {
        router.visit(`/images/${props.previousImage.slug}`);
    }
}

function visitNext(): void {
    if (props.nextImage) {
        router.visit(`/images/${props.nextImage.slug}`);
    }
}
</script>

<template>
    <Head>
        <link
            v-if="previewUrl"
            rel="preload"
            as="image"
            :href="previewUrl"
            fetchpriority="high"
        />
    </Head>
    <PublicSeoHead
        :title="imageRecord.title"
        :description="
            imageRecord.description
            || `${imageRecord.title} from the Unclad Collection image library.`
        "
        :image="previewUrl"
        :canonical-path="`/images/${imageRecord.slug}`"
/>


    <StructuredData

        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Images', url: '/images' },
            { name: imageRecord.title, url: `/images/${imageRecord.slug}` },
        ]"

        :image="previewUrl"

    />

    <PublicPageLayout>
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-5 py-4 sm:px-8 lg:px-12">
                <Link
                    href="/images"
                    class="inline-flex items-center gap-2 text-sm font-medium text-stone-500 transition hover:text-[var(--brand-accent)]"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to image library
                </Link>

                <ImageShareActions :title="imageRecord.title" />
            </div>
        </section>

        <section class="mx-auto grid max-w-[1440px] gap-8 px-4 py-6 sm:px-8 sm:py-8 pb-28 sm:px-8 lg:grid-cols-[minmax(0,1fr)_400px] lg:px-12 lg:py-12 lg:pb-12">
            <div class="min-w-0">
                <div class="group relative overflow-hidden rounded-3xl border border-stone-200 bg-stone-100 dark:border-stone-800 dark:bg-stone-900">
                    <button
                        v-if="previewUrl"
                        type="button"
                        class="block w-full"
                        aria-label="Open expanded image viewer"
                        @click="lightboxOpen = true"
                    >
                        <img
                            :src="previewUrl"
                            :alt="imageRecord.title"
                            fetchpriority="high"
                            class="max-h-[80vh] w-full object-contain"
                        />

                        <span class="absolute right-4 top-4 inline-flex h-11 items-center gap-2 rounded-full bg-black/50 px-4 text-sm font-semibold text-white opacity-0 backdrop-blur transition group-hover:opacity-100">
                            <Maximize2 class="h-4 w-4" />
                            Expand
                        </span>
                    </button>

                    <div
                        v-else
                        class="flex aspect-[4/3] items-center justify-center"
                    >
                        <ImageIcon class="h-12 w-12 text-stone-400" />
                    </div>

                    <span
                        v-if="imageRecord.is_ai_generated"
                        class="absolute left-4 top-4 rounded-full bg-black/55 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white backdrop-blur"
                    >
                        AI Generated
                    </span>
                </div>

                <div class="mt-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="max-w-3xl">
                            <Link
                                v-if="imageRecord.collection"
                                :href="imageRecord.collection.slug ? `/collections/${imageRecord.collection.slug}` : `/images?collection_id=${imageRecord.collection.id}`"
                                class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                            >
                                <FolderOpen class="h-4 w-4" />
                                {{ imageRecord.collection.name }}
                            </Link>

                            <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-5xl">
                                {{ imageRecord.title }}
                            </h1>

                            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-stone-500 dark:text-stone-400">
                                <span class="inline-flex items-center gap-2">
                                    <UserRound class="h-4 w-4" />
                                    {{
                                        imageRecord.photographer
                                            || 'Unclad Collection'
                                    }}
                                </span>

                                <span>
                                    Added {{ imageRecord.created_at || '—' }}
                                </span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full border border-stone-300 px-5 text-sm font-semibold transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
                            :aria-pressed="favoriteState"
                            :disabled="favoriteProcessing"
                            @click="toggleFavorite"
                        >
                            <Heart
                                :class="[
                                    'h-4 w-4',
                                    favoriteState
                                        ? 'fill-current text-[var(--brand-accent)]'
                                        : '',
                                ]"
                            />

                            {{ favoriteState ? 'Saved' : 'Save Image' }}
                        </button>
                    </div>

                    <p
                        v-if="imageRecord.description"
                        class="mt-7 max-w-4xl text-base leading-8 text-stone-600 sm:text-lg dark:text-stone-300"
                    >
                        {{ imageRecord.description }}
                    </p>

                    <div class="mt-7 flex flex-wrap gap-2">
                        <Link
                            v-for="category in imageRecord.categories"
                            :key="category.id"
                            :href="`/images?category_id=${category.id}`"
                            class="rounded-full bg-stone-200 px-3 py-1.5 text-xs font-medium transition hover:bg-stone-300 dark:bg-stone-800 dark:hover:bg-stone-700"
                        >
                            {{ category.name }}
                        </Link>

                        <Link
                            v-for="tag in imageRecord.tags"
                            :key="tag.id"
                            :href="`/images?tag_id=${tag.id}`"
                            class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-medium transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
                        >
                            #{{ tag.name }}
                        </Link>
                    </div>
                </div>

                <div class="mt-10">
                    <ImageNavigation
                        :previous-image="previousImage"
                        :next-image="nextImage"
                    />
                </div>
            </div>

            <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                <div id="purchase-panel" class="scroll-mt-24">
                    <PurchasePanel
                        :image-id="imageRecord.id"
                        :image-title="imageRecord.title"
                        :licenses="licenseTypes"
                        :can-purchase="imageRecord.can_purchase"
                        :is-purchased="imageRecord.is_purchased"
                    />
                </div>

                <div
                    v-if="imageRecord.active_license"
                    class="rounded-3xl border border-emerald-500/25 bg-emerald-500/10 p-6"
                >
                    <h2 class="font-semibold text-emerald-800 dark:text-emerald-200">
                        Your License
                    </h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-emerald-700/75 dark:text-emerald-300/75">
                                Type
                            </dt>

                            <dd class="font-medium">
                                {{ imageRecord.active_license.license_name }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-emerald-700/75 dark:text-emerald-300/75">
                                Downloads
                            </dt>

                            <dd class="font-medium">
                                {{ downloadSummary }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-emerald-700/75 dark:text-emerald-300/75">
                                Expires
                            </dt>

                            <dd class="font-medium">
                                {{ imageRecord.active_license.expires_at || 'Never' }}
                            </dd>
                        </div>
                    </dl>

                    <a
                        v-if="imageRecord.can_download"
                        :href="`/images/${imageRecord.id}/download`"
                        class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                    >
                        <Download class="h-4 w-4" />
                        Download Image
                    </a>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="font-semibold">
                        Image Details
                    </h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-stone-500">
                                Views
                            </dt>

                            <dd class="inline-flex items-center gap-1 font-medium">
                                <Eye class="h-4 w-4" />
                                {{ imageRecord.views_count.toLocaleString() }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-stone-500">
                                Favorites
                            </dt>

                            <dd class="font-medium">
                                {{ imageRecord.favorites_count.toLocaleString() }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-stone-500">
                                Downloads
                            </dt>

                            <dd class="font-medium">
                                {{ imageRecord.downloads_count.toLocaleString() }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-stone-500">
                                Source
                            </dt>

                            <dd class="font-medium">
                                {{
                                    imageRecord.is_ai_generated
                                        ? 'AI Generated'
                                        : 'Photography'
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="font-semibold">
                        About the Creator
                    </h2>

                    <div class="mt-4 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-stone-200 dark:bg-stone-800">
                            <UserRound class="h-5 w-5 text-stone-500" />
                        </div>

                        <div>
                            <div class="font-medium">
                                {{
                                    imageRecord.photographer
                                        || 'Unclad Collection'
                                }}
                            </div>

                            <div class="text-xs text-stone-500">
                                Image contributor
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </section>

        <section
            v-if="relatedImages.length"
            class="public-deferred-section border-t border-stone-200 bg-white py-16 dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                            Similar context and themes
                        </p>

                        <h2 class="mt-3 text-3xl font-semibold tracking-tight">
                            Related Images
                        </h2>
                    </div>

                    <Link
                        href="/images"
                        class="text-sm font-semibold text-[var(--brand-accent)]"
                    >
                        View all images
                    </Link>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <GalleryCard
                        v-for="image in relatedImages"
                        :key="image.id"
                        :image="image"
                        :show-collection="false"
                    />
                </div>
            </div>
        </section>

        <RecentlyViewedImages
            :current-image="{
                id: imageRecord.id,
                title: imageRecord.title,
                slug: imageRecord.slug,
                thumbnail_url: imageRecord.thumbnail_url,
                icon_url: imageRecord.icon_url,
            }"
        />

        <ImageLightbox
            v-if="previewUrl"
            v-model:open="lightboxOpen"
            :image-url="previewUrl"
            :title="imageRecord.title"
            :previous-href="
                previousImage
                    ? `/images/${previousImage.slug}`
                    : null
            "
            :next-href="
                nextImage
                    ? `/images/${nextImage.slug}`
                    : null
            "
            @previous="visitPrevious"
            @next="visitNext"
        />

        <MobilePurchaseBar
            :can-purchase="imageRecord.can_purchase"
            :can-download="imageRecord.can_download"
            :image-id="imageRecord.id"
        />
    </PublicPageLayout>
</template>
