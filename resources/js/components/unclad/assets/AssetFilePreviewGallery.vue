<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight, Maximize2 } from '@lucide/vue';
import AssetViewerLightbox from '@/components/unclad/media/AssetViewerLightbox.vue';
import MediaFileDetails from '@/components/unclad/media/MediaFileDetails.vue';
import MediaThumbnailCard from '@/components/unclad/media/MediaThumbnailCard.vue';
import MediaViewer from '@/components/unclad/media/MediaViewer.vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

const props = withDefaults(
    defineProps<{
        files: MediaPresentationFile[];
        assetTitle: string;
        initialFileId?: number | null;
        compact?: boolean;
    }>(),
    {
        initialFileId: null,
        compact: false,
    },
);

const selectedId = ref<number | null>(
    props.initialFileId ??
        props.files.find((file) => file.can_preview)?.id ??
        props.files[0]?.id ??
        null,
);
const lightboxOpen = ref(false);

const selectedFile = computed(
    () =>
        props.files.find((file) => file.id === selectedId.value) ??
        props.files[0] ??
        null,
);
const selectedIndex = computed(() =>
    props.files.findIndex((file) => file.id === selectedId.value),
);

function move(offset: number): void {
    if (props.files.length < 2) return;
    const current = selectedIndex.value < 0 ? 0 : selectedIndex.value;
    const next = (current + offset + props.files.length) % props.files.length;
    selectedId.value = props.files[next].id;
}

function onKeydown(event: KeyboardEvent): void {
    if (lightboxOpen.value) return;
    const target = event.target as HTMLElement | null;
    if (target?.closest('input, textarea, select, [contenteditable="true"]'))
        return;
    if (event.key === 'ArrowLeft') move(-1);
    if (event.key === 'ArrowRight') move(1);
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

watch(
    () => props.files,
    (files) => {
        if (!files.some((file) => file.id === selectedId.value)) {
            selectedId.value =
                files.find((file) => file.can_preview)?.id ??
                files[0]?.id ??
                null;
        }
    },
    { deep: true },
);
</script>

<template>
    <section aria-label="Asset media gallery">
        <div
            class="group relative overflow-hidden rounded-3xl border border-stone-200 bg-stone-950 shadow-sm dark:border-stone-800"
        >
            <Transition name="media-fade" mode="out-in">
                <MediaViewer
                    :key="selectedFile?.id"
                    :file="selectedFile"
                    :asset-title="assetTitle"
                    @open="lightboxOpen = true"
                />
            </Transition>

            <template v-if="files.length > 1">
                <button
                    type="button"
                    class="absolute top-1/2 left-4 z-30 -translate-y-1/2 rounded-full border border-white/15 bg-black/55 p-2.5 text-white opacity-0 backdrop-blur transition group-hover:opacity-100 hover:bg-black/75 focus:opacity-100 sm:opacity-100"
                    aria-label="Previous preview"
                    @click="move(-1)"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>
                <button
                    type="button"
                    class="absolute top-1/2 right-4 z-30 -translate-y-1/2 rounded-full border border-white/15 bg-black/55 p-2.5 text-white opacity-0 backdrop-blur transition group-hover:opacity-100 hover:bg-black/75 focus:opacity-100 sm:opacity-100"
                    aria-label="Next preview"
                    @click="move(1)"
                >
                    <ChevronRight class="h-5 w-5" />
                </button>
            </template>
        </div>

        <MediaFileDetails v-if="!compact" :file="selectedFile" />

        <div v-if="files.length > 1" class="mt-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Preview every file</h2>
                    <p class="text-sm text-stone-500">
                        Select a file, use the arrow keys, or open the
                        full-screen viewer.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs font-semibold transition hover:border-stone-400 dark:border-stone-700 dark:bg-stone-900"
                    @click="lightboxOpen = true"
                >
                    <Maximize2 class="h-3.5 w-3.5" />
                    {{ selectedIndex + 1 }} of {{ files.length }}
                </button>
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

        <AssetViewerLightbox
            :open="lightboxOpen"
            :files="files"
            :selected-id="selectedId"
            :asset-title="assetTitle"
            @close="lightboxOpen = false"
            @select="selectedId = $event"
        />
    </section>
</template>

<style scoped>
.media-fade-enter-active,
.media-fade-leave-active {
    transition: opacity 0.18s ease;
}
.media-fade-enter-from,
.media-fade-leave-to {
    opacity: 0;
}
</style>
