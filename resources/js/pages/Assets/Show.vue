<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, FolderOpen, ShieldCheck } from '@lucide/vue';

import AssetCartConfigurator from '@/components/Assets/Public/AssetCartConfigurator.vue';
import AssetDetailSummary from '@/components/Assets/Public/AssetDetailSummary.vue';
import AssetFormatBadge from '@/components/Assets/Public/AssetFormatBadge.vue';
import AssetIncludedFiles from '@/components/Assets/Public/AssetIncludedFiles.vue';
import AssetPurchaseConfidence from '@/components/Assets/Public/AssetPurchaseConfidence.vue';
import AssetPurchaseSummaryCard from '@/components/Assets/Public/AssetPurchaseSummaryCard.vue';
import AssetTechnicalSpecs from '@/components/Assets/Public/AssetTechnicalSpecs.vue';
import MarketplaceSectionHeader from '@/components/Marketplace/MarketplaceSectionHeader.vue';
import PresentationMedia from '@/components/Marketplace/PresentationMedia.vue';
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
        :image="asset.presentation_url || asset.preview?.preview_url || null"
        :canonical-path="`/assets/${asset.slug}`"
    />

    <PublicPageLayout>
        <div class="border-b border-stone-200 bg-stone-50/80 dark:border-stone-800 dark:bg-stone-950/70">
            <div class="mx-auto flex max-w-[1440px] items-center gap-2 overflow-x-auto px-4 py-3 text-xs text-stone-500 sm:px-6 lg:px-8">
                <Link href="/images" class="inline-flex shrink-0 items-center gap-1.5 font-semibold transition hover:text-[var(--brand-accent)]">
                    <ArrowLeft class="h-3.5 w-3.5" />
                    Marketplace
                </Link>
                <ChevronRight class="h-3.5 w-3.5 shrink-0 text-stone-300 dark:text-stone-700" />
                <Link
                    v-if="asset.collection"
                    :href="`/collections/${asset.collection.slug}`"
                    class="shrink-0 transition hover:text-[var(--brand-accent)]"
                >
                    {{ asset.collection.name }}
                </Link>
                <ChevronRight v-if="asset.collection" class="h-3.5 w-3.5 shrink-0 text-stone-300 dark:text-stone-700" />
                <span class="truncate text-stone-700 dark:text-stone-300">{{ asset.title }}</span>
            </div>
        </div>

        <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <section class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_420px] xl:items-start xl:gap-10">
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-[2rem] border border-stone-200 bg-white p-2 shadow-[0_24px_70px_-45px_rgba(28,25,23,0.55)] dark:border-stone-800 dark:bg-stone-900">
                        <div class="px-4 pb-3 pt-3 sm:px-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.17em] text-[var(--brand-accent)]">Protected file previews</p>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">These previews represent the downloadable files and remain watermarked.</p>
                        </div>
                        <AssetFilePreviewGallery
                            :files="asset.files"
                            :asset-title="asset.title"
                            :initial-file-id="asset.preview?.id"
                        />
                    </div>

                    <div class="mt-6 rounded-[1.75rem] border border-stone-200 bg-white p-5 shadow-sm sm:p-6 dark:border-stone-800 dark:bg-stone-900">
                        <AssetDetailSummary :asset="asset" />
                    </div>
                </div>

                <aside class="xl:sticky xl:top-24">
                    <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-[0_24px_70px_-45px_rgba(28,25,23,0.6)] sm:p-7 dark:border-stone-800 dark:bg-stone-900">
                        <Link
                            v-if="asset.collection"
                            :href="`/collections/${asset.collection.slug}`"
                            class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                        >
                            <FolderOpen class="h-4 w-4" />
                            {{ asset.collection.name }}
                        </Link>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-200">
                                {{ asset.asset_type_label }}
                            </span>

                            <span
                                v-if="asset.is_ai_generated"
                                class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-200"
                            >
                                AI Generated
                            </span>
                        </div>

                        <h1 class="mt-5 text-3xl font-semibold leading-tight tracking-[-0.035em] text-stone-950 sm:text-4xl dark:text-white">
                            {{ asset.title }}
                        </h1>

                        <p
                            v-if="asset.description"
                            class="mt-4 text-base leading-7 text-stone-600 dark:text-stone-300"
                        >
                            {{ asset.description }}
                        </p>

                        <div v-if="asset.keywords.length" class="mt-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-400">
                                Keywords
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="keyword in asset.keywords"
                                    :key="keyword"
                                    class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-medium text-stone-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200"
                                >
                                    {{ keyword }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <AssetFormatBadge
                                v-for="format in asset.formats"
                                :key="format"
                                :format="format"
                            />
                        </div>

                        <div class="mt-6 border-t border-stone-100 pt-6 dark:border-stone-800">
                            <AssetPurchaseSummaryCard
                                :offerings="offerings"
                                :formats="asset.formats"
                                :fulfillment-type="asset.fulfillment_type"
                            />
                        </div>

                        <div class="mt-5 flex items-start gap-3 rounded-2xl bg-stone-50 px-4 py-3 text-xs leading-5 text-stone-600 dark:bg-stone-800/70 dark:text-stone-300">
                            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-[var(--brand-accent)]" />
                            Choose the license and files that fit your project. Your purchase history keeps the details available after checkout.
                        </div>
                    </div>
                </aside>
            </section>

            <section class="mt-14 rounded-[2rem] border border-stone-200 bg-stone-50/60 p-5 sm:p-8 dark:border-stone-800 dark:bg-stone-900/40">
                <MarketplaceSectionHeader
                    eyebrow="Configure your purchase"
                    title="Choose the right package"
                    description="Select available options, license terms, and quantity before adding this asset to your cart."
                />

                <div class="mt-8">
                    <AssetCartConfigurator
                        :groups="asset.configurations"
                        :offerings="offerings"
                        :asset-title="asset.title"
                        :allow-quantity="asset.allows_quantity"
                        :collect-shipping-address="asset.collects_shipping_address"
                        :shipping-address-required="asset.shipping_address_required"
                        :fulfillment-type="asset.fulfillment_type"
                    />
                </div>
            </section>

            <section class="mt-14">
                <MarketplaceSectionHeader
                    eyebrow="What you receive"
                    title="Files and technical details"
                    description="Review the available deliverables and technical specifications before choosing a license."
                />

                <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
                    <AssetIncludedFiles :files="asset.files" />
                    <AssetTechnicalSpecs :files="asset.files" />
                </div>
            </section>

            <section class="mt-10">
                <AssetPurchaseConfidence :fulfillment-type="asset.fulfillment_type" />
            </section>

            <section v-if="relatedAssets.length" class="mt-20 border-t border-stone-200 pt-14 dark:border-stone-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <MarketplaceSectionHeader
                        eyebrow="Keep exploring"
                        title="Related assets"
                        description="More marketplace content selected from similar formats, subjects, and collections."
                    />

                    <Link href="/images" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:text-[var(--brand-accent)] dark:text-white">
                        Browse marketplace
                        <ChevronRight class="h-4 w-4" />
                    </Link>
                </div>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="related in relatedAssets"
                        :key="related.id"
                        :href="`/assets/${related.slug}`"
                        class="group overflow-hidden rounded-[1.75rem] border border-stone-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-stone-800 dark:bg-stone-900"
                    >
                        <PresentationMedia
                            :src="related.preview_url"
                            :alt="related.title"
                            aspect-class="aspect-[4/3]"
                            image-class="transition duration-500 group-hover:scale-[1.045]"
                            sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                        />

                        <div class="p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-accent)]">
                                {{ related.asset_type_label }}
                            </p>

                            <h3 class="mt-2 text-lg font-semibold tracking-tight">
                                {{ related.title }}
                            </h3>

                            <div class="mt-4 flex flex-wrap gap-1.5">
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
