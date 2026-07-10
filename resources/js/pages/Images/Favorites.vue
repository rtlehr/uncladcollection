<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AssetCard from '@/Components/Assets/AssetCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
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

        <Pagination
            :links="images.links"
            :from="images.from"
            :to="images.to"
            :total="images.total"
            item-label="favorites"
            show-summary
        />
    </div>
</template>
