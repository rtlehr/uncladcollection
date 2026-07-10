<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AssetActions from '@/Components/Assets/AssetActions.vue';
import AssetCard from '@/Components/Assets/AssetCard.vue';
import AssetDescription from '@/Components/Assets/AssetDescription.vue';
import AssetHero from '@/Components/Assets/AssetHero.vue';
import AssetMetadata from '@/Components/Assets/AssetMetadata.vue';
import AssetStats from '@/Components/Assets/AssetStats.vue';
import ChipList from '@/Components/Shared/ChipList.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';
import SidebarCard from '@/Components/Shared/SidebarCard.vue';

import type {
    AssetDetailData,
    LicenseType,
    RelatedAssetData,
} from '@/types/asset';

const props = defineProps<{
    imageRecord: AssetDetailData;
    relatedImages: RelatedAssetData[];
    licenseTypes: LicenseType[];
}>();

const page = usePage();

const isLoggedIn = computed(() => Boolean((page.props as any).auth?.user));
</script>

<template>
    <Head :title="imageRecord.title" />

    <div class="space-y-8 p-6">
        <AssetHero
            :title="imageRecord.title"
            :collection-name="imageRecord.collection?.name"
        >
            <template #actions>
                <AssetActions
                    :asset="imageRecord"
                    :license-types="licenseTypes"
                    :is-logged-in="isLoggedIn"
                />
            </template>
        </AssetHero>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
                <div
                    v-if="imageRecord.thumbnail_url || imageRecord.high_res_url"
                    class="rounded-lg border bg-muted p-4"
                >
                    <img
                        :src="imageRecord.thumbnail_url || imageRecord.high_res_url || ''"
                        :alt="imageRecord.title"
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
                <AssetMetadata :asset="imageRecord" />

                <AssetStats :asset="imageRecord" />

                <SidebarCard title="Categories">
                    <ChipList
                        :items="imageRecord.categories"
                        empty-message="No categories assigned."
                    />
                </SidebarCard>

                <SidebarCard title="Tags">
                    <ChipList
                        :items="imageRecord.tags"
                        empty-message="No tags assigned."
                        prefix="#"
                    />
                </SidebarCard>
            </aside>
        </div>

        <AssetDescription :description="imageRecord.description" />

        <section v-if="relatedImages.length">
            <SectionHeader
                title="Related Images"
                description="Explore similar images from Unclad Collection."
            />

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <AssetCard
                    v-for="image in relatedImages"
                    :key="image.id"
                    :asset="image"
                    :show-collection="false"
                    :show-categories="false"
                    :show-ai-badge="false"
                />
            </div>
        </section>
    </div>
</template>
