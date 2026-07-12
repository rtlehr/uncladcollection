<script setup lang="ts">
import GalleryCard from '@/components/Gallery/GalleryCard.vue';
import type { GalleryImage } from '@/types/gallery';

defineProps<{
    images: GalleryImage[];
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
        <GalleryCard
            v-for="image in images"
            :key="image.id"
            :image="image"
        />
    </div>
</template>
