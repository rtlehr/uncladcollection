<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AssetPreview from '@/Components/Assets/AssetPreview.vue';
import { Button } from '@/components/ui/button';

import type { PurchasedAsset } from '@/types/purchase';

const props = defineProps<{
    license: PurchasedAsset;
}>();

function downloadLabel(license: PurchasedAsset): string {
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
    <article class="overflow-hidden rounded-lg border bg-card shadow-sm">
        <Link
            :href="license.detail_url"
            class="group block"
        >
            <AssetPreview
                :src="license.product.preview_url"
                :alt="license.product.title"
                aspect="square"
                fallback-text="No Preview"
            />
        </Link>

        <div class="space-y-4 p-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold" :class="license.status.tone === 'danger' ? 'border-red-300 text-red-700' : license.status.tone === 'warning' ? 'border-amber-300 text-amber-700' : 'border-emerald-300 text-emerald-700'">{{ license.status.label }}</span>
                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ license.product.asset_type_label }}
                    </span>

                    <span
                        v-if="license.product.is_ai_generated"
                        class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-200"
                    >
                        AI Generated
                    </span>
                </div>

                <Link
                    :href="license.detail_url"
                    class="mt-3 block font-semibold hover:underline"
                >
                    {{ license.product.title }}
                </Link>

                <p class="text-sm text-muted-foreground">
                    {{ license.license_name }}
                </p>

                <p
                    v-if="configurationLabel()"
                    class="mt-2 line-clamp-2 text-xs text-muted-foreground"
                >
                    {{ configurationLabel() }}
                </p>
            </div>

            <div class="space-y-1 text-xs text-muted-foreground">
                <div>
                    Order: {{ license.order.order_number ?? '—' }}
                </div>

                <div>
                    Purchased: {{ license.order.paid_at ?? '—' }}
                </div>

                <div v-if="license.quantity > 1">
                    Quantity: {{ license.quantity }}
                </div>

                <div>
                    Downloads: {{ downloadLabel(license) }}
                </div>

                <div v-if="license.kind === 'asset'">
                    Included files: {{ license.included_files_count }}
                </div>

                <div>
                    Expires: {{ license.expires_at ?? 'Never' }}
                </div>
            </div>

            <div class="flex gap-2">
                <Button
                    v-if="license.download_url && license.can_download"
                    as-child
                    class="flex-1"
                >
                    <a :href="license.download_url">
                        Download
                    </a>
                </Button>

                <Button
                    v-else-if="license.status.key === 'active' && license.kind === 'asset'"
                    disabled
                    variant="secondary"
                    class="flex-1"
                >
                    Open Details
                </Button>

                <Button
                    v-else
                    disabled
                    variant="secondary"
                    class="flex-1"
                >
                    Unavailable
                </Button>

                <Button variant="outline" as-child>
                    <Link :href="license.detail_url">
                        Details
                    </Link>
                </Button>
            </div>
        </div>
    </article>
</template>
