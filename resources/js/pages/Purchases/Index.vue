<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import PurchasedAssetCard from '@/Components/Purchases/PurchasedAssetCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

import type { PaginatedPurchases } from '@/types/purchase';

defineProps<{
    licenses: PaginatedPurchases;
}>();
</script>

<template>
    <Head title="My Purchases" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="My Purchases"
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
                        Browse Images
                    </Link>
                </Button>
            </template>
        </EmptyState>

        <div
            v-if="licenses.links?.length > 3"
            class="flex flex-wrap justify-center gap-2"
        >
            <Link
                v-for="link in licenses.links"
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
