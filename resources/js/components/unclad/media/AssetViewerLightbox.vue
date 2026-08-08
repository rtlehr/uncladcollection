<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    Maximize2,
    Minimize2,
    RotateCcw,
    X,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';
import MediaPreviewUnavailable from './MediaPreviewUnavailable.vue';

const props = defineProps<{
    open: boolean;
    files: MediaPresentationFile[];
    selectedId: number | null;
    assetTitle: string;
}>();

const emit = defineEmits<{
    close: [];
    select: [id: number];
}>();

const zoom = ref(1);
const isFullscreen = ref(false);

const selectedIndex = computed(() =>
    props.files.findIndex((file) => file.id === props.selectedId),
);
const selectedFile = computed(
    () => props.files[selectedIndex.value] ?? props.files[0] ?? null,
);

function resetZoom(): void {
    zoom.value = 1;
}

function move(offset: number): void {
    if (props.files.length < 2) {
return;
}

    const current = selectedIndex.value < 0 ? 0 : selectedIndex.value;
    const next = (current + offset + props.files.length) % props.files.length;
    emit('select', props.files[next].id);
    resetZoom();
}

function handleKeydown(event: KeyboardEvent): void {
    if (!props.open) {
return;
}

    if (event.key === 'Escape') {
emit('close');
}

    if (event.key === 'ArrowLeft') {
move(-1);
}

    if (event.key === 'ArrowRight') {
move(1);
}

    if (event.key === '+' || event.key === '=') {
zoom.value = Math.min(2.5, zoom.value + 0.2);
}

    if (event.key === '-') {
zoom.value = Math.max(0.5, zoom.value - 0.2);
}

    if (event.key === '0') {
resetZoom();
}
}

async function toggleFullscreen(): Promise<void> {
    if (!document.fullscreenElement) {
        await document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        await document.exitFullscreen();
        isFullscreen.value = false;
    }
}

function onFullscreenChange(): void {
    isFullscreen.value = Boolean(document.fullscreenElement);
}

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';

        if (!open) {
resetZoom();
}
    },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    document.addEventListener('fullscreenchange', onFullscreenChange);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="viewer-lightbox">
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex flex-col bg-black/95 text-white"
                role="dialog"
                aria-modal="true"
                :aria-label="`${assetTitle} full-screen preview`"
            >
                <header
                    class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3 sm:px-6"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ assetTitle }}
                        </p>
                        <p
                            v-if="selectedFile"
                            class="truncate text-xs text-white/55"
                        >
                            {{ selectedFile.role_label || selectedFile.role }} ·
                            {{ selectedFile.extension }}
                        </p>
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-if="selectedFile?.preview_kind === 'image'">
                            <button
                                type="button"
                                class="viewer-action"
                                aria-label="Zoom out"
                                @click="zoom = Math.max(0.5, zoom - 0.2)"
                            >
                                <ZoomOut class="h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                class="viewer-action hidden text-xs font-semibold sm:inline-flex"
                                aria-label="Reset zoom"
                                @click="resetZoom"
                            >
                                {{ Math.round(zoom * 100) }}%
                            </button>
                            <button
                                type="button"
                                class="viewer-action"
                                aria-label="Zoom in"
                                @click="zoom = Math.min(2.5, zoom + 0.2)"
                            >
                                <ZoomIn class="h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                class="viewer-action"
                                aria-label="Reset zoom"
                                @click="resetZoom"
                            >
                                <RotateCcw class="h-5 w-5" />
                            </button>
                        </template>

                        <button
                            type="button"
                            class="viewer-action"
                            :aria-label="
                                isFullscreen
                                    ? 'Exit fullscreen'
                                    : 'Enter fullscreen'
                            "
                            @click="toggleFullscreen"
                        >
                            <Minimize2 v-if="isFullscreen" class="h-5 w-5" />
                            <Maximize2 v-else class="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            class="viewer-action"
                            aria-label="Close preview"
                            @click="emit('close')"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </header>

                <div
                    class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden p-4 sm:p-8"
                >
                    <button
                        v-if="files.length > 1"
                        type="button"
                        class="absolute left-3 z-10 rounded-full border border-white/15 bg-black/50 p-3 backdrop-blur transition hover:bg-black/75 sm:left-6"
                        aria-label="Previous file"
                        @click="move(-1)"
                    >
                        <ChevronLeft class="h-6 w-6" />
                    </button>

                    <div
                        class="flex h-full w-full items-center justify-center overflow-auto"
                    >
                        <img
                            v-if="
                                selectedFile?.preview_kind === 'image' &&
                                selectedFile.preview_url
                            "
                            :src="selectedFile.preview_url"
                            :alt="`${assetTitle} — ${selectedFile.role_label || selectedFile.original_filename}`"
                            class="max-h-full max-w-full object-contain transition-transform duration-150 select-none"
                            :style="{ transform: `scale(${zoom})` }"
                            draggable="false"
                        />

                        <video
                            v-else-if="
                                selectedFile?.preview_kind === 'video' &&
                                selectedFile.preview_url
                            "
                            :key="selectedFile.id"
                            class="max-h-full max-w-full bg-black object-contain"
                            controls
                            autoplay
                            playsinline
                            :poster="selectedFile.poster_url || undefined"
                        >
                            <source
                                :src="selectedFile.preview_url"
                                :type="selectedFile.mime_type || undefined"
                            />
                        </video>

                        <iframe
                            v-else-if="
                                selectedFile?.preview_kind === 'document' &&
                                selectedFile.preview_url
                            "
                            :key="selectedFile.id"
                            :src="selectedFile.preview_url"
                            :title="`${assetTitle} document preview`"
                            class="h-full w-full max-w-6xl bg-white"
                            sandbox
                        />

                        <MediaPreviewUnavailable
                            v-else
                            :file="selectedFile"
                            :title="assetTitle"
                        />
                    </div>

                    <button
                        v-if="files.length > 1"
                        type="button"
                        class="absolute right-3 z-10 rounded-full border border-white/15 bg-black/50 p-3 backdrop-blur transition hover:bg-black/75 sm:right-6"
                        aria-label="Next file"
                        @click="move(1)"
                    >
                        <ChevronRight class="h-6 w-6" />
                    </button>
                </div>

                <footer
                    v-if="files.length > 1"
                    class="border-t border-white/10 px-4 py-3 text-center text-xs text-white/55"
                >
                    {{ selectedIndex + 1 }} of {{ files.length }} · Use arrow
                    keys to navigate
                </footer>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.viewer-action {
    display: inline-flex;
    height: 2.5rem;
    min-width: 2.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(255 255 255 / 0.06);
    padding-inline: 0.65rem;
    transition:
        background-color 150ms ease,
        border-color 150ms ease;
}
.viewer-action:hover {
    background: rgb(255 255 255 / 0.14);
    border-color: rgb(255 255 255 / 0.22);
}
.viewer-lightbox-enter-active,
.viewer-lightbox-leave-active {
    transition: opacity 0.2s ease;
}
.viewer-lightbox-enter-from,
.viewer-lightbox-leave-to {
    opacity: 0;
}
</style>
