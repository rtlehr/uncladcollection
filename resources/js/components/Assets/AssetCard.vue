<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AssetPreview from '@/Components/Assets/AssetPreview.vue';
import ChipList from '@/Components/Shared/ChipList.vue';

import { formatNumber } from '@/lib/formatNumber';

import type {
    AssetCardData,
    FavoriteAssetData,
    RelatedAssetData,
} from '@/types/asset';

type CardAsset = AssetCardData | FavoriteAssetData | RelatedAssetData;

withDefaults(
    defineProps<{
        asset: CardAsset;
        showCollection?: boolean;
        showCategories?: boolean;
        showStats?: boolean;
        showAiBadge?: boolean;
    }>(),
    {
        showCollection: true,
        showCategories: true,
        showStats: true,
        showAiBadge: true,
    },
);

function hasCollection(asset: CardAsset): asset is AssetCardData | FavoriteAssetData {
    return 'collection' in asset;
}

function hasCategories(asset: CardAsset): asset is AssetCardData | FavoriteAssetData {
    return 'categories' in asset;
}
</script>

<template>
    <Link
        :href="`/images/${asset.slug}`"
        class="group overflow-hidden rounded-lg border bg-card shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <AssetPreview
            :src="asset.thumbnail_url ?? asset.icon_url ?? null"
            :alt="asset.title"
            aspect="square"
        />

        <div class="space-y-3 p-4">
            <div>
                <h2 class="line-clamp-1 font-semibold">
                    {{ asset.title }}
                </h2>

                <p
                    v-if="showCollection && hasCollection(asset)"
                    class="line-clamp-1 text-xs text-muted-foreground"
                >
                    {{ asset.collection?.name ?? 'Unassigned' }}
                </p>
            </div>

            <div
                v-if="
                    (showAiBadge && asset.is_ai_generated)
                    || (
                        showCategories
                        && hasCategories(asset)
                        && asset.categories.length
                    )
                "
                class="space-y-2"
            >
                <span
                    v-if="showAiBadge && asset.is_ai_generated"
                    class="inline-flex rounded-full border px-2 py-0.5 text-xs"
                >
                    AI
                </span>

                <ChipList
                    v-if="showCategories && hasCategories(asset)"
                    :items="asset.categories.slice(0, 2)"
                    size="sm"
                />
            </div>

            <div
                v-if="showStats"
                class="flex justify-between gap-3 text-xs text-muted-foreground"
            >
                <span>
                    {{ formatNumber(asset.views_count) }} views
                </span>

                <span>
                    {{ formatNumber(asset.favorites_count) }} favorites
                </span>
            </div>
        </div>
    </Link>
</template>
