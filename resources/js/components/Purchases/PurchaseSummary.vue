<script setup lang="ts">
import DetailRow from '@/Components/Shared/DetailRow.vue';
import DetailSection from '@/Components/Shared/DetailSection.vue';

import type { PurchaseDetailRecord } from '@/types/purchase';

const props = defineProps<{
    license: PurchaseDetailRecord;
}>();

function downloadLabel(license: PurchaseDetailRecord): string {
    if (license.download_limit === null) {
        return `${license.downloads_used} / Unlimited`;
    }

    return `${license.downloads_used} / ${license.download_limit}`;
}

function configurationLabel(): string | null {
    const labels = props.license.configuration?.labels ?? [];

    if (!labels.length) {
        return null;
    }

    return labels
        .map((label) => `${label.group}: ${label.values.join(', ')}`)
        .join(' · ');
}
</script>

<template>
    <div class="space-y-6">
        <DetailSection title="License Details">
            <div class="space-y-4">
                <DetailRow
                    label="Status"
                    :value="license.status.label"
                />

                <DetailRow
                    label="License"
                    :value="license.license_name"
                />

                <DetailRow
                    label="License Key"
                    :value="license.license_key"
                    break-all
                />

                <DetailRow
                    v-if="license.quantity > 1"
                    label="Quantity"
                    :value="String(license.quantity)"
                />

                <DetailRow
                    v-if="configurationLabel()"
                    label="Configuration"
                    :value="configurationLabel()"
                />

                <DetailRow
                    label="Downloads"
                    :value="downloadLabel(license)"
                />

                <DetailRow
                    label="Starts"
                    :value="license.starts_at"
                />

                <DetailRow
                    label="Expires"
                    :value="license.expires_at ?? 'Never'"
                />
            </div>
        </DetailSection>

        <DetailSection title="Order Details">
            <div class="space-y-4">
                <DetailRow
                    label="Order Number"
                    :value="license.order.order_number"
                />

                <DetailRow
                    label="Purchased"
                    :value="license.order.paid_at"
                />

                <DetailRow
                    label="Item Total"
                    :value="license.order.line_total_formatted"
                />

                <DetailRow
                    label="Order Total"
                    :value="license.order.total_formatted"
                />
            </div>
        </DetailSection>
    </div>
</template>
