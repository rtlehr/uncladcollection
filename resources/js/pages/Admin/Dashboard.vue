<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    stats: {
        total_revenue_formatted: string;
        total_orders: number;
        paid_orders: number;
        active_licenses: number;
        total_downloads: number;
        total_images: number;
        active_images: number;
        total_users: number;
    };

    recentOrders: Array<{
        id: number;
        order_number: string;
        status: string;
        total_formatted: string;
        created_at: string | null;
        user: {
            name: string;
            email: string;
        } | null;
    }>;

    recentDownloads: Array<{
        id: number;
        download_type: string;
        downloaded_at: string | null;
        user: {
            name: string;
            email: string;
        } | null;
        image: {
            title: string;
            slug: string;
        } | null;
        license: {
            id: number;
            license_name: string;
        } | null;
    }>;

    topPurchasedImages: Array<{
        id: number;
        title: string;
        slug: string;
        purchases_count: number;
        downloads_count: number;
    }>;

    topDownloadedImages: Array<{
        id: number;
        title: string;
        slug: string;
        purchases_count: number;
        downloads_count: number;
    }>;
}>();
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">
                Admin Dashboard
            </h1>

            <p class="mt-1 text-muted-foreground">
                Overview of sales, licenses, downloads, and site activity.
            </p>
        </div>

        <!-- Stats Cards -->

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Total Revenue
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.total_revenue_formatted }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Total Orders
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.total_orders }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Paid Orders
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.paid_orders }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Active Licenses
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.active_licenses }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Total Downloads
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.total_downloads }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Total Images
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.total_images }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Active Images
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.active_images }}
                </div>
            </div>

            <div class="rounded-lg border bg-card p-5">
                <div class="text-sm text-muted-foreground">
                    Total Users
                </div>

                <div class="mt-2 text-3xl font-bold">
                    {{ stats.total_users }}
                </div>
            </div>
        </div>

        <!-- Quick Links -->

        <div class="rounded-lg border bg-card p-6">
            <h2 class="mb-4 text-lg font-semibold">
                Quick Access
            </h2>

            <div class="flex flex-wrap gap-3">
                <Link
                    href="/admin/orders"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    Orders
                </Link>

                <Link
                    href="/admin/licenses"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    Licenses
                </Link>

                <Link
                    href="/admin/downloads"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    Downloads
                </Link>

                <Link
                    href="/admin/images"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    Images
                </Link>

                <Link
                    href="/admin/users"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    Users
                </Link>
            </div>
        </div>

        <!-- Recent Orders -->

        <div class="rounded-lg border bg-card">
            <div class="border-b p-6">
                <h2 class="text-lg font-semibold">
                    Recent Orders
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left">Order</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Total</th>
                            <th class="px-4 py-3 text-left">Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="border-b"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/admin/orders/${order.id}`"
                                    class="text-primary hover:underline"
                                >
                                    {{ order.order_number }}
                                </Link>
                            </td>

                            <td class="px-4 py-3">
                                {{ order.user?.name || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ order.status }}
                            </td>

                            <td class="px-4 py-3">
                                {{ order.total_formatted }}
                            </td>

                            <td class="px-4 py-3">
                                {{ order.created_at }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Downloads -->

        <div class="rounded-lg border bg-card">
            <div class="border-b p-6">
                <h2 class="text-lg font-semibold">
                    Recent Downloads
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left">Image</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">License</th>
                            <th class="px-4 py-3 text-left">Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="download in recentDownloads"
                            :key="download.id"
                            class="border-b"
                        >
                            <td class="px-4 py-3">
                                {{ download.image?.title || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ download.user?.name || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ download.license?.license_name || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ download.downloaded_at }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Images -->

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border bg-card">
                <div class="border-b p-6">
                    <h2 class="text-lg font-semibold">
                        Top Purchased Images
                    </h2>
                </div>

                <div class="p-6">
                    <ul class="space-y-3">
                        <li
                            v-for="image in topPurchasedImages"
                            :key="image.id"
                            class="flex justify-between"
                        >
                            <span>{{ image.title }}</span>

                            <span class="font-medium">
                                {{ image.purchases_count }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="rounded-lg border bg-card">
                <div class="border-b p-6">
                    <h2 class="text-lg font-semibold">
                        Top Downloaded Images
                    </h2>
                </div>

                <div class="p-6">
                    <ul class="space-y-3">
                        <li
                            v-for="image in topDownloadedImages"
                            :key="image.id"
                            class="flex justify-between"
                        >
                            <span>{{ image.title }}</span>

                            <span class="font-medium">
                                {{ image.downloads_count }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>