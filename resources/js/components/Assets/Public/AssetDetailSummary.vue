<script setup lang="ts">
import { CalendarDays, Eye, Files, FolderOpen, UserRound } from '@lucide/vue';
import type { PublicAsset } from '@/types/publicAsset';

defineProps<{ asset: PublicAsset }>();

function published(value: string | null): string {
    if (!value) return 'Recently published';
    return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(value));
}
</script>

<template>
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
            <UserRound class="h-4 w-4 text-stone-500" /><p class="mt-3 text-xs text-stone-500">Creator</p><p class="mt-1 font-medium">{{ asset.photographer || 'Unclad Collection' }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
            <FolderOpen class="h-4 w-4 text-stone-500" /><p class="mt-3 text-xs text-stone-500">Collection</p><p class="mt-1 font-medium">{{ asset.collection?.name || 'Independent asset' }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
            <Files class="h-4 w-4 text-stone-500" /><p class="mt-3 text-xs text-stone-500">Files</p><p class="mt-1 font-medium">{{ asset.files.length }} in {{ asset.formats.length }} formats</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
            <Eye class="h-4 w-4 text-stone-500" /><p class="mt-3 text-xs text-stone-500">Views</p><p class="mt-1 font-medium">{{ asset.views_count.toLocaleString() }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
            <CalendarDays class="h-4 w-4 text-stone-500" /><p class="mt-3 text-xs text-stone-500">Published</p><p class="mt-1 font-medium">{{ published(asset.published_at) }}</p>
        </div>
    </section>
</template>
