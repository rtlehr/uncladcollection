<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    FileKey,
    Package,
    Receipt,
    ShoppingCart,
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

import { formatCurrency } from '@/lib/formatCurrency';

import type { AdminOrderDetail } from '@/types/order';

const props = defineProps<{
    order: AdminOrderDetail;
}>();

function formattedCents(value: number): string {
    return formatCurrency(value, props.order.currency || 'USD');
}

function downloadLabel(
    downloadsUsed: number,
    downloadLimit: number | null,
): string {
    if (downloadLimit === null) {
        return `${downloadsUsed} / Unlimited`;
    }

    return `${downloadsUsed} / ${downloadLimit}`;
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="space-y-6 p-6">
        <ShowPageHeader
            :title="`Order ${order.order_number}`"
            description="View order details, purchased images, and generated licenses."
            eyebrow="Commerce"
        >
            <template #actions>
                <StatusBadge
                    :status="order.status"
                    size="md"
                />

                <Button
                    variant="outline"
                    as-child
                >
                    <Link href="/admin/orders">
                        Back to Orders
                    </Link>
                </Button>
            </template>
        </ShowPageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <ShowSection
                title="Customer"
                description="Customer account attached to this order."
            >
                <ShowDetailsGrid :columns="1">
                    <DetailRow
                        label="Name"
                        :value="order.user?.name"
                        fallback="No user attached"
                    />

                    <DetailRow
                        label="Email"
                        :value="order.user?.email"
                    />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection
                title="Payment"
                description="Payment provider and transaction timing."
                class="lg:col-span-2"
            >
                <ShowDetailsGrid :columns="3">
                    <DetailRow
                        label="Provider"
                        :value="order.payment_provider"
                    />

                    <DetailRow
                        label="Created"
                        :value="order.created_at"
                    />

                    <DetailRow
                        label="Paid"
                        :value="order.paid_at"
                    />

                    <DetailRow
                        label="Refunded"
                        :value="order.refunded_at"
                    />

                    <DetailRow
                        label="Canceled"
                        :value="order.canceled_at"
                    />

                    <DetailRow
                        label="Currency"
                        :value="order.currency?.toUpperCase()"
                    />
                </ShowDetailsGrid>
            </ShowSection>
        </div>

        <section>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <MetricCard
                    label="Subtotal"
                    :value="
                        order.subtotal_formatted
                        || formattedCents(order.subtotal_cents)
                    "
                >
                    <template #icon>
                        <Receipt class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Discount"
                    :value="formattedCents(order.discount_cents)"
                />

                <MetricCard
                    label="Tax"
                    :value="formattedCents(order.tax_cents)"
                />

                <MetricCard
                    label="Total"
                    :value="
                        order.total_formatted
                        || formattedCents(order.total_cents)
                    "
                    emphasized
                    size="lg"
                >
                    <template #icon>
                        <BadgeDollarSign class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Order Items"
                    :value="order.items.length"
                >
                    <template #icon>
                        <ShoppingCart class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Licenses"
                    :value="order.licenses.length"
                >
                    <template #icon>
                        <FileKey class="h-5 w-5" />
                    </template>
                </MetricCard>
            </div>
        </section>

        <ShowSection
            title="Order Items"
            description="Images and licenses included in this order."
        >
            <DataTable min-width="850px">
                <thead>
                    <tr class="border-b bg-muted/30">
                        <DataTableHeaderCell label="Image" />
                        <DataTableHeaderCell label="License" />
                        <DataTableHeaderCell label="Status" />
                        <DataTableHeaderCell label="Qty" />
                        <DataTableHeaderCell label="Unit" />
                        <DataTableHeaderCell label="Total" />
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="item in order.items"
                        :key="item.id"
                        class="border-b last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-4">
                            <div class="font-medium">
                                {{ item.image_title }}
                            </div>

                            <Link
                                v-if="item.image"
                                :href="`/images/${item.image.slug}`"
                                class="text-xs text-primary hover:underline"
                            >
                                View Public Image
                            </Link>
                        </td>

                        <td class="p-4">
                            {{ item.license_name }}
                        </td>

                        <td class="p-4">
                            <StatusBadge :status="item.status" />
                        </td>

                        <td class="p-4">
                            {{ item.quantity }}
                        </td>

                        <td class="p-4">
                            {{ item.unit_price_formatted }}
                        </td>

                        <td class="p-4 font-medium">
                            {{ item.total_price_formatted }}
                        </td>
                    </tr>

                    <DataTableEmpty
                        v-if="order.items.length === 0"
                        :colspan="6"
                        message="No order items found."
                    />
                </tbody>
            </DataTable>
        </ShowSection>

        <ShowSection
            title="Licenses"
            description="Licenses generated from this order."
        >
            <DataTable min-width="1100px">
                <thead>
                    <tr class="border-b bg-muted/30">
                        <DataTableHeaderCell label="License Key" />
                        <DataTableHeaderCell label="Image" />
                        <DataTableHeaderCell label="License" />
                        <DataTableHeaderCell label="Status" />
                        <DataTableHeaderCell label="Downloads" />
                        <DataTableHeaderCell label="Starts" />
                        <DataTableHeaderCell label="Expires" />
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="license in order.licenses"
                        :key="license.id"
                        class="border-b last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-4">
                            <div class="max-w-[240px] break-all font-mono text-xs">
                                {{ license.license_key }}
                            </div>
                        </td>

                        <td class="p-4">
                            <div class="font-medium">
                                {{ license.image?.title || '—' }}
                            </div>

                            <Link
                                v-if="license.image"
                                :href="`/images/${license.image.slug}`"
                                class="text-xs text-primary hover:underline"
                            >
                                View Public Image
                            </Link>
                        </td>

                        <td class="p-4">
                            {{ license.license_name }}
                        </td>

                        <td class="p-4">
                            <StatusBadge :status="license.status" />
                        </td>

                        <td class="p-4">
                            {{
                                downloadLabel(
                                    license.downloads_used,
                                    license.download_limit,
                                )
                            }}
                        </td>

                        <td class="p-4">
                            {{ license.starts_at || '—' }}
                        </td>

                        <td class="p-4">
                            {{ license.expires_at || 'Never' }}
                        </td>
                    </tr>

                    <DataTableEmpty
                        v-if="order.licenses.length === 0"
                        :colspan="7"
                        message="No licenses found."
                    />
                </tbody>
            </DataTable>
        </ShowSection>

        <ShowSection
            v-if="
                order.payment_reference
                || order.stripe_checkout_session_id
                || order.stripe_payment_intent_id
            "
            title="Payment References"
            description="Identifiers returned by the payment provider."
        >
            <ShowDetailsGrid :columns="1">
                <DetailRow
                    v-if="order.payment_reference"
                    label="Payment Reference"
                    :value="order.payment_reference"
                    break-all
                />

                <DetailRow
                    v-if="order.stripe_checkout_session_id"
                    label="Stripe Checkout Session"
                    :value="order.stripe_checkout_session_id"
                    break-all
                />

                <DetailRow
                    v-if="order.stripe_payment_intent_id"
                    label="Stripe Payment Intent"
                    :value="order.stripe_payment_intent_id"
                    break-all
                />
            </ShowDetailsGrid>
        </ShowSection>
    </div>
</template>
