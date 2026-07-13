<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Heart, Images } from '@lucide/vue';
import { computed, ref } from 'vue';

import AssetFormatBadges from '@/components/Assets/Public/AssetFormatBadges.vue';
import PerformanceImage from '@/components/Public/PerformanceImage.vue';
import type { GalleryAsset } from '@/types/gallery';

const props = withDefaults(defineProps<{
    asset: GalleryAsset;
    showCollection?: boolean;
}>(), {
    showCollection: true,
});

const page = usePage();
const favoriteProcessing = ref(false);
const favoriteState = ref(props.asset.is_favorited);
const favoriteCount = ref(props.asset.favorites_count);

const isAuthenticated = computed(() => Boolean((page.props.auth as any)?.user));

const formattedPrice = computed(() => {
    if (props.asset.starting_price_cents === null) return null;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.asset.currency || 'USD',
    }).format(props.asset.starting_price_cents / 100);
});

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
    <article class="public-card group overflow-hidden rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="relative overflow-hidden bg-stone-200 dark:bg-stone-800">
            <Link :href="asset.href" class="block" prefetch="hover">
                <PerformanceImage
                    v-if="asset.preview_url"
                    :src="asset.preview_url"
                    :alt="asset.title"
                    loading="lazy"
                    fetchpriority="low"
                    sizes="(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 520px) 50vw, 100vw"
                    wrapper-class="aspect-[4/3]"
                    image-class="public-image-zoom object-cover"
                />
                <div v-else class="flex aspect-[4/3] items-center justify-center" role="img" :aria-label="`${asset.title}: preview unavailable`">
                    <Images class="h-9 w-9 text-stone-400" />
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent opacity-70 transition group-hover:opacity-95" />
                <div class="absolute inset-x-0 bottom-0 hidden translate-y-2 p-4 text-white opacity-0 transition duration-300 sm:block sm:group-hover:translate-y-0 sm:group-hover:opacity-100">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold">
                        View asset
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </span>
                </div>
            </Link>

            <button
                v-if="asset.is_favoritable"
                type="button"
                class="public-icon-button absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-black/45 text-white shadow-lg backdrop-blur transition hover:bg-black/70 disabled:opacity-50"
                :aria-label="favoriteState ? 'Remove from favorites' : 'Add to favorites'"
                :aria-pressed="favoriteState"
                :disabled="favoriteProcessing"
                @click="toggleFavorite"
            >
                <Heart :class="['h-5 w-5', favoriteState ? 'fill-current' : '']" />
            </button>

            <div class="absolute left-3 top-3 flex flex-col items-start gap-2">
                <span class="rounded-full bg-black/55 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur">
                    {{ asset.asset_type_label }}
                </span>
                <span v-if="asset.is_ai_generated" class="rounded-full bg-black/55 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur">
                    AI Generated
                </span>
            </div>

            <div class="absolute bottom-3 right-3">
                <AssetFormatBadges :formats="asset.formats" :limit="3" />
            </div>
        </div>

        <div class="p-4 sm:p-5">
            <div v-if="showCollection && asset.collection" class="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--brand-accent)]">
                {{ asset.collection.name }}
            </div>

            <Link :href="asset.href" prefetch="hover" class="block line-clamp-2 text-base font-semibold leading-snug transition hover:text-[var(--brand-accent)] sm:text-lg">
                {{ asset.title }}
            </Link>

            <div class="mt-2 flex items-center justify-between gap-3">
                <p class="min-w-0 truncate text-sm text-stone-500 dark:text-stone-400">
                    {{ asset.photographer ? `By ${asset.photographer}` : 'Unclad Collection' }}
                </p>
                <p v-if="formattedPrice" class="shrink-0 text-sm font-semibold text-stone-800 dark:text-stone-100">
                    From {{ formattedPrice }}
                </p>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-stone-100 pt-3 text-[11px] text-stone-500 sm:mt-4 sm:pt-4 sm:text-xs dark:border-stone-800 dark:text-stone-400">
                <span>{{ asset.views_count.toLocaleString() }} views</span>
                <span class="inline-flex items-center gap-1">
                    <Heart class="h-3.5 w-3.5" />
                    {{ favoriteCount.toLocaleString() }}
                </span>
            </div>
        </div>
    </article>
</template>
