<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Images,
} from '@lucide/vue';

import type { RelatedCollection } from '@/types/collection';

defineProps<{
    collection: RelatedCollection;
}>();
</script>

<template>
    <Link
        :href="`/collections/${collection.slug}`"
        class="public-card group overflow-hidden rounded-3xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
    >
        <div class="overflow-hidden bg-stone-200 dark:bg-stone-800">
            <img
                v-if="collection.cover_image_url"
                :src="collection.cover_image_url"
                :alt="collection.name"
                loading="lazy"
                class="public-image-zoom aspect-[16/10] w-full object-cover"
            />

            <div
                v-else
                class="flex aspect-[16/10] items-center justify-center"
            >
                <Images class="h-9 w-9 text-stone-400" />
            </div>
        </div>

        <div class="p-6">
            <div class="text-xs font-semibold uppercase tracking-wide text-[var(--brand-accent)]">
                {{ collection.images_count }} images
            </div>

            <h3 class="mt-2 text-2xl font-semibold transition group-hover:text-[var(--brand-accent)]">
                {{ collection.name }}
            </h3>

            <p class="mt-3 line-clamp-3 text-sm leading-6 text-stone-600 dark:text-stone-400">
                {{
                    collection.description
                    || 'Explore this related image collection.'
                }}
            </p>

            <div class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                Explore Collection
                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
            </div>
        </div>
    </Link>
</template>
