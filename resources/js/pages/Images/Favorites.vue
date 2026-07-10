<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AssetCard from '@/Components/Assets/AssetCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

import type { PaginatedFavoriteAssets } from '@/types/asset';

defineProps<{
    images: PaginatedFavoriteAssets;
}>();
</script>

<template>
    <Head title="My Favorites" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="My Favorites"
            description="Images you have saved to your favorites."
        />

        <div
            v-if="images.data.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <AssetCard
                v-for="image in images.data"
                :key="image.id"
                :asset="image"
            />
        </div>

        <EmptyState
            v-else
            title="No favorites yet"
            description="Browse the image library and click the heart icon to save images here."
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link href="/images">
                        Browse Images
                    </Link>
                </Button>
            </template>
        </EmptyState>

        <div
            v-if="images.links.length > 3"
            class="flex flex-wrap justify-center gap-2"
        >
            <Link
                v-for="link in images.links"
                :key="link.label"
                :href="link.url ?? '#'"
                preserve-scroll
                class="rounded-md border px-3 py-2 text-sm transition hover:bg-muted"
                :class="[
                    link.active
                        ? 'bg-primary text-primary-foreground hover:bg-primary'
                        : '',
                    !link.url
                        ? 'pointer-events-none opacity-50'
                        : '',
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
