<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AssetFormatBadge from '@/components/Assets/Public/AssetFormatBadge.vue';
import PresentationMedia from '@/components/Marketplace/PresentationMedia.vue';
import type { RelatedPublicAsset } from '@/types/publicAsset';

defineProps<{
    assets: RelatedPublicAsset[];
}>();
</script>

<template>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <Link
            v-for="asset in assets"
            :key="asset.id"
            :href="asset.href"
            class="group overflow-hidden rounded-[1.75rem] border border-stone-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] focus-visible:ring-offset-2 dark:border-stone-800 dark:bg-stone-900"
        >
            <PresentationMedia
                :src="asset.preview_url"
                :alt="asset.title"
                aspect-class="aspect-[4/3]"
                image-class="transition duration-500 group-hover:scale-[1.045]"
                sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
            />

            <div class="p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-accent)]">
                    {{ asset.asset_type_label }}
                </p>

                <h3 class="mt-2 text-lg font-semibold tracking-tight">
                    {{ asset.title }}
                </h3>

                <p v-if="asset.reason" class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    {{ asset.reason }}
                </p>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    <AssetFormatBadge
                        v-for="format in asset.formats.slice(0, 4)"
                        :key="format"
                        :format="format"
                    />
                </div>
            </div>
        </Link>
    </div>
</template>
