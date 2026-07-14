<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';

import AssetCartConfigurator from '@/components/Assets/Public/AssetCartConfigurator.vue';
import AssetDetailSummary from '@/components/Assets/Public/AssetDetailSummary.vue';
import AssetFormatBadge from '@/components/Assets/Public/AssetFormatBadge.vue';
import AssetIncludedFiles from '@/components/Assets/Public/AssetIncludedFiles.vue';
import AssetPurchaseConfidence from '@/components/Assets/Public/AssetPurchaseConfidence.vue';
import AssetPurchaseSummaryCard from '@/components/Assets/Public/AssetPurchaseSummaryCard.vue';
import AssetTechnicalSpecs from '@/components/Assets/Public/AssetTechnicalSpecs.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import AssetFilePreviewGallery from '@/components/unclad/assets/AssetFilePreviewGallery.vue';

import type {
    PublicAsset,
    PublicAssetOffering,
    RelatedPublicAsset,
} from '@/types/publicAsset';

defineProps<{
    asset: PublicAsset;
    offerings: PublicAssetOffering[];
    relatedAssets: RelatedPublicAsset[];
}>();
</script>

<template>
    <PublicSeoHead
        :title="asset.title"
        :description="asset.description"
        :image="asset.preview?.preview_url || null"
        :canonical-path="`/assets/${asset.slug}`"
    />

    <PublicPageLayout>
        <main
            class="mx-auto max-w-[1380px] px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
        >
            <section
                class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_400px] xl:items-start"
            >
                <div class="min-w-0">
                    <AssetFilePreviewGallery
                        :files="asset.files"
                        :asset-title="asset.title"
                        :initial-file-id="asset.preview?.id"
                    />
                </div>

                <aside class="xl:sticky xl:top-24">
                    <Link
                        v-if="asset.collection"
                        :href="`/collections/${asset.collection.slug}`"
                        class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                    >
                        <FolderOpen class="h-4 w-4" />
                        {{ asset.collection.name }}
                    </Link>

                    <div class="mt-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold dark:bg-stone-800"
                            >
                                {{ asset.asset_type_label }}
                            </span>

                            <span
                                v-if="asset.is_ai_generated"
                                class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-200"
                            >
                                AI Generated
                            </span>
                        </div>

                        <h1
                            class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl"
                        >
                            {{ asset.title }}
                        </h1>

                        <p
                            v-if="asset.description"
                            class="mt-4 text-base leading-7 text-stone-600 dark:text-stone-300"
                        >
                            {{ asset.description }}
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <AssetFormatBadge
                                v-for="format in asset.formats"
                                :key="format"
                                :format="format"
                            />
                        </div>
                    </div>

                    <div class="mt-6">
                        <AssetPurchaseSummaryCard
                            :offerings="offerings"
                            :formats="asset.formats"
                            :fulfillment-type="
                                asset.fulfillment_type
                            "
                        />
                    </div>
                </aside>
            </section>

            <section class="mt-8">
                <AssetDetailSummary :asset="asset" />
            </section>

            <section class="mt-12">
                <AssetCartConfigurator
                    :groups="asset.configurations"
                    :offerings="offerings"
                    :asset-title="asset.title"
                    :allow-quantity="asset.allows_quantity"
                    :collect-shipping-address="
                        asset.collects_shipping_address
                    "
                    :shipping-address-required="
                        asset.shipping_address_required
                    "
                    :fulfillment-type="
                        asset.fulfillment_type
                    "
                />
            </section>

            <section
                class="mt-10 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]"
            >
                <AssetIncludedFiles :files="asset.files" />
                <AssetTechnicalSpecs :files="asset.files" />
            </section>

            <section class="mt-10">
                <AssetPurchaseConfidence
                    :fulfillment-type="
                        asset.fulfillment_type
                    "
                />
            </section>

            <section
                v-if="relatedAssets.length"
                class="mt-16"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                        >
                            Keep exploring
                        </p>

                        <h2 class="mt-2 text-2xl font-semibold">
                            Related assets
                        </h2>
                    </div>

                    <Link
                        href="/images"
                        class="text-sm font-semibold text-[var(--brand-primary)]"
                    >
                        Browse marketplace
                    </Link>
                </div>

                <div
                    class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="related in relatedAssets"
                        :key="related.id"
                        :href="`/assets/${related.slug}`"
                        class="group overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-stone-800 dark:bg-stone-900"
                    >
                        <div
                            class="aspect-[4/3] overflow-hidden bg-stone-100 dark:bg-stone-800"
                        >
                            <img
                                v-if="related.preview_url"
                                :src="related.preview_url"
                                :alt="related.title"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]"
                            />
                        </div>

                        <div class="p-5">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-stone-500"
                            >
                                {{ related.asset_type_label }}
                            </p>

                            <h3 class="mt-1 font-semibold">
                                {{ related.title }}
                            </h3>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <AssetFormatBadge
                                    v-for="format in related.formats.slice(0, 4)"
                                    :key="format"
                                    :format="format"
                                />
                            </div>
                        </div>
                    </Link>
                </div>
            </section>
        </main>
    </PublicPageLayout>
</template>
