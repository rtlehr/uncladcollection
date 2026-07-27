<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import DiscoveryAssetGrid from '@/components/Discovery/DiscoveryAssetGrid.vue';
import PublicSectionHeading from '@/components/Public/PublicSectionHeading.vue';
import type { HomeDiscoveryCollectionPlacement, HomeImage } from '@/types/home';
import type { HomepageDiscoverySection } from '@/types/homeDiscovery';
import type { RelatedPublicAsset } from '@/types/publicAsset';

defineProps<{ sections: HomepageDiscoverySection[] }>();

const asCollections = (items: HomepageDiscoverySection['items']) => items as HomeDiscoveryCollectionPlacement[];
const asAssets = (items: HomepageDiscoverySection['items']) => items as RelatedPublicAsset[];
const asImages = (items: HomepageDiscoverySection['items']) => items as HomeImage[];
</script>

<template>
    <div class="homepage-discovery-sections">
        <section
            v-for="section in sections"
            :key="section.key"
            class="border-b border-stone-200 py-20 dark:border-stone-800 lg:py-28"
            :class="section.key === 'trending' || section.key === 'primary_collections' ? 'bg-white dark:bg-stone-900' : 'bg-stone-50 dark:bg-stone-950'"
            :data-discovery-section="section.key"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <template v-if="section.key === 'primary_collections'">
                    <div class="space-y-8">
                        <article
                            v-for="placement in asCollections(section.items)"
                            :key="placement.id"
                            class="grid overflow-hidden rounded-[2rem] border border-stone-200 bg-stone-950 text-white shadow-xl lg:grid-cols-[1.05fr_.95fr] dark:border-stone-800"
                        >
                            <div class="flex flex-col justify-center p-8 sm:p-12 lg:p-16">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">{{ placement.eyebrow }}</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">{{ placement.heading }}</h2>
                                <p v-if="placement.description" class="mt-5 max-w-2xl text-base leading-8 text-white/75">{{ placement.description }}</p>
                                <div class="mt-7 flex items-center gap-4">
                                    <Link :href="placement.href" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-stone-100">
                                        {{ placement.call_to_action }}<ArrowRight class="h-4 w-4" aria-hidden="true" />
                                    </Link>
                                    <span class="text-sm text-white/60">{{ placement.collection.assets_count }} assets</span>
                                </div>
                            </div>
                            <div class="min-h-[280px] bg-stone-800 lg:min-h-[420px]">
                                <img v-if="placement.collection.cover_image_url" :src="placement.collection.cover_image_url" :alt="`${placement.collection.name} collection`" class="h-full w-full object-cover" />
                            </div>
                        </article>
                    </div>
                </template>

                <template v-else-if="section.key === 'secondary_collections'">
                    <PublicSectionHeading :eyebrow="section.eyebrow ?? ''" :title="section.heading ?? section.label" :description="section.description ?? ''" />
                    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Link v-for="placement in asCollections(section.items)" :key="placement.id" :href="placement.href" class="group overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl dark:border-stone-800 dark:bg-stone-900">
                            <div class="aspect-[16/10] overflow-hidden bg-stone-200 dark:bg-stone-800">
                                <img v-if="placement.collection.cover_image_url" :src="placement.collection.cover_image_url" :alt="`${placement.collection.name} collection`" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            </div>
                            <div class="p-6"><div class="text-xs font-semibold uppercase tracking-wider text-[var(--brand-accent)]">{{ placement.eyebrow }}</div><h3 class="mt-2 text-2xl font-semibold">{{ placement.heading }}</h3><p class="mt-3 line-clamp-3 text-sm leading-7 text-stone-600 dark:text-stone-400">{{ placement.description }}</p><div class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">{{ placement.call_to_action }}<ArrowRight class="h-4 w-4" /></div></div>
                        </Link>
                    </div>
                </template>

                <template v-else-if="section.key === 'featured_assets'">
                    <PublicSectionHeading :eyebrow="section.eyebrow ?? ''" :title="section.heading ?? section.label" :description="section.description ?? ''" />
                    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <Link v-for="image in asImages(section.items)" :key="image.id" :href="`/images/${image.slug}?discovery_source=featured_assets`" class="group relative aspect-[4/3] overflow-hidden rounded-[1.75rem] bg-stone-900">
                            <img v-if="image.thumbnail_url || image.icon_url" :src="image.thumbnail_url ?? image.icon_url ?? ''" :alt="image.title" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent" />
                            <div class="absolute inset-x-0 bottom-0 p-5 text-white"><h3 class="font-semibold">{{ image.title }}</h3><p class="mt-1 text-xs text-white/70">{{ image.photographer ? `By ${image.photographer}` : image.collection?.name }}</p></div>
                        </Link>
                    </div>
                </template>

                <template v-else>
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <PublicSectionHeading :eyebrow="section.eyebrow ?? ''" :title="section.heading ?? section.label" :description="section.description ?? ''" />
                        <Link :href="section.key === 'trending' ? '/images?sort=trending&discovery_source=trending' : '/images?discovery_source=recommended_for_you'" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">Explore the marketplace<ArrowRight class="h-4 w-4" /></Link>
                    </div>
                    <DiscoveryAssetGrid class="mt-10" :assets="asAssets(section.items)" />
                </template>
            </div>
        </section>
    </div>
</template>
