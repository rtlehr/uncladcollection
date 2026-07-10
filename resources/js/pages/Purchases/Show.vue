<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AssetDescription from '@/Components/Assets/AssetDescription.vue';
import AssetHero from '@/Components/Assets/AssetHero.vue';
import ChipList from '@/Components/Shared/ChipList.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import DetailSection from '@/Components/Shared/DetailSection.vue';
import SidebarCard from '@/Components/Shared/SidebarCard.vue';
import PurchaseSummary from '@/Components/Purchases/PurchaseSummary.vue';
import { Button } from '@/components/ui/button';

import type { PurchaseDetailRecord } from '@/types/purchase';

defineProps<{
    licenseRecord: PurchaseDetailRecord;
}>();
</script>

<template>
    <Head :title="licenseRecord.image.title" />

    <div class="space-y-8 p-6">
        <AssetHero
            :title="licenseRecord.image.title"
            :collection-name="licenseRecord.image.collection?.name"
            back-href="/purchases"
            back-label="Back to My Purchases"
        >
            <template #actions>
                <Button
                    v-if="licenseRecord.can_download"
                    as-child
                >
                    <a :href="`/images/${licenseRecord.image.id}/download`">
                        Download
                    </a>
                </Button>

                <Button
                    v-else
                    disabled
                    variant="secondary"
                >
                    Download Unavailable
                </Button>
            </template>
        </AssetHero>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
                <div
                    v-if="
                        licenseRecord.image.thumbnail_url
                        || licenseRecord.image.high_res_url
                    "
                    class="rounded-lg border bg-muted p-4"
                >
                    <img
                        :src="
                            licenseRecord.image.thumbnail_url
                            || licenseRecord.image.high_res_url
                            || ''
                        "
                        :alt="licenseRecord.image.title"
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

            <aside class="space-y-6">
                <PurchaseSummary :license="licenseRecord" />

                <DetailSection title="Image Details">
                    <div class="space-y-4">
                        <DetailRow
                            label="Photographer"
                            :value="licenseRecord.image.photographer"
                        />

                        <DetailRow
                            label="AI Generated"
                            :value="
                                licenseRecord.image.is_ai_generated
                                    ? 'Yes'
                                    : 'No'
                            "
                        />

                        <DetailRow
                            label="Added"
                            :value="licenseRecord.image.created_at"
                        />
                    </div>
                </DetailSection>

                <SidebarCard title="Categories">
                    <ChipList
                        :items="licenseRecord.image.categories"
                        empty-message="No categories assigned."
                    />
                </SidebarCard>

                <SidebarCard title="Tags">
                    <ChipList
                        :items="licenseRecord.image.tags"
                        empty-message="No tags assigned."
                        prefix="#"
                    />
                </SidebarCard>
            </aside>
        </div>

        <AssetDescription
            :description="licenseRecord.image.description"
        />

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
