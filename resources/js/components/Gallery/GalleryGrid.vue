<script setup lang="ts">
import GalleryCard from '@/components/Gallery/GalleryCard.vue';
import type { GalleryAsset } from '@/types/gallery';

defineProps<{
    assets: GalleryAsset[];
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
        <GalleryCard
            v-for="asset in assets"
            :key="asset.id"
            :asset="asset"
        />
    </div>
</template>
