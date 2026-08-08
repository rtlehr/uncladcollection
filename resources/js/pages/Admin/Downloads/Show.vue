<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Download,
    FileKey,
    Globe2,
    Receipt,
} from '@lucide/vue';

import DetailRow from '@/Components/Shared/DetailRow.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import ShowDetailsGrid from '@/Components/Show/ShowDetailsGrid.vue';
import ShowPageHeader from '@/Components/Show/ShowPageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';

import type { AdminDownloadDetail } from '@/types/downloadDetail';

defineProps<{
    downloadRecord: AdminDownloadDetail;
}>();

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
    <Head :title="`Download #${downloadRecord.id}`" />

    <div class="space-y-6 p-6">
        <ShowPageHeader
            title="Download Details"
            :description="`Download record #${downloadRecord.id}`"
            eyebrow="Commerce"
        >
            <template #actions>
                <Button
                    variant="outline"
                    as-child
                >
                    <Link href="/admin/downloads">
                        Back to Downloads
                    </Link>
                </Button>
            </template>
        </ShowPageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                label="Download Type"
                :value="downloadRecord.download_type"
            >
                <template #icon>
                    <Download class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="License Usage"
                :value="
                    downloadRecord.license
                        ? downloadUsageLabel(
                            downloadRecord.license.downloads_used,
                            downloadRecord.license.download_limit,
                        )
                        : '—'
                "
            >
                <template #icon>
                    <FileKey class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="Order Total"
                :value="downloadRecord.order?.total_formatted ?? '—'"
                emphasized
            >
                <template #icon>
                    <Receipt class="h-5 w-5" />
                </template>
            </MetricCard>

            <MetricCard
                label="IP Address"
                :value="downloadRecord.ip_address ?? '—'"
                size="sm"
            >
                <template #icon>
                    <Globe2 class="h-5 w-5" />
                </template>
            </MetricCard>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <ShowSection
                title="Download"
                description="Core download metadata."
            >
                <ShowDetailsGrid :columns="1">
                    <DetailRow
                        label="Downloaded"
                        :value="downloadRecord.downloaded_at"
                    />

                    <DetailRow
                        label="Type"
                        :value="downloadRecord.download_type"
                    />

                    <DetailRow
                        label="IP Address"
                        :value="downloadRecord.ip_address"
                    />

                    <DetailRow
                        label="Created"
                        :value="downloadRecord.created_at"
                    />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection
                title="User"
                description="User account associated with this download."
            >
                <ShowDetailsGrid :columns="1">
                    <DetailRow
                        label="Name"
                        :value="downloadRecord.user?.name"
                        fallback="No user attached"
                    />

                    <DetailRow
                        label="Email"
                        :value="downloadRecord.user?.email"
                    />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection
                title="Image"
                description="Marketplace image associated with this download."
            >
                <div v-if="downloadRecord.image" class="space-y-4">
                    <ShowDetailsGrid :columns="1">
                        <DetailRow
                            label="Title"
                            :value="downloadRecord.image.title"
                        />

                        <DetailRow
                            label="Photographer"
                            :value="downloadRecord.image.photographer"
                        />
                    </ShowDetailsGrid>

                    <img
                        v-if="downloadRecord.image.icon_url"
                        :src="downloadRecord.image.icon_url"
                        :alt="downloadRecord.image.title"
                        class="h-32 w-auto rounded-md border object-contain"
                    />

                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/images/${downloadRecord.image.slug}`">
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
                title="License"
                description="License used for this download."
            >
                <div v-if="downloadRecord.license" class="space-y-4">
                    <ShowDetailsGrid :columns="2">
                        <DetailRow
                            label="License Type"
                            :value="downloadRecord.license.license_name"
                        />

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Status
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="downloadRecord.license.status"
                                />
                            </div>
                        </div>

                        <DetailRow
                            label="Downloads Used"
                            :value="
                                downloadUsageLabel(
                                    downloadRecord.license.downloads_used,
                                    downloadRecord.license.download_limit,
                                )
                            "
                        />

                        <DetailRow
                            label="License Key"
                            :value="downloadRecord.license.license_key"
                            break-all
                        />
                    </ShowDetailsGrid>

                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/admin/licenses/${downloadRecord.license.id}`">
                            View License
                        </Link>
                    </Button>
                </div>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No license attached.
                </p>
            </ShowSection>

            <ShowSection
                title="Order"
                description="Order associated with this download."
            >
                <div v-if="downloadRecord.order" class="space-y-4">
                    <ShowDetailsGrid :columns="2">
                        <DetailRow
                            label="Order Number"
                            :value="downloadRecord.order.order_number"
                        />

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Status
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="downloadRecord.order.status"
                                />
                            </div>
                        </div>

                        <DetailRow
                            label="Paid"
                            :value="downloadRecord.order.paid_at"
                        />

                        <DetailRow
                            label="Total"
                            :value="downloadRecord.order.total_formatted"
                        />
                    </ShowDetailsGrid>

                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/admin/orders/${downloadRecord.order.id}`">
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
        </div>

        <ShowSection
            title="User Agent"
            description="Browser and device information recorded with the download."
        >
            <div
                v-if="downloadRecord.user_agent"
                class="break-words rounded-md border bg-muted/40 p-4 font-mono text-xs leading-6"
            >
                {{ downloadRecord.user_agent }}
            </div>

            <p
                v-else
                class="text-sm text-muted-foreground"
            >
                No user agent recorded.
            </p>
        </ShowSection>
    </div>
</template>
