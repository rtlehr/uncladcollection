<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AssetPreview from '@/Components/Assets/AssetPreview.vue';
import { Button } from '@/components/ui/button';

import type { PurchasedAsset } from '@/types/purchase';

defineProps<{
    license: PurchasedAsset;
}>();

function downloadLabel(license: PurchasedAsset): string {
    if (license.download_limit === null) {
        return `${license.downloads_used} / Unlimited`;
    }

    return `${license.downloads_used} / ${license.download_limit}`;
}
</script>

<template>
    <article class="overflow-hidden rounded-lg border bg-card shadow-sm">
        <Link
            :href="`/purchases/${license.image.slug}`"
            class="group block"
        >
            <AssetPreview
                :src="license.image.thumbnail_url ?? license.image.icon_url"
                :alt="license.image.title"
                aspect="square"
                fallback-text="No Preview"
            />
        </Link>

        <div class="space-y-4 p-4">
            <div>
                <Link
                    :href="`/purchases/${license.image.slug}`"
                    class="font-semibold hover:underline"
                >
                    {{ license.image.title }}
                </Link>

                <p class="text-sm text-muted-foreground">
                    {{ license.license_name }}
                </p>
            </div>

            <div class="space-y-1 text-xs text-muted-foreground">
                <div>
                    Order:
                    {{ license.order.order_number ?? '—' }}
                </div>

                <div>
                    Purchased:
                    {{ license.order.paid_at ?? '—' }}
                </div>

                <div>
                    Downloads:
                    {{ downloadLabel(license) }}
                </div>

                <div>
                    Expires:
                    {{ license.expires_at ?? 'Never' }}
                </div>
            </div>

            <div class="flex gap-2">
                <Button as-child class="flex-1">
                    <a :href="`/images/${license.image.id}/download`">
                        Download
                    </a>
                </Button>

                <Button variant="outline" as-child>
                    <Link :href="`/purchases/${license.image.slug}`">
                        Details
                    </Link>
                </Button>
            </div>
        </div>
    </article>
</template>
