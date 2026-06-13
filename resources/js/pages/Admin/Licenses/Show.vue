<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type DownloadRecord = {
    id: number;
    download_type: string;
    ip_address: string | null;
    user_agent: string | null;
    downloaded_at: string | null;
};

type LicenseRecord = {
    id: number;
    license_key: string;
    status: string;
    license_name: string;
    license_terms: string | null;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string | null;

    user: {
        id: number;
        name: string;
        email: string;
    } | null;

    image: {
        id: number;
        title: string;
        slug: string;
        photographer: string | null;
        is_ai_generated: boolean;
    } | null;

    order: {
        id: number;
        order_number: string;
        status: string;
        total_formatted: string;
        paid_at: string | null;
    } | null;

    order_item: {
        id: number;
        status: string;
        unit_price_formatted: string;
        total_price_formatted: string;
    } | null;

    downloads: DownloadRecord[];
};

defineProps<{
    licenseRecord: LicenseRecord;
}>();
</script>

<template>
    <Head :title="`License ${licenseRecord.license_key}`" />

    <div class="space-y-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <Link
                    href="/admin/licenses"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    ← Back to Licenses
                </Link>

                <h1 class="mt-2 text-3xl font-semibold">
                    License Details
                </h1>

                <p class="mt-1 break-all text-muted-foreground">
                    {{ licenseRecord.license_key }}
                </p>
            </div>

            <span class="rounded-full border px-3 py-1 text-sm">
                {{ licenseRecord.status }}
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    License
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Type</span>
                        <span>{{ licenseRecord.license_name }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Created</span>
                        <span>{{ licenseRecord.created_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Starts</span>
                        <span>{{ licenseRecord.starts_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Expires</span>
                        <span>{{ licenseRecord.expires_at || 'Never' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Downloads</span>
                        <span>
                            {{ licenseRecord.downloads_used }}
                            <template v-if="licenseRecord.download_limit !== null">
                                / {{ licenseRecord.download_limit }}
                            </template>
                            <template v-else>
                                / Unlimited
                            </template>
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Customer
                </h2>

                <div v-if="licenseRecord.user" class="space-y-2">
                    <div class="font-medium">
                        {{ licenseRecord.user.name }}
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ licenseRecord.user.email }}
                    </div>
                </div>

                <div v-else class="text-muted-foreground">
                    No user attached.
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Image
                </h2>

                <div v-if="licenseRecord.image" class="space-y-2">
                    <div class="font-medium">
                        {{ licenseRecord.image.title }}
                    </div>

                    <div class="text-sm text-muted-foreground">
                        Photographer:
                        {{ licenseRecord.image.photographer || '—' }}
                    </div>

                    <div class="text-sm text-muted-foreground">
                        AI Generated:
                        {{ licenseRecord.image.is_ai_generated ? 'Yes' : 'No' }}
                    </div>

                    <Link
                        :href="`/images/${licenseRecord.image.slug}`"
                        class="text-sm text-primary hover:underline"
                    >
                        View Public Image
                    </Link>
                </div>

                <div v-else class="text-muted-foreground">
                    No image attached.
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Order
                </h2>

                <div v-if="licenseRecord.order" class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Order Number</span>

                        <Link
                            :href="`/admin/orders/${licenseRecord.order.id}`"
                            class="text-primary hover:underline"
                        >
                            {{ licenseRecord.order.order_number }}
                        </Link>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Status</span>
                        <span>{{ licenseRecord.order.status }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Paid</span>
                        <span>{{ licenseRecord.order.paid_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total</span>
                        <span>{{ licenseRecord.order.total_formatted }}</span>
                    </div>
                </div>

                <div v-else class="text-muted-foreground">
                    No order attached.
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Order Item
                </h2>

                <div v-if="licenseRecord.order_item" class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Status</span>
                        <span>{{ licenseRecord.order_item.status }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Unit Price</span>
                        <span>{{ licenseRecord.order_item.unit_price_formatted }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total Price</span>
                        <span>{{ licenseRecord.order_item.total_price_formatted }}</span>
                    </div>
                </div>

                <div v-else class="text-muted-foreground">
                    No order item attached.
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card shadow-sm">
            <div class="border-b p-6">
                <h2 class="text-lg font-semibold">
                    Download History
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-left font-medium">Type</th>
                            <th class="px-4 py-3 text-left font-medium">IP Address</th>
                            <th class="px-4 py-3 text-left font-medium">User Agent</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="download in licenseRecord.downloads"
                            :key="download.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">
                                {{ download.downloaded_at || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ download.download_type }}
                            </td>

                            <td class="px-4 py-3">
                                {{ download.ip_address || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="max-w-xl break-words text-xs text-muted-foreground">
                                    {{ download.user_agent || '—' }}
                                </div>
                            </td>
                        </tr>

                        <tr v-if="licenseRecord.downloads.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No downloads recorded for this license.
                            </td>
                        </tr>
                    </tbody>
                </table>
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