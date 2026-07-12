<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    Download,
    Eye,
    Heart,
    Images,
} from '@lucide/vue';

import ImageShareActions from '@/components/Gallery/ImageShareActions.vue';
import PerformanceImage from '@/components/Public/PerformanceImage.vue';

import type {
    CollectionHeroImage,
    CollectionStatistics,
    PublicCollection,
} from '@/types/collection';

defineProps<{
    collection: PublicCollection;
    images: CollectionHeroImage[];
    statistics: CollectionStatistics;
}>();

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}
</script>

<template>
    <section class="relative overflow-hidden bg-[var(--brand-primary)] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,color-mix(in_srgb,var(--brand-accent)_24%,transparent),transparent_30%),radial-gradient(circle_at_86%_8%,color-mix(in_srgb,var(--brand-secondary)_44%,transparent),transparent_38%)]" />

        <div class="relative mx-auto grid max-w-[1440px] gap-8 px-4 py-10 sm:gap-10 sm:px-8 sm:py-12 lg:grid-cols-[0.82fr_1.18fr] lg:items-center lg:px-12 lg:py-16">
            <div>
                <nav
                    class="flex flex-wrap items-center gap-2 text-sm text-white/60"
                    aria-label="Breadcrumb"
                >
                    <Link href="/" class="hover:text-white">
                        Home
                    </Link>

                    <ChevronRight class="h-4 w-4" />

                    <Link href="/images" class="hover:text-white">
                        Images
                    </Link>

                    <ChevronRight class="h-4 w-4" />

                    <span aria-current="page">
                        {{ collection.name }}
                    </span>
                </nav>

                <p class="mt-8 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-accent)]">
                    Curated collection
                </p>

                <h1 class="mt-4 break-words text-3xl font-semibold sm:text-4xl leading-[1.06] tracking-[-0.04em] sm:text-6xl">
                    {{ collection.name }}
                </h1>

                <p class="mt-5 max-w-xl text-base leading-8 text-white/75 sm:text-lg">
                    {{
                        collection.description
                        || 'Explore this carefully curated selection from the Unclad Collection image library.'
                    }}
                </p>

                <div class="mt-7">
                    <ImageShareActions :title="collection.name" />
                </div>

                <dl class="mt-7 grid grid-cols-2 gap-4 sm:mt-8 sm:grid-cols-4">
                    <div>
                        <dt class="flex items-center gap-2 text-xs uppercase tracking-wider text-white/55">
                            <Images class="h-4 w-4" />
                            Images
                        </dt>

                        <dd class="mt-2 text-2xl font-semibold">
                            {{ formatNumber(statistics.images) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-2 text-xs uppercase tracking-wider text-white/55">
                            <Eye class="h-4 w-4" />
                            Views
                        </dt>

                        <dd class="mt-2 text-2xl font-semibold">
                            {{ formatNumber(statistics.views) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-2 text-xs uppercase tracking-wider text-white/55">
                            <Heart class="h-4 w-4" />
                            Favorites
                        </dt>

                        <dd class="mt-2 text-2xl font-semibold">
                            {{ formatNumber(statistics.favorites) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-2 text-xs uppercase tracking-wider text-white/55">
                            <Download class="h-4 w-4" />
                            Downloads
                        </dt>

                        <dd class="mt-2 text-2xl font-semibold">
                            {{ formatNumber(statistics.downloads) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="images.length"
                class="grid h-[300px] grid-cols-2 grid-rows-2 gap-2 sm:h-[520px] sm:gap-3"
            >
                <Link
                    v-for="(image, index) in images.slice(0, 5)"
                    :key="image.id"
                    :href="`/images/${image.slug}`"
                    :class="[
                        'group relative overflow-hidden rounded-2xl bg-white/10',
                        index === 0
                            ? 'row-span-2'
                            : '',
                        index >= 3
                            ? 'hidden sm:block'
                            : '',
                    ]"
                >
                    <PerformanceImage
                        v-if="image.image_url"
                        :src="image.image_url"
                        :alt="image.title"
                        :loading="index === 0 ? 'eager' : 'lazy'"
                        :fetchpriority="index === 0 ? 'high' : 'low'"
                        sizes="(min-width: 1024px) 40vw, 50vw"
                        wrapper-class="h-full"
                        image-class="object-cover transition duration-500 group-hover:scale-105"
                    />

                    <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-transparent" />

                    <div class="absolute inset-x-0 bottom-0 line-clamp-2 p-3 text-xs font-semibold sm:p-4 sm:text-sm">
                        {{ image.title }}
                    </div>
                </Link>
            </div>

            <div
                v-else
                class="flex min-h-[360px] items-center justify-center rounded-[2rem] bg-white/10"
            >
                <div class="text-center text-white/70">
                    <Images class="mx-auto h-10 w-10" />
                    <p class="mt-3">Images will appear here when added.</p>
                </div>
            </div>
        </div>
    </section>
</template>
