<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import MediaFileDetails from '@/components/unclad/media/MediaFileDetails.vue';
import MediaThumbnailCard from '@/components/unclad/media/MediaThumbnailCard.vue';
import MediaViewer from '@/components/unclad/media/MediaViewer.vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

const props = withDefaults(defineProps<{
    files: MediaPresentationFile[];
    assetTitle: string;
    initialFileId?: number | null;
    compact?: boolean;
}>(), { initialFileId: null, compact: false });

const selectedId = ref<number | null>(props.initialFileId ?? props.files.find((file) => file.can_preview)?.id ?? props.files[0]?.id ?? null);
const selectedFile = computed(() => props.files.find((file) => file.id === selectedId.value) ?? props.files[0] ?? null);

watch(() => props.files, (files) => {
    if (!files.some((file) => file.id === selectedId.value)) {
        selectedId.value = files.find((file) => file.can_preview)?.id ?? files[0]?.id ?? null;
    }
}, { deep: true });
</script>

<template>
    <section>
        <MediaViewer :file="selectedFile" :asset-title="assetTitle" />
        <MediaFileDetails v-if="!compact" :file="selectedFile" />

        <div v-if="files.length > 1" class="mt-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Asset files</h2>
                    <p class="text-sm text-stone-500">Select a file to preview its media and technical details.</p>
                </div>
                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold dark:bg-stone-800">{{ files.length }} files</span>
            </div>
            <div class="flex gap-3 overflow-x-auto pb-3">
                <MediaThumbnailCard
                    v-for="file in files"
                    :key="file.id"
                    :file="file"
                    :selected="file.id === selectedFile?.id"
                    @select="selectedId = file.id"
                />
            </div>
        </div>
    </section>
</template>
