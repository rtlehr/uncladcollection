<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import type { GalleryAsset, GalleryPaginationLink } from '@/types/gallery';

interface Item { id: number; note: string | null; asset: GalleryAsset }
interface Pagination {
    data: Item[]; links: GalleryPaginationLink[]; current_page: number; last_page: number;
    per_page: number; from: number | null; to: number | null; total: number;
    next_page_url: string | null; prev_page_url: string | null;
}
const props = defineProps<{
    wish_list: { name: string; description: string | null; owner_name: string | null; items_count: number };
    items: Pagination;
}>();
const assets = computed(() => props.items.data.map((item) => item.asset));
</script>

<template>
    <Head :title="wish_list.name" />
    <PublicPageLayout>
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto max-w-[1440px] px-5 py-14 sm:px-8 lg:px-12">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">Shared wish list</p>
                <h1 class="mt-4 text-3xl font-semibold tracking-[-0.035em] sm:text-5xl">{{ wish_list.name }}</h1>
                <p v-if="wish_list.description" class="mt-4 max-w-2xl text-base leading-7 text-stone-600 dark:text-stone-400">{{ wish_list.description }}</p>
                <p class="mt-3 text-sm text-stone-500">Shared by {{ wish_list.owner_name || 'an Unclad Collection member' }} · {{ wish_list.items_count }} assets</p>
            </div>
        </section>
        <section class="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 lg:px-12">
            <GalleryGrid v-if="assets.length" :assets="assets" />
            <div v-else class="rounded-3xl border border-dashed px-6 py-16 text-center">
                <Heart class="mx-auto h-8 w-8 text-stone-400" />
                <h2 class="mt-4 text-xl font-semibold">No available assets</h2>
                <p class="mt-2 text-sm text-stone-500">This list is empty or its saved assets are no longer available.</p>
                <Link href="/images" class="mt-6 inline-flex min-h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white">Browse Marketplace</Link>
            </div>
            <PublicPagination v-if="items.last_page > 1" class="mt-10" :pagination="items" />
        </section>
    </PublicPageLayout>
</template>
