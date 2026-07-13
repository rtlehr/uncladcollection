<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import AssetFormatBadge from '@/components/Assets/Public/AssetFormatBadge.vue';
import AssetOfferingCard from '@/components/Assets/Public/AssetOfferingCard.vue';
import AssetTechnicalSpecs from '@/components/Assets/Public/AssetTechnicalSpecs.vue';
import AssetIncludedFiles from '@/components/Assets/Public/AssetIncludedFiles.vue';
import AssetDetailSummary from '@/components/Assets/Public/AssetDetailSummary.vue';
import AssetFilePreviewGallery from '@/components/unclad/assets/AssetFilePreviewGallery.vue';
import type { PublicAsset, PublicAssetOffering, RelatedPublicAsset } from '@/types/publicAsset';

defineProps<{ asset: PublicAsset; offerings: PublicAssetOffering[]; relatedAssets: RelatedPublicAsset[] }>();
</script>
<template>
    <PublicSeoHead :title="asset.title" :description="asset.description" :image="asset.preview?.preview_url || null" :canonical-path="`/assets/${asset.slug}`" />
    <PublicPageLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-10 xl:grid-cols-[minmax(0,1fr)_380px] xl:items-start">
                <div><AssetFilePreviewGallery :files="asset.files" :asset-title="asset.title" :initial-file-id="asset.preview?.id" /></div>
                <aside class="xl:sticky xl:top-24">
                    <Link v-if="asset.collection" :href="`/collections/${asset.collection.slug}`" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"><FolderOpen class="h-4 w-4" />{{ asset.collection.name }}</Link>
                    <div class="mt-3 flex flex-wrap items-center gap-3"><h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ asset.title }}</h1><span class="rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold dark:bg-stone-800">{{ asset.asset_type_label }}</span><span v-if="asset.is_ai_generated" class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-200">AI Generated</span></div>
                    <p v-if="asset.description" class="mt-5 text-base leading-7 text-stone-600 dark:text-stone-300">{{ asset.description }}</p>
                    <div class="mt-5 flex flex-wrap gap-2"><AssetFormatBadge v-for="format in asset.formats" :key="format" :format="format" /></div>
                    <div class="mt-6 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Choose your license</p><h2 class="mt-2 text-xl font-semibold">Ready when you are</h2><p class="mt-2 text-sm leading-6 text-stone-500">Compare file packages and usage rights below.</p><a v-if="asset.legacy_image_url" :href="asset.legacy_image_url" class="mt-5 inline-flex h-11 w-full items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white">View purchase options</a><button v-else disabled class="mt-5 h-11 w-full rounded-full bg-stone-200 px-5 text-sm font-semibold text-stone-500 dark:bg-stone-800">Purchasing coming next</button></div>
                </aside>
            </div>

            <div class="mt-10"><AssetDetailSummary :asset="asset" /></div>
            <div class="mt-10 grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]"><AssetIncludedFiles :files="asset.files" /><AssetTechnicalSpecs :files="asset.files" /></div>

            <section class="mt-16"><div class="max-w-2xl"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Choose your license</p><h2 class="mt-2 text-3xl font-semibold">Find the right package</h2><p class="mt-3 text-stone-600 dark:text-stone-300">Compare included files, package size, download limits, and expiration in one place.</p></div><div v-if="offerings.length" class="mt-7 grid gap-5 md:grid-cols-2 xl:grid-cols-3"><AssetOfferingCard v-for="(offering,index) in offerings" :key="offering.id" :offering="offering" :purchase-url="asset.legacy_image_url" :featured="index === 1 && offerings.length > 2" /></div><div v-else class="mt-7 rounded-3xl border border-dashed border-stone-300 p-8 text-center text-stone-500 dark:border-stone-700">License offerings are being prepared for this asset.</div></section>

            <section v-if="relatedAssets.length" class="mt-16"><div class="flex items-end justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Keep exploring</p><h2 class="mt-2 text-2xl font-semibold">Related assets</h2></div><Link href="/images" class="text-sm font-semibold text-[var(--brand-primary)]">Browse marketplace</Link></div><div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><Link v-for="related in relatedAssets" :key="related.id" :href="`/assets/${related.slug}`" class="group overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-stone-800 dark:bg-stone-900"><div class="aspect-[4/3] overflow-hidden bg-stone-100 dark:bg-stone-800"><img v-if="related.preview_url" :src="related.preview_url" :alt="related.title" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]" /></div><div class="p-5"><p class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ related.asset_type_label }}</p><h3 class="mt-1 font-semibold">{{ related.title }}</h3><div class="mt-3 flex flex-wrap gap-1.5"><AssetFormatBadge v-for="format in related.formats.slice(0,4)" :key="format" :format="format" /></div></div></Link></div></section>
        </div>
    </PublicPageLayout>
</template>
