<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type PurchasedImage = {
    id: number;
    license_key: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;

    image: {
        id: number;
        title: string;
        slug: string;
        photographer: string | null;
        thumbnail_url: string | null;
        icon_url: string | null;
        is_ai_generated: boolean;
        favorites_count: number;
        downloads_count: number;
        purchases_count: number;
        views_count: number;
    };

    order: {
        id: number | null;
        order_number: string | null;
        paid_at: string | null;
        total_formatted: string | null;
    };
};

defineProps<{
    licenses: {
        data: PurchasedImage[];
        links: any[];
        meta: any;
    };
}>();

function downloadLabel(license: PurchasedImage): string {
    if (license.download_limit === null) {
        return `${license.downloads_used} / Unlimited`;
    }

    return `${license.downloads_used} / ${license.download_limit}`;
}
</script>

<template>
    <Head title="My Purchases" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">
                My Purchases
            </h1>

            <p class="mt-1 text-muted-foreground">
                View and download your licensed images.
            </p>
        </div>

        <div
            v-if="licenses.data.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="license in licenses.data"
                :key="license.id"
                class="overflow-hidden rounded-lg border bg-card shadow-sm"
            >
                <Link :href="`/purchases/${license.image.slug}`">
                    <div class="aspect-square bg-muted">
                        <img
                            v-if="license.image.thumbnail_url"
                            :src="license.image.thumbnail_url"
                            :alt="license.image.title"
                            class="h-full w-full object-cover transition hover:scale-105"
                        />

                        <div
                            v-else
                            class="flex h-full items-center justify-center text-sm text-muted-foreground"
                        >
                            No Preview
                        </div>
                    </div>
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
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border bg-card p-12 text-center"
        >
            <h2 class="text-lg font-semibold">
                No Purchases Yet
            </h2>

            <p class="mt-2 text-muted-foreground">
                You have not purchased any images yet.
            </p>

            <Link
                href="/images"
                class="mt-4 inline-block text-primary hover:underline"
            >
                Browse Images
            </Link>
        </div>
    </div>
</template>