<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type Option = {
    id: number;
    name: string;
};

type LicenseRecord = {
    id: number;
    license_key: string;
    license_name: string;
    license_terms: string | null;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    can_download: boolean;

    image: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        photographer: string | null;
        thumbnail_url: string | null;
        high_res_url: string | null;
        original_url: string | null;
        is_ai_generated: boolean;
        created_at: string | null;
        collection: Option | null;
        categories: Option[];
        tags: Option[];
    };

    order: {
        id: number | null;
        order_number: string | null;
        paid_at: string | null;
        total_formatted: string | null;
    };
};

defineProps<{
    licenseRecord: LicenseRecord;
}>();
</script>

<template>
    <Head :title="licenseRecord.image.title" />

    <div class="space-y-8 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <Link
                    href="/purchases"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    ← Back to My Purchases
                </Link>

                <h1 class="mt-2 text-3xl font-semibold">
                    {{ licenseRecord.image.title }}
                </h1>

                <p
                    v-if="licenseRecord.image.collection"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ licenseRecord.image.collection.name }}
                </p>
            </div>

            <Button
                v-if="licenseRecord.can_download"
                as-child
            >
                <a :href="`/images/${licenseRecord.image.id}/download`">
                    Download
                </a>
            </Button>

            <Button
                v-else
                disabled
                variant="secondary"
            >
                Download Unavailable
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
                <div
                    v-if="licenseRecord.image.thumbnail_url || licenseRecord.image.high_res_url"
                    class="rounded-lg border bg-muted p-4"
                >
                    <img
                        :src="licenseRecord.image.thumbnail_url || licenseRecord.image.high_res_url || ''"
                        :alt="licenseRecord.image.title"
                        class="max-h-[700px] w-full rounded object-contain"
                    />
                </div>

                <div
                    v-else
                    class="flex h-96 items-center justify-center rounded-lg border text-muted-foreground"
                >
                    No preview available.
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        License Details
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                License Type
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.license_name }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                License Key
                            </div>

                            <div class="mt-1 break-all text-sm">
                                {{ licenseRecord.license_key }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Downloads
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.downloads_used }}
                                <span v-if="licenseRecord.download_limit !== null">
                                    / {{ licenseRecord.download_limit }}
                                </span>

                                <span v-else>
                                    / Unlimited
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Starts
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.starts_at || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Expires
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.expires_at || 'Never' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Order Details
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Order Number
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.order.order_number || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Purchased
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.order.paid_at || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Total
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.order.total_formatted || '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Image Details
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Photographer
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.image.photographer || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                AI Generated
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.image.is_ai_generated ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Added
                            </div>

                            <div class="mt-1">
                                {{ licenseRecord.image.created_at || '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Categories
                    </h2>

                    <div
                        v-if="licenseRecord.image.categories.length"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="category in licenseRecord.image.categories"
                            :key="category.id"
                            class="rounded-full border px-3 py-1 text-sm"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <p
                        v-else
                        class="text-sm text-muted-foreground"
                    >
                        No categories assigned.
                    </p>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Tags
                    </h2>

                    <div
                        v-if="licenseRecord.image.tags.length"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in licenseRecord.image.tags"
                            :key="tag.id"
                            class="rounded-full border px-3 py-1 text-sm"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <p
                        v-else
                        class="text-sm text-muted-foreground"
                    >
                        No tags assigned.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="licenseRecord.image.description"
            class="rounded-lg border bg-card p-6 shadow-sm"
        >
            <h2 class="mb-4 text-lg font-semibold">
                Description
            </h2>

            <div class="whitespace-pre-line text-sm leading-7">
                {{ licenseRecord.image.description }}
            </div>
        </div>

        <div
            v-if="licenseRecord.license_terms"
            class="rounded-lg border bg-card p-6 shadow-sm"
        >
            <h2 class="mb-4 text-lg font-semibold">
                License Terms
            </h2>

            <div class="whitespace-pre-line text-sm leading-7">
                {{ licenseRecord.license_terms }}
            </div>
        </div>
    </div>
</template>