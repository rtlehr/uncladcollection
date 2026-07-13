<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MediaFileDetails from '@/components/unclad/media/MediaFileDetails.vue';
import MediaThumbnailCard from '@/components/unclad/media/MediaThumbnailCard.vue';
import MediaViewer from '@/components/unclad/media/MediaViewer.vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

const props = withDefaults(defineProps<{ files: MediaPresentationFile[]; assetTitle: string; initialFileId?: number | null; compact?: boolean; }>(), { initialFileId: null, compact: false });
const selectedId = ref<number | null>(props.initialFileId ?? props.files.find(file => file.can_preview)?.id ?? props.files[0]?.id ?? null);
const selectedFile = computed(() => props.files.find(file => file.id === selectedId.value) ?? props.files[0] ?? null);
const selectedIndex = computed(() => props.files.findIndex(file => file.id === selectedId.value));
function move(offset: number): void { if (props.files.length < 2) return; const next = (selectedIndex.value + offset + props.files.length) % props.files.length; selectedId.value = props.files[next].id; }
function onKeydown(event: KeyboardEvent): void { if (event.key === 'ArrowLeft') move(-1); if (event.key === 'ArrowRight') move(1); }
onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
watch(() => props.files, files => { if (!files.some(file => file.id === selectedId.value)) selectedId.value = files.find(file => file.can_preview)?.id ?? files[0]?.id ?? null; }, { deep: true });
</script>
<template>
    <section aria-label="Asset media gallery">
        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-stone-950 shadow-sm dark:border-stone-800"><Transition name="media-fade" mode="out-in"><MediaViewer :key="selectedFile?.id" :file="selectedFile" :asset-title="assetTitle" /></Transition></div>
        <MediaFileDetails v-if="!compact" :file="selectedFile" />
        <div v-if="files.length > 1" class="mt-5"><div class="mb-3 flex items-center justify-between gap-3"><div><h2 class="font-semibold">Preview every file</h2><p class="text-sm text-stone-500">Select a file, or use the left and right arrow keys.</p></div><span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold dark:bg-stone-800">{{ selectedIndex + 1 }} of {{ files.length }}</span></div><div class="flex gap-3 overflow-x-auto pb-3"><MediaThumbnailCard v-for="file in files" :key="file.id" :file="file" :selected="file.id === selectedFile?.id" @select="selectedId = file.id" /></div></div>
    </section>
</template>
<style scoped>
.media-fade-enter-active,.media-fade-leave-active{transition:opacity .18s ease}.media-fade-enter-from,.media-fade-leave-to{opacity:0}
</style>
