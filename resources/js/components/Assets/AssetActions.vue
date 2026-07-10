<script setup lang="ts">
import { ref, watch } from 'vue';

import DownloadButton from '@/Components/Assets/DownloadButton.vue';
import FavoriteButton from '@/Components/Assets/FavoriteButton.vue';
import LicenseSelector from '@/Components/Assets/LicenseSelector.vue';
import PurchaseActions from '@/Components/Assets/PurchaseActions.vue';

import type {
    AssetDetailData,
    LicenseType,
} from '@/types/asset';

const props = defineProps<{
    asset: AssetDetailData;
    licenseTypes: LicenseType[];
    isLoggedIn: boolean;
}>();

const selectedLicenseTypeId = ref<number | null>(
    props.licenseTypes.length ? props.licenseTypes[0].id : null,
);

watch(
    () => props.licenseTypes,
    (licenseTypes) => {
        const stillExists = licenseTypes.some(
            (licenseType) => licenseType.id === selectedLicenseTypeId.value,
        );

        if (!stillExists) {
            selectedLicenseTypeId.value = licenseTypes[0]?.id ?? null;
        }
    },
);
</script>

<template>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <FavoriteButton
            :asset-id="asset.id"
            :is-favorited="asset.is_favorited"
            :is-logged-in="isLoggedIn"
        />

        <DownloadButton
            :asset-id="asset.id"
            :can-download="asset.can_download"
            :is-purchased="asset.is_purchased"
        />

        <div
            v-if="asset.can_purchase && !asset.can_download && !asset.is_purchased"
            class="flex flex-wrap items-center gap-2"
        >
            <LicenseSelector
                v-model="selectedLicenseTypeId"
                :license-types="licenseTypes"
            />

            <PurchaseActions
                :asset-id="asset.id"
                :selected-license-type-id="selectedLicenseTypeId"
                :is-logged-in="isLoggedIn"
            />
        </div>
    </div>
</template>
