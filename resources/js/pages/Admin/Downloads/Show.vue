<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type DownloadRecord = {
    id: number;
    download_type: string;
    ip_address: string | null;
    user_agent: string | null;
    downloaded_at: string | null;
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
        icon_url: string | null;
    } | null;

    license: {
        id: number;
        license_key: string;
        license_name: string;
        status: string;
        downloads_used: number;
        download_limit: number | null;
        order_id: number | null;
    } | null;

    order: {
        id: number;
        order_number: string;
        status: string;
        total_formatted: string;
        paid_at: string | null;
    } | null;
};

defineProps<{
    downloadRecord: DownloadRecord;
}>();
</script>

<template>
    <Head :title="`Download #${downloadRecord.id}`" />

    <div class="space-y-6 p-6">
        <div>
            <Link
                href="/admin/downloads"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← Back to Downloads
            </Link>

            <h1 class="mt-2 text-3xl font-semibold">
                Download Details
            </h1>

            <p class="mt-1 text-muted-foreground">
                Download record #{{ downloadRecord.id }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Download
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Downloaded</span>
                        <span>{{ downloadRecord.downloaded_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Type</span>
                        <span>{{ downloadRecord.download_type }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">IP Address</span>
                        <span>{{ downloadRecord.ip_address || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Created</span>
                        <span>{{ downloadRecord.created_at || '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    User
                </h2>

                <div v-if="downloadRecord.user" class="space-y-2">
                    <div class="font-medium">
                        {{ downloadRecord.user.name }}
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ downloadRecord.user.email }}
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

                <div v-if="downloadRecord.image" class="space-y-3">
                    <div class="font-medium">
                        {{ downloadRecord.image.title }}
                    </div>

                    <div class="text-sm text-muted-foreground">
                        Photographer:
                        {{ downloadRecord.image.photographer || '—' }}
                    </div>

                    <img
                        v-if="downloadRecord.image.icon_url"
                        :src="downloadRecord.image.icon_url"
                        :alt="downloadRecord.image.title"
                        class="h-32 w-auto rounded-md border object-contain"
                    />

                    <Link
                        :href="`/images/${downloadRecord.image.slug}`"
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
                    License
                </h2>

                <div v-if="downloadRecord.license" class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">License Type</span>

                        <Link
                            :href="`/admin/licenses/${downloadRecord.license.id}`"
                            class="text-primary hover:underline"
                        >
                            {{ downloadRecord.license.license_name }}
                        </Link>
                    </div>

                    <div class="space-y-1">
                        <div class="text-muted-foreground">
                            License Key
                        </div>

                        <div class="break-all text-xs">
                            {{ downloadRecord.license.license_key }}
                        </div>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Status</span>
                        <span>{{ downloadRecord.license.status }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Downloads Used</span>
                        <span>
                            {{ downloadRecord.license.downloads_used }}

                            <template v-if="downloadRecord.license.download_limit !== null">
                                / {{ downloadRecord.license.download_limit }}
                            </template>

                            <template v-else>
                                / Unlimited
                            </template>
                        </span>
                    </div>
                </div>

                <div v-else class="text-muted-foreground">
                    No license attached.
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Order
                </h2>

                <div v-if="downloadRecord.order" class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Order Number</span>

                        <Link
                            :href="`/admin/orders/${downloadRecord.order.id}`"
                            class="text-primary hover:underline"
                        >
                            {{ downloadRecord.order.order_number }}
                        </Link>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Status</span>
                        <span>{{ downloadRecord.order.status }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Paid</span>
                        <span>{{ downloadRecord.order.paid_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total</span>
                        <span>{{ downloadRecord.order.total_formatted }}</span>
                    </div>
                </div>

                <div v-else class="text-muted-foreground">
                    No order attached.
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">
                User Agent
            </h2>

            <div
                v-if="downloadRecord.user_agent"
                class="break-words rounded-md border bg-muted/40 p-4 text-sm"
            >
                {{ downloadRecord.user_agent }}
            </div>

            <p v-else class="text-sm text-muted-foreground">
                No user agent recorded.
            </p>
        </div>
    </div>
</template>