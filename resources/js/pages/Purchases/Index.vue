<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
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
                Images you have purchased and licensed.
            </p>
        </div>

        <div
            v-if="licenses.data.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Link
                v-for="license in licenses.data"
                :key="license.id"
                :href="`/purchases/${license.image.slug}`"
                class="group overflow-hidden rounded-lg border bg-card shadow-sm transition hover:shadow-md"
            >
                <div class="aspect-square bg-muted">
                    <img
                        v-if="license.image.thumbnail_url"
                        :src="license.image.thumbnail_url"
                        :alt="license.image.title"
                        class="h-full w-full object-cover transition group-hover:scale-105"
                    />

                    <div
                        v-else
                        class="flex h-full items-center justify-center text-sm text-muted-foreground"
                    >
                        No Preview
                    </div>
                </div>

                <div class="space-y-3 p-4">
                    <div>
                        <h2 class="line-clamp-1 font-semibold">
                            {{ license.image.title }}
                        </h2>

                        <p
                            class="text-sm text-muted-foreground"
                        >
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
                            {{ license.downloads_used }}
                            <span v-if="license.download_limit !== null">
                                / {{ license.download_limit }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex justify-between text-xs text-muted-foreground"
                    >
                        <span>
                            {{ formatNumber(license.image.views_count) }}
                            views
                        </span>

                        <span>
                            {{ formatNumber(license.image.downloads_count) }}
                            downloads
                        </span>
                    </div>
                </div>
            </Link>
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