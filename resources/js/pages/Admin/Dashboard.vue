<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    Ban,
    CircleDollarSign,
    Download,
    Image,
    Images,
    KeyRound,
    ShoppingCart,
    Users,
} from '@lucide/vue';

import MetricCard from '@/Components/Shared/MetricCard.vue';
import AdminCommandCenter, { type AdminToolGroup } from '@/components/admin/AdminCommandCenter.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminDashboardDownload,
    AdminDashboardImage,
    AdminDashboardOrder,
    AdminDashboardStats,
} from '@/types/adminDashboard';

defineProps<{
    adminTools: AdminToolGroup[];
    stats: AdminDashboardStats;
    recentOrders: AdminDashboardOrder[];
    recentDownloads: AdminDashboardDownload[];
    topPurchasedImages: AdminDashboardImage[];
    topDownloadedImages: AdminDashboardImage[];
}>();

function formatDownloadType(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Admin Dashboard"
            description="Search and open every administration tool, then monitor marketplace activity."
        />

        <AdminCommandCenter :groups="adminTools" />

        <section class="grid gap-4 xl:grid-cols-4">
            <MetricCard
                label="Total Revenue"
                :value="stats.total_revenue_formatted"
                description="All paid image-license orders"
                emphasized
                size="lg"
                class="xl:col-span-2"
            >
                <template #icon>
                    <BadgeDollarSign class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Total Orders"
                :value="stats.total_orders.toLocaleString()"
                description="All order records"
                size="lg"
            >
                <template #icon>
                    <ShoppingCart class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Paid Orders"
                :value="stats.paid_orders.toLocaleString()"
                description="Successfully completed purchases"
                size="lg"
            >
                <template #icon>
                    <CircleDollarSign class="h-5 w-5" />
                </template>
            </MetricCard>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                label="Active Licenses"
                :value="stats.active_licenses.toLocaleString()"
            >
                <template #icon>
                    <KeyRound class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Total Downloads"
                :value="stats.total_downloads.toLocaleString()"
            >
                <template #icon>
                    <Download class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Total Images"
                :value="stats.total_images.toLocaleString()"
            >
                <template #icon>
                    <Images class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Active Images"
                :value="stats.active_images.toLocaleString()"
            >
                <template #icon>
                    <Image class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Total Users"
                :value="stats.total_users.toLocaleString()"
                class="sm:col-span-2 xl:col-span-1"
            >
                <template #icon>
                    <Users class="h-5 w-5" />
                </template>
            </MetricCard>
        </section>

        <ShowSection
            title="Quick Access"
            description="Open the most frequently used administration areas."
        >
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <Button
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-4"
                    as-child
                >
                    <Link href="/admin/orders">
                        <ShoppingCart class="h-5 w-5" />

                        <span class="text-left">
                            <span class="block font-medium">Orders</span>
                            <span class="block text-xs text-muted-foreground">
                                Review purchases
                            </span>
                        </span>
                    </Link>
                </Button>

                <Button
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-4"
                    as-child
                >
                    <Link href="/admin/licenses">
                        <KeyRound class="h-5 w-5" />

                        <span class="text-left">
                            <span class="block font-medium">Licenses</span>
                            <span class="block text-xs text-muted-foreground">
                                Manage usage rights
                            </span>
                        </span>
                    </Link>
                </Button>

                <Button
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-4"
                    as-child
                >
                    <Link href="/admin/downloads">
                        <Download class="h-5 w-5" />

                        <span class="text-left">
                            <span class="block font-medium">Downloads</span>
                            <span class="block text-xs text-muted-foreground">
                                Review download history
                            </span>
                        </span>
                    </Link>
                </Button>

                <Button
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-4"
                    as-child
                >
                    <Link href="/admin/images">
                        <Images class="h-5 w-5" />

                        <span class="text-left">
                            <span class="block font-medium">Images</span>
                            <span class="block text-xs text-muted-foreground">
                                Manage marketplace assets
                            </span>
                        </span>
                    </Link>
                </Button>

                <Button
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-4"
                    as-child
                >
                    <Link href="/admin/ai-keyword-exclusions">
                        <Ban class="h-5 w-5" />

                        <span class="text-left">
                            <span class="block font-medium">AI Exclusions</span>
                            <span class="block text-xs text-muted-foreground">
                                Filter AI keywords
                            </span>
                        </span>
                    </Link>
                </Button>

                <Button
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-4"
                    as-child
                >
                    <Link href="/admin/users">
                        <Users class="h-5 w-5" />

                        <span class="text-left">
                            <span class="block font-medium">Users</span>
                            <span class="block text-xs text-muted-foreground">
                                Manage accounts
                            </span>
                        </span>
                    </Link>
                </Button>
            </div>
        </ShowSection>

        <div class="grid gap-6 2xl:grid-cols-2">
            <ShowSection
                title="Recent Orders"
                description="The newest customer orders."
            >
                <DataTable min-width="720px">
                    <thead>
                        <tr class="border-b bg-muted/30">
                            <DataTableHeaderCell label="Order" />
                            <DataTableHeaderCell label="Customer" />
                            <DataTableHeaderCell label="Status" />
                            <DataTableHeaderCell label="Total" />
                            <DataTableHeaderCell label="Created" />
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="border-b last:border-0 hover:bg-muted/20"
                        >
                            <td class="p-4">
                                <Link
                                    :href="`/admin/orders/${order.id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ order.order_number }}
                                </Link>
                            </td>

                            <td class="p-4">
                                <div v-if="order.user">
                                    <div class="font-medium">
                                        {{ order.user.name }}
                                    </div>

                                    <div class="text-xs text-muted-foreground">
                                        {{ order.user.email }}
                                    </div>
                                </div>

                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </td>

                            <td class="p-4">
                                <StatusBadge :status="order.status" />
                            </td>

                            <td class="p-4 font-medium">
                                {{ order.total_formatted }}
                            </td>

                            <td class="p-4">
                                {{ order.created_at || '—' }}
                            </td>
                        </tr>

                        <DataTableEmpty
                            v-if="recentOrders.length === 0"
                            :colspan="5"
                            message="No recent orders found."
                        />
                    </tbody>
                </DataTable>
            </ShowSection>

            <ShowSection
                title="Recent Downloads"
                description="The newest licensed image downloads."
            >
                <DataTable min-width="760px">
                    <thead>
                        <tr class="border-b bg-muted/30">
                            <DataTableHeaderCell label="Image" />
                            <DataTableHeaderCell label="User" />
                            <DataTableHeaderCell label="License" />
                            <DataTableHeaderCell label="Type" />
                            <DataTableHeaderCell label="Downloaded" />
                            <DataTableHeaderCell label="Actions" align="right" />
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="download in recentDownloads"
                            :key="download.id"
                            class="border-b last:border-0 hover:bg-muted/20"
                        >
                            <td class="p-4">
                                <Link
                                    v-if="download.image"
                                    :href="`/images/${download.image.slug}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ download.image.title }}
                                </Link>

                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </td>

                            <td class="p-4">
                                <div v-if="download.user">
                                    <div class="font-medium">
                                        {{ download.user.name }}
                                    </div>

                                    <div class="text-xs text-muted-foreground">
                                        {{ download.user.email }}
                                    </div>
                                </div>

                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </td>

                            <td class="p-4">
                                <Link
                                    v-if="download.license"
                                    :href="`/admin/licenses/${download.license.id}`"
                                    class="text-primary hover:underline"
                                >
                                    {{ download.license.license_name }}
                                </Link>

                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </td>

                            <td class="p-4">
                                {{ formatDownloadType(download.download_type) }}
                            </td>

                            <td class="p-4">
                                {{ download.downloaded_at || '—' }}
                            </td>

                            <td class="p-4">
                                <div class="flex justify-end">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link :href="`/admin/downloads/${download.id}`">
                                            View
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <DataTableEmpty
                            v-if="recentDownloads.length === 0"
                            :colspan="6"
                            message="No recent downloads found."
                        />
                    </tbody>
                </DataTable>
            </ShowSection>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <ShowSection
                title="Top Purchased Images"
                description="Images ranked by completed purchases."
            >
                <div
                    v-if="topPurchasedImages.length"
                    class="divide-y overflow-hidden rounded-md border"
                >
                    <Link
                        v-for="(image, index) in topPurchasedImages"
                        :key="image.id"
                        :href="`/images/${image.slug}`"
                        class="flex items-center gap-4 p-4 transition hover:bg-muted/30"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold">
                            {{ index + 1 }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium">
                                {{ image.title }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ image.downloads_count.toLocaleString() }} downloads
                            </div>
                        </div>

                        <div class="text-lg font-semibold">
                            {{ image.purchases_count.toLocaleString() }}
                        </div>
                    </Link>
                </div>

                <p v-else class="text-sm text-muted-foreground">
                    No purchased-image statistics are available.
                </p>
            </ShowSection>

            <ShowSection
                title="Top Downloaded Images"
                description="Images ranked by download activity."
            >
                <div
                    v-if="topDownloadedImages.length"
                    class="divide-y overflow-hidden rounded-md border"
                >
                    <Link
                        v-for="(image, index) in topDownloadedImages"
                        :key="image.id"
                        :href="`/images/${image.slug}`"
                        class="flex items-center gap-4 p-4 transition hover:bg-muted/30"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold">
                            {{ index + 1 }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium">
                                {{ image.title }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ image.purchases_count.toLocaleString() }} purchases
                            </div>
                        </div>

                        <div class="text-lg font-semibold">
                            {{ image.downloads_count.toLocaleString() }}
                        </div>
                    </Link>
                </div>

                <p v-else class="text-sm text-muted-foreground">
                    No downloaded-image statistics are available.
                </p>
            </ShowSection>
        </div>
    </div>
</template>
