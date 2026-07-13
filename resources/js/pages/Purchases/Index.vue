<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import PurchasedAssetCard from '@/Components/Purchases/PurchasedAssetCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { Button } from '@/components/ui/button';

import type { PaginatedPurchases } from '@/types/purchase';

defineProps<{
    licenses: PaginatedPurchases;
}>();
</script>

<template>
    <Head title="My Library" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="My Library"
            description="View and download your licensed images."
        />

        <div
            v-if="licenses.data.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <PurchasedAssetCard
                v-for="license in licenses.data"
                :key="license.id"
                :license="license"
            />
        </div>

        <EmptyState
            v-else
            title="No purchases yet"
            description="You have not purchased any images yet."
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link href="/images">
                        Browse Marketplace
                    </Link>
                </Button>
            </template>
        </EmptyState>

        <Pagination
            :links="licenses.links"
            item-label="purchases"
        />
    </div>
</template>
