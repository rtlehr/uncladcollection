<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';

import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';

import type { PaginatedGalleryAssets } from '@/types/gallery';

defineProps<{
    assets: PaginatedGalleryAssets;
}>();
</script>

<template>
    <Head title="My Favorites" />

    <PublicPageLayout>
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto max-w-[1440px] px-5 py-14 sm:px-8 lg:px-12 lg:py-18">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                    Saved for later
                </p>

                <h1 class="mt-4 break-words text-3xl font-semibold sm:text-4xl tracking-[-0.035em] sm:text-5xl">
                    My Favorites
                </h1>

                <p class="mt-4 max-w-2xl text-base leading-7 text-stone-600 dark:text-stone-400">
                    Keep track of images you want to revisit, compare, or license later.
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-[1440px] px-4 py-8 sm:px-8 sm:py-10 sm:px-8 lg:px-12 lg:py-14">
            <GalleryGrid
                v-if="assets.data.length"
                :assets="assets.data"
            />

            <div
                v-else
                class="rounded-3xl border border-dashed border-stone-300 px-6 py-16 text-center dark:border-stone-700"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-200 text-stone-500 dark:bg-stone-800">
                    <Heart class="h-7 w-7" />
                </div>

                <h2 class="mt-5 text-xl font-semibold">
                    No favorites yet
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-400">
                    Browse the marketplace and select the heart on any asset you want to save.
                </p>

                <Link
                    href="/images"
                    class="mt-6 inline-flex h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                >
                    Browse Marketplace
                </Link>
            </div>

            <PublicPagination
                class="mt-10"
                :pagination="assets"
            />
        </section>
    </PublicPageLayout>
</template>
