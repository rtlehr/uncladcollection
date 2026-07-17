<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    Download,
    Eye,
    Heart,
    Images,
    Sparkles,
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
    <section class="relative isolate overflow-hidden bg-[var(--brand-primary)] text-white">
        <div class="absolute inset-0 -z-20 bg-[linear-gradient(135deg,color-mix(in_srgb,var(--brand-primary)_92%,black),var(--brand-primary))]" />
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_12%_18%,color-mix(in_srgb,var(--brand-accent)_30%,transparent),transparent_30%),radial-gradient(circle_at_86%_8%,color-mix(in_srgb,var(--brand-secondary)_48%,transparent),transparent_38%)]" />

        <div class="mx-auto grid max-w-[1440px] gap-10 px-4 py-10 sm:px-8 sm:py-14 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-12 lg:py-18">
            <div>
                <nav class="flex flex-wrap items-center gap-2 text-sm text-white/60" aria-label="Breadcrumb">
                    <Link href="/" class="transition hover:text-white">Home</Link>
                    <ChevronRight class="h-4 w-4" />
                    <Link href="/images" class="transition hover:text-white">Marketplace</Link>
                    <ChevronRight class="h-4 w-4" />
                    <span class="truncate" aria-current="page">{{ collection.name }}</span>
                </nav>

                <div class="mt-8 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.17em] text-white/85 backdrop-blur">
                    <Sparkles class="h-3.5 w-3.5 text-[var(--brand-accent)]" />
                    Curated collection
                </div>

                <h1 class="mt-5 break-words text-4xl font-semibold leading-[1.04] tracking-[-0.045em] sm:text-6xl">
                    {{ collection.name }}
                </h1>

                <p class="mt-5 max-w-xl text-base leading-8 text-white/72 sm:text-lg">
                    {{
                        collection.description
                        || 'Explore this carefully curated selection from the Unclad Collection marketplace.'
                    }}
                </p>

                <div class="mt-7">
                    <ImageShareActions :title="collection.name" />
                </div>

                <dl class="mt-8 grid grid-cols-2 overflow-hidden rounded-2xl border border-white/12 bg-white/[0.07] backdrop-blur sm:grid-cols-4">
                    <div class="border-b border-white/10 p-4 sm:border-b-0 sm:border-r">
                        <dt class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/55">
                            <Images class="h-4 w-4" />
                            Assets
                        </dt>
                        <dd class="mt-2 text-2xl font-semibold">{{ formatNumber(statistics.images) }}</dd>
                    </div>

                    <div class="border-b border-white/10 p-4 sm:border-b-0 sm:border-r">
                        <dt class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/55">
                            <Eye class="h-4 w-4" />
                            Views
                        </dt>
                        <dd class="mt-2 text-2xl font-semibold">{{ formatNumber(statistics.views) }}</dd>
                    </div>

                    <div class="border-r border-white/10 p-4 sm:border-r">
                        <dt class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/55">
                            <Heart class="h-4 w-4" />
                            Favorites
                        </dt>
                        <dd class="mt-2 text-2xl font-semibold">{{ formatNumber(statistics.favorites) }}</dd>
                    </div>

                    <div class="p-4">
                        <dt class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/55">
                            <Download class="h-4 w-4" />
                            Downloads
                        </dt>
                        <dd class="mt-2 text-2xl font-semibold">{{ formatNumber(statistics.downloads) }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="images.length" class="grid h-[330px] grid-cols-2 grid-rows-2 gap-2 sm:h-[540px] sm:gap-3">
                <Link
                    v-for="(image, index) in images.slice(0, 5)"
                    :key="image.id"
                    :href="`/images/${image.slug}`"
                    :class="[
                        'group relative overflow-hidden rounded-[1.5rem] border border-white/10 bg-white/10 shadow-2xl',
                        index === 0 ? 'row-span-2' : '',
                        index >= 3 ? 'hidden sm:block' : '',
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
                        image-class="object-cover transition duration-700 ease-out group-hover:scale-105"
                    />

                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-black/5" />
                    <div class="absolute inset-x-0 bottom-0 line-clamp-2 p-4 text-xs font-semibold sm:p-5 sm:text-sm">
                        {{ image.title }}
                    </div>
                </Link>
            </div>

            <div v-else class="flex min-h-[360px] items-center justify-center rounded-[2rem] border border-white/10 bg-white/[0.07]">
                <div class="text-center text-white/70">
                    <Images class="mx-auto h-10 w-10" />
                    <p class="mt-3">Assets will appear here when added.</p>
                </div>
            </div>
        </div>
    </section>
</template>
