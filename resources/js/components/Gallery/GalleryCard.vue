<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Heart, Play } from '@lucide/vue';
import { computed, ref } from 'vue';

import AssetFormatBadges from '@/components/Assets/Public/AssetFormatBadges.vue';
import MarketplaceMediaTypeBadge from '@/components/Marketplace/MarketplaceMediaTypeBadge.vue';
import MarketplacePrice from '@/components/Marketplace/MarketplacePrice.vue';
import PresentationMedia from '@/components/Marketplace/PresentationMedia.vue';
import WishListPickerButton from '@/components/WishLists/WishListPickerButton.vue';
import type { GalleryAsset } from '@/types/gallery';

const props = withDefaults(defineProps<{
    asset: GalleryAsset;
    showCollection?: boolean;
}>(), {
    showCollection: true,
});

const page = usePage();
const favoriteProcessing = ref(false);
const favoriteState = ref(Boolean(props.asset.is_favorited));
const favoriteCount = ref(Number(props.asset.favorites_count ?? 0));

const isAuthenticated = computed(() => Boolean((page.props.auth as any)?.user));
const hasVideo = computed(() => (props.asset.formats ?? []).some((format) => ['MP4', 'MOV', 'WEBM', 'OGG'].includes(format.toUpperCase())));
const formattedViewsCount = computed(() => Number(props.asset.views_count ?? 0).toLocaleString());
const formattedFavoriteCount = computed(() => Number(favoriteCount.value ?? 0).toLocaleString());

function toggleFavorite(): void {
    if (!props.asset.is_favoritable || !props.asset.favorite_url || !props.asset.unfavorite_url) return;

    if (!isAuthenticated.value) {
        router.visit('/login');
        return;
    }

    if (favoriteProcessing.value) return;

    favoriteProcessing.value = true;
    const wasFavorited = favoriteState.value;
    favoriteState.value = !wasFavorited;
    favoriteCount.value = Math.max(0, favoriteCount.value + (wasFavorited ? -1 : 1));

    const method = wasFavorited ? 'delete' : 'post';
    const url = wasFavorited ? props.asset.unfavorite_url : props.asset.favorite_url;

    router[method](url, {
        preserveScroll: true,
        preserveState: true,
        only: [],
        onError: () => {
            favoriteState.value = wasFavorited;
            favoriteCount.value = props.asset.favorites_count;
        },
        onFinish: () => {
            favoriteProcessing.value = false;
        },
    } as any);
}
</script>

<template>
    <article class="group flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-stone-200/90 bg-white shadow-[0_10px_30px_-24px_rgba(28,25,23,0.45)] transition duration-300 hover:-translate-y-1.5 hover:border-stone-300 hover:shadow-[0_24px_55px_-30px_rgba(28,25,23,0.55)] focus-within:ring-2 focus-within:ring-[var(--brand-accent)] focus-within:ring-offset-2 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-stone-700">
        <div class="relative overflow-hidden bg-stone-200 dark:bg-stone-800">
            <Link :href="asset.href" class="block focus:outline-none" prefetch="hover" :aria-label="`View ${asset.title}`">
                <PresentationMedia
                    :src="asset.preview_url"
                    :alt="asset.title"
                    loading="lazy"
                    fetchpriority="low"
                    sizes="(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 520px) 50vw, 100vw"
                    aspect-class="aspect-[4/3]"
                    image-class="transition duration-500 ease-out group-hover:scale-[1.045]"
                />

                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/5 to-black/20 opacity-70 transition duration-300 group-hover:opacity-90" />

                <div v-if="hasVideo" class="absolute inset-0 flex items-center justify-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-white/60 bg-black/35 text-white shadow-xl backdrop-blur transition duration-300 group-hover:scale-110 group-hover:bg-black/60">
                        <Play class="ml-0.5 h-6 w-6 fill-current" aria-hidden="true" />
                    </span>
                </div>

                <div class="absolute inset-x-0 bottom-0 flex translate-y-2 items-end justify-between gap-3 p-4 text-white opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em]">
                        View asset
                        <ArrowUpRight class="h-3.5 w-3.5" aria-hidden="true" />
                    </span>
                </div>
            </Link>

            <div v-if="asset.is_favoritable" class="absolute right-3 top-3 z-20 flex flex-col gap-2">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-black/45 text-white shadow-lg backdrop-blur transition hover:scale-105 hover:bg-black/75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white disabled:opacity-50"
                :aria-label="favoriteState ? `Remove ${asset.title} from favorites` : `Add ${asset.title} to favorites`"
                :aria-pressed="favoriteState"
                :disabled="favoriteProcessing"
                @click="toggleFavorite"
            >
                <Heart :class="['h-5 w-5', favoriteState ? 'fill-current' : '']" aria-hidden="true" />
            </button>
            <WishListPickerButton v-if="isAuthenticated" :asset-id="asset.id" :asset-title="asset.title" />
            </div>

            <div class="absolute left-3 top-3 flex flex-wrap items-center gap-2 pr-14">
                <MarketplaceMediaTypeBadge :type="asset.asset_type" :label="asset.asset_type_label" />

                <span v-if="asset.is_featured" class="rounded-full bg-amber-400 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.08em] text-stone-950 shadow-sm">
                    Featured
                </span>

                <span v-if="asset.is_ai_generated" class="rounded-full bg-violet-600/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-white shadow-sm backdrop-blur">
                    AI Generated
                </span>
            </div>

            <div class="absolute bottom-3 right-3">
                <AssetFormatBadges :formats="asset.formats" :limit="3" />
            </div>
        </div>

        <div class="flex flex-1 flex-col p-5 sm:p-5">
            <div v-if="showCollection && asset.collection" class="mb-2 truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-accent)]">
                {{ asset.collection.name }}
            </div>

            <Link :href="asset.href" prefetch="hover" class="block line-clamp-2 text-lg font-semibold leading-snug tracking-[-0.015em] text-stone-950 transition hover:text-[var(--brand-accent)] focus-visible:outline-none focus-visible:underline dark:text-white">
                {{ asset.title }}
            </Link>

            <p class="mt-2 min-w-0 truncate text-sm text-stone-500 dark:text-stone-400">
                {{ asset.photographer ? `By ${asset.photographer}` : 'Unclad Collection' }}
            </p>

            <div class="mt-auto pt-5">
                <div class="flex items-end justify-between gap-4 border-t border-stone-100 pt-4 dark:border-stone-800">
                    <div class="flex items-center gap-3 text-xs text-stone-500 dark:text-stone-400">
                        <span>{{ formattedViewsCount }} views</span>
                        <span class="inline-flex items-center gap-1.5">
                            <Heart class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ formattedFavoriteCount }}
                            <span class="sr-only">favorites</span>
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-400">From</span>
                        <MarketplacePrice :price-cents="asset.starting_price_cents" :currency="asset.currency" compact />
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
