<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Download,
    FileKey,
    Gauge,
    History,
} from '@lucide/vue';

import ShowDetailsGrid from '@/Components/Show/ShowDetailsGrid.vue';
import ShowPageHeader from '@/Components/Show/ShowPageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type { AdminLicenseDetail } from '@/types/licenseDetail';

defineProps<{
    licenseRecord: AdminLicenseDetail;
}>();

function downloadLimitLabel(limit: number | null): string {
    return limit === null ? 'Unlimited' : String(limit);
}

function downloadUsageLabel(
    downloadsUsed: number,
    downloadLimit: number | null,
): string {
    return downloadLimit === null
        ? `${downloadsUsed} / Unlimited`
        : `${downloadsUsed} / ${downloadLimit}`;
}
</script>

<template>
    <Head :title="`License ${licenseRecord.license_key}`" />

    <div class="space-y-6 p-6">
        <ShowPageHeader
            title="License Details"
            :description="licenseRecord.license_key"
            eyebrow="Commerce"
        >
            <template #actions>
                <StatusBadge
                    :status="licenseRecord.status"
                    size="md"
                />

                <Button
                    variant="outline"
                    as-child
                >
                    <Link href="/admin/licenses">
                        Back to Licenses
                    </Link>
                </Button>
            </template>
        </ShowPageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                label="Downloads Used"
                :value="licenseRecord.downloads_used"
            >
                <template #icon>
                    <Download class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Download Limit"
                :value="downloadLimitLabel(licenseRecord.download_limit)"
            >
                <template #icon>
                    <Gauge class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Usage"
                :value="
                    downloadUsageLabel(
                        licenseRecord.downloads_used,
                        licenseRecord.download_limit,
                    )
                "
                emphasized
            >
                <template #icon>
                    <FileKey class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Download Records"
                :value="licenseRecord.downloads.length"
            >
                <template #icon>
                    <History class="h-5 w-5" />
                </template>
            </MetricCard>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <ShowSection
                title="License"
                description="Core license dates and terms."
            >
                <ShowDetailsGrid :columns="1">
                    <DetailRow
                        label="Type"
                        :value="licenseRecord.license_name"
                    />

                    <DetailRow
                        label="Created"
                        :value="licenseRecord.created_at"
                    />

                    <DetailRow
                        label="Starts"
                        :value="licenseRecord.starts_at"
                    />

                    <DetailRow
                        label="Expires"
                        :value="licenseRecord.expires_at || 'Never'"
                    />

                    <DetailRow
                        label="Downloads"
                        :value="
                            downloadUsageLabel(
                                licenseRecord.downloads_used,
                                licenseRecord.download_limit,
                            )
                        "
                    />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection
                title="Customer"
                description="Customer account attached to this license."
            >
                <ShowDetailsGrid :columns="1">
                    <DetailRow
                        label="Name"
                        :value="licenseRecord.user?.name"
                        fallback="No user attached"
                    />

                    <DetailRow
                        label="Email"
                        :value="licenseRecord.user?.email"
                    />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection
                title="Image"
                description="Licensed marketplace image."
            >
                <div v-if="licenseRecord.image" class="space-y-4">
                    <ShowDetailsGrid :columns="1">
                        <DetailRow
                            label="Title"
                            :value="licenseRecord.image.title"
                        />

                        <DetailRow
                            label="Photographer"
                            :value="licenseRecord.image.photographer"
                        />

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                AI Generated
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="
                                        licenseRecord.image.is_ai_generated
                                            ? 'ai_generated'
                                            : 'non_ai'
                                    "
                                    :label="
                                        licenseRecord.image.is_ai_generated
                                            ? 'Yes'
                                            : 'No'
                                    "
                                    :tone="
                                        licenseRecord.image.is_ai_generated
                                            ? 'info'
                                            : 'neutral'
                                    "
                                />
                            </div>
                        </div>
                    </ShowDetailsGrid>

                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/images/${licenseRecord.image.slug}`">
                            View Public Image
                        </Link>
                    </Button>
                </div>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No image attached.
                </p>
            </ShowSection>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <ShowSection
                title="Order"
                description="Order that generated this license."
            >
                <div v-if="licenseRecord.order" class="space-y-4">
                    <ShowDetailsGrid :columns="2">
                        <DetailRow
                            label="Order Number"
                            :value="licenseRecord.order.order_number"
                        />

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Status
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="licenseRecord.order.status"
                                />
                            </div>
                        </div>

                        <DetailRow
                            label="Paid"
                            :value="licenseRecord.order.paid_at"
                        />

                        <DetailRow
                            label="Total"
                            :value="licenseRecord.order.total_formatted"
                        />
                    </ShowDetailsGrid>

                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/admin/orders/${licenseRecord.order.id}`">
                            View Order
                        </Link>
                    </Button>
                </div>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No order attached.
                </p>
            </ShowSection>

            <ShowSection
                title="Order Item"
                description="Order line item associated with this license."
            >
                <div v-if="licenseRecord.order_item">
                    <ShowDetailsGrid :columns="2">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Status
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="licenseRecord.order_item.status"
                                />
                            </div>
                        </div>

                        <DetailRow
                            label="Unit Price"
                            :value="licenseRecord.order_item.unit_price_formatted"
                        />

                        <DetailRow
                            label="Total Price"
                            :value="licenseRecord.order_item.total_price_formatted"
                        />
                    </ShowDetailsGrid>
                </div>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No order item attached.
                </p>
            </ShowSection>
        </div>

        <ShowSection
            title="Download History"
            description="Recorded downloads made under this license."
        >
            <DataTable min-width="900px">
                <thead>
                    <tr class="border-b bg-muted/30">
                        <DataTableHeaderCell label="Date" />
                        <DataTableHeaderCell label="Type" />
                        <DataTableHeaderCell label="IP Address" />
                        <DataTableHeaderCell label="User Agent" />
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="download in licenseRecord.downloads"
                        :key="download.id"
                        class="border-b last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-4">
                            {{ download.downloaded_at || '—' }}
                        </td>

                        <td class="p-4 capitalize">
                            {{ download.download_type }}
                        </td>

                        <td class="p-4 font-mono text-xs">
                            {{ download.ip_address || '—' }}
                        </td>

                        <td class="p-4">
                            <div class="max-w-xl break-words text-xs text-muted-foreground">
                                {{ download.user_agent || '—' }}
                            </div>
                        </td>
                    </tr>

                    <DataTableEmpty
                        v-if="licenseRecord.downloads.length === 0"
                        :colspan="4"
                        message="No downloads recorded for this license."
                    />
                </tbody>
            </DataTable>
        </ShowSection>

        <ShowSection
            v-if="licenseRecord.license_terms"
            title="License Terms"
            description="Terms attached to this license at the time of purchase."
        >
            <div class="whitespace-pre-line text-sm leading-7">
                {{ licenseRecord.license_terms }}
            </div>
        </ShowSection>
    </div>
</template>
