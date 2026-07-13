<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AssetDescription from '@/Components/Assets/AssetDescription.vue';
import AssetHero from '@/Components/Assets/AssetHero.vue';
import ChipList from '@/Components/Shared/ChipList.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import DetailSection from '@/Components/Shared/DetailSection.vue';
import SidebarCard from '@/Components/Shared/SidebarCard.vue';
import PurchaseSummary from '@/Components/Purchases/PurchaseSummary.vue';
import { Button } from '@/components/ui/button';

import type { PurchaseDetailRecord, PurchasedIncludedFile } from '@/types/purchase';

const props = defineProps<{
    licenseRecord: PurchaseDetailRecord;
}>();

function formatBytes(bytes: number | null): string {
    if (!bytes || bytes <= 0) {
        return 'Size unavailable';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, index);

    return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

function fileSubtitle(file: PurchasedIncludedFile): string {
    return [file.extension, file.role?.replaceAll('_', ' '), formatBytes(file.size_bytes)]
        .filter(Boolean)
        .join(' · ');
}
</script>

<template>
    <Head :title="licenseRecord.product.title" />

    <div class="space-y-8 p-6">
        <AssetHero
            :title="licenseRecord.product.title"
            :collection-name="licenseRecord.product.collection?.name"
            back-href="/purchases"
            back-label="Back to My Library"
        >
            <template #actions>
                <Button
                    v-if="licenseRecord.download_url && licenseRecord.can_download"
                    as-child
                >
                    <a :href="licenseRecord.download_url">
                        Download
                    </a>
                </Button>

                <Button
                    v-else-if="licenseRecord.kind === 'asset'"
                    disabled
                    variant="secondary"
                >
                    Downloads Coming Next
                </Button>

                <Button
                    v-else
                    disabled
                    variant="secondary"
                >
                    Download Unavailable
                </Button>

                <Button variant="outline" as-child>
                    <Link :href="licenseRecord.product.public_url">
                        View Asset
                    </Link>
                </Button>
            </template>
        </AssetHero>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <div
                        v-if="licenseRecord.product.preview_url"
                        class="rounded-lg border bg-muted p-4"
                    >
                        <img
                            :src="licenseRecord.product.preview_url"
                            :alt="licenseRecord.product.title"
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

                <DetailSection
                    v-if="licenseRecord.kind === 'asset'"
                    title="Included Files"
                >
                    <div
                        v-if="licenseRecord.included_files.length"
                        class="divide-y rounded-lg border"
                    >
                        <div
                            v-for="file in licenseRecord.included_files"
                            :key="`${file.id}-${file.name}`"
                            class="flex items-center justify-between gap-4 p-4"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ file.name }}
                                </p>
                                <p class="mt-1 capitalize text-xs text-muted-foreground">
                                    {{ fileSubtitle(file) }}
                                </p>
                            </div>

                            <span
                                v-if="file.extension"
                                class="rounded-full bg-muted px-3 py-1 text-xs font-semibold"
                            >
                                {{ file.extension }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-else
                        class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
                    >
                        No file manifest was stored with this license.
                    </p>

                    <p class="mt-4 text-sm text-muted-foreground">
                        Native multi-file delivery will be enabled in UC-A005.3. Your purchase and file entitlement are already preserved with this license.
                    </p>
                </DetailSection>
            </div>

            <aside class="space-y-6">
                <PurchaseSummary :license="licenseRecord" />

                <DetailSection title="Asset Details">
                    <div class="space-y-4">
                        <DetailRow
                            label="Type"
                            :value="licenseRecord.product.asset_type_label"
                        />

                        <DetailRow
                            label="Creator"
                            :value="licenseRecord.product.creator"
                        />

                        <DetailRow
                            label="AI Generated"
                            :value="licenseRecord.product.is_ai_generated ? 'Yes' : 'No'"
                        />

                        <DetailRow
                            label="Added"
                            :value="licenseRecord.product.created_at"
                        />
                    </div>
                </DetailSection>

                <SidebarCard
                    v-if="licenseRecord.product.categories.length"
                    title="Categories"
                >
                    <ChipList
                        :items="licenseRecord.product.categories"
                        empty-message="No categories assigned."
                    />
                </SidebarCard>

                <SidebarCard
                    v-if="licenseRecord.product.tags.length"
                    title="Tags"
                >
                    <ChipList
                        :items="licenseRecord.product.tags"
                        empty-message="No tags assigned."
                        prefix="#"
                    />
                </SidebarCard>
            </aside>
        </div>

        <AssetDescription :description="licenseRecord.product.description" />

        <DetailSection
            v-if="licenseRecord.license_terms"
            title="License Terms"
        >
            <div class="whitespace-pre-line text-sm leading-7">
                {{ licenseRecord.license_terms }}
            </div>
        </DetailSection>
    </div>
</template>
