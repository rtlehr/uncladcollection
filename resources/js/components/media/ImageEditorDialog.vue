<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Crop, Minus, Plus, RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type ImageEditorPreset = {
    key: string;
    label: string;
    width: number;
    height: number;
    outputType?: 'image/jpeg' | 'image/png' | 'image/webp';
    quality?: number;
};

export type ImageEditData = {
    preset: string;
    zoom: number;
    offsetX: number;
    offsetY: number;
    outputWidth: number;
    outputHeight: number;
};

const props = withDefaults(defineProps<{
    open: boolean;
    source: File | string | null;
    filename?: string;
    preset?: ImageEditorPreset;
    initialEdit?: Partial<ImageEditData> | null;
}>(), {
    filename: 'edited-image.jpg',
    preset: () => ({
        key: 'marketing-hero',
        label: 'Marketing hero',
        width: 1920,
        height: 800,
        outputType: 'image/jpeg',
        quality: 0.9,
    }),
    initialEdit: null,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    apply: [payload: { file: File; edit: ImageEditData; previewUrl: string }];
}>();

const imageEl = ref<HTMLImageElement | null>(null);
const stageEl = ref<HTMLDivElement | null>(null);
const sourceUrl = ref<string | null>(null);
const naturalWidth = ref(0);
const naturalHeight = ref(0);
const zoom = ref(1);
const offsetX = ref(0);
const offsetY = ref(0);
const dragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const dragOriginX = ref(0);
const dragOriginY = ref(0);
const processing = ref(false);
const editorError = ref<string | null>(null);

const aspectRatio = computed(() => props.preset.width / props.preset.height);
const baseScale = computed(() => {
    if (!naturalWidth.value || !naturalHeight.value || !stageEl.value) return 1;
    return Math.max(
        stageEl.value.clientWidth / naturalWidth.value,
        stageEl.value.clientHeight / naturalHeight.value,
    );
});
const renderedWidth = computed(() => naturalWidth.value * baseScale.value * zoom.value);
const renderedHeight = computed(() => naturalHeight.value * baseScale.value * zoom.value);
const imageStyle = computed(() => ({
    width: `${renderedWidth.value}px`,
    height: `${renderedHeight.value}px`,
    transform: `translate(calc(-50% + ${offsetX.value}px), calc(-50% + ${offsetY.value}px))`,
}));

function revokeSourceUrl(): void {
    if (sourceUrl.value?.startsWith('blob:')) URL.revokeObjectURL(sourceUrl.value);
    sourceUrl.value = null;
}

function sourceCandidates(source: string): string[] {
    const candidates = new Set<string>();

    candidates.add(source);

    try {
        const parsed = new URL(source, window.location.origin);

        // Absolute image URLs stored while using another APP_URL (for example
        // staging versus localhost, or localhost versus 127.0.0.1) should be
        // retried against the current application origin.
        candidates.add(`${window.location.origin}${parsed.pathname}${parsed.search}`);
    } catch {
        // The original source remains the only candidate.
    }

    return [...candidates];
}

async function fetchImageBlob(source: string): Promise<Blob> {
    let lastError: unknown = null;

    for (const candidate of sourceCandidates(source)) {
        try {
            const response = await fetch(candidate, {
                credentials: 'include',
                cache: 'no-store',
                headers: {
                    Accept: 'image/*',
                },
            });

            if (!response.ok) {
                lastError = new Error(
                    `Unable to load the saved image (${response.status}).`,
                );
                continue;
            }

            const blob = await response.blob();

            if (!blob.type.startsWith('image/')) {
                lastError = new Error(
                    'The saved image URL returned a non-image response.',
                );
                continue;
            }

            return blob;
        } catch (error) {
            lastError = error;
        }
    }

    throw (
        lastError instanceof Error
            ? lastError
            : new Error('Unable to fetch the saved image.')
    );
}

async function loadSource(): Promise<void> {
    revokeSourceUrl();
    editorError.value = null;

    if (!props.source) {
        return;
    }

    try {
        if (typeof props.source === 'string') {
            const blob = await fetchImageBlob(props.source);
            sourceUrl.value = URL.createObjectURL(blob);
        } else {
            sourceUrl.value = URL.createObjectURL(props.source);
        }

        await nextTick();
        resetFromInitial();
    } catch (error) {
        editorError.value =
            error instanceof Error
                ? error.message
                : 'Unable to load the image for editing.';
    }
}

function resetFromInitial(): void {
    zoom.value = Math.max(1, props.initialEdit?.zoom ?? 1);
    offsetX.value = props.initialEdit?.offsetX ?? 0;
    offsetY.value = props.initialEdit?.offsetY ?? 0;
    nextTick(clampOffsets);
}

function onImageLoad(event: Event): void {
    const image = event.target as HTMLImageElement;
    naturalWidth.value = image.naturalWidth;
    naturalHeight.value = image.naturalHeight;
    nextTick(clampOffsets);
}

function clampOffsets(): void {
    const stage = stageEl.value;
    if (!stage) return;
    const maxX = Math.max(0, (renderedWidth.value - stage.clientWidth) / 2);
    const maxY = Math.max(0, (renderedHeight.value - stage.clientHeight) / 2);
    offsetX.value = Math.min(maxX, Math.max(-maxX, offsetX.value));
    offsetY.value = Math.min(maxY, Math.max(-maxY, offsetY.value));
}

function beginDrag(event: PointerEvent): void {
    dragging.value = true;
    dragStartX.value = event.clientX;
    dragStartY.value = event.clientY;
    dragOriginX.value = offsetX.value;
    dragOriginY.value = offsetY.value;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function moveDrag(event: PointerEvent): void {
    if (!dragging.value) return;
    offsetX.value = dragOriginX.value + event.clientX - dragStartX.value;
    offsetY.value = dragOriginY.value + event.clientY - dragStartY.value;
    clampOffsets();
}

function endDrag(): void { dragging.value = false; }
function changeZoom(delta: number): void {
    zoom.value = Math.min(4, Math.max(1, Number((zoom.value + delta).toFixed(2))));
    nextTick(clampOffsets);
}
function reset(): void { zoom.value = 1; offsetX.value = 0; offsetY.value = 0; nextTick(clampOffsets); }
function close(): void { emit('update:open', false); }

async function apply(): Promise<void> {
    const image = imageEl.value;
    const stage = stageEl.value;

    editorError.value = null;

    if (!image || !stage || !naturalWidth.value) {
        editorError.value = 'The image has not finished loading yet.';
        return;
    }

    processing.value = true;

    try {
        const canvas = document.createElement('canvas');
        canvas.width = props.preset.width;
        canvas.height = props.preset.height;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Canvas is unavailable.');
        const scaleToOutput = props.preset.width / stage.clientWidth;
        const outputWidth = renderedWidth.value * scaleToOutput;
        const outputHeight = renderedHeight.value * scaleToOutput;
        const outputX = (props.preset.width - outputWidth) / 2 + offsetX.value * scaleToOutput;
        const outputY = (props.preset.height - outputHeight) / 2 + offsetY.value * scaleToOutput;
        ctx.drawImage(image, outputX, outputY, outputWidth, outputHeight);
        const type = props.preset.outputType ?? 'image/jpeg';
        const blob = await new Promise<Blob>((resolve, reject) =>
            canvas.toBlob(value => value ? resolve(value) : reject(new Error('Image export failed.')), type, props.preset.quality ?? 0.9),
        );
        const extension = type === 'image/png' ? 'png' : type === 'image/webp' ? 'webp' : 'jpg';
        const base = props.filename.replace(/\.[^.]+$/, '') || 'edited-image';
        const file = new File([blob], `${base}.${extension}`, { type });
        const previewUrl = URL.createObjectURL(blob);
        emit('apply', {
            file,
            previewUrl,
            edit: {
                preset: props.preset.key,
                zoom: zoom.value,
                offsetX: offsetX.value,
                offsetY: offsetY.value,
                outputWidth: props.preset.width,
                outputHeight: props.preset.height,
            },
        });
        close();
    } catch (error) {
        editorError.value =
            error instanceof Error
                ? error.message
                : 'The edited image could not be created.';
    } finally {
        processing.value = false;
    }
}

watch(() => props.open, value => { if (value) loadSource(); });
watch(() => props.source, () => { if (props.open) loadSource(); });
watch(zoom, () => nextTick(clampOffsets));
onBeforeUnmount(revokeSourceUrl);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[94vh] w-[96vw] max-w-[1600px] flex-col overflow-hidden p-0 sm:max-w-[1600px]">
            <DialogHeader class="shrink-0 border-b px-6 py-5">
                <DialogTitle class="flex items-center gap-2"><Crop class="h-5 w-5" /> Edit image</DialogTitle>
                <DialogDescription>Drag to position the image, then zoom until the crop looks right. A new {{ preset.width }} × {{ preset.height }} image will be saved.</DialogDescription>
            </DialogHeader>

            <div class="grid min-h-0 flex-1 gap-6 overflow-y-auto p-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div class="flex min-h-[360px] items-center rounded-xl bg-muted p-4 lg:min-h-[520px]">
                    <div
                        ref="stageEl"
                        class="relative mx-auto w-full touch-none overflow-hidden rounded-lg bg-black/90 shadow-inner"
                        :style="{ aspectRatio: String(aspectRatio), cursor: dragging ? 'grabbing' : 'grab' }"
                        @pointerdown="beginDrag"
                        @pointermove="moveDrag"
                        @pointerup="endDrag"
                        @pointercancel="endDrag"
                    >
                        <img
                            v-if="sourceUrl"
                            ref="imageEl"
                            :src="sourceUrl"
                            alt="Image being edited"
                            draggable="false"
                            class="pointer-events-none absolute left-1/2 top-1/2 max-w-none select-none"
                            :style="imageStyle"
                            @load="onImageLoad"
                            @error="editorError = 'The image could not be displayed in the editor.'"
                        />
                        <div class="pointer-events-none absolute inset-0 border border-white/40" />
                        <div class="pointer-events-none absolute inset-x-0 top-1/3 border-t border-dashed border-white/30" />
                        <div class="pointer-events-none absolute inset-x-0 top-2/3 border-t border-dashed border-white/30" />
                        <div class="pointer-events-none absolute inset-y-0 left-1/3 border-l border-dashed border-white/30" />
                        <div class="pointer-events-none absolute inset-y-0 left-2/3 border-l border-dashed border-white/30" />
                    </div>
                </div>

                <aside class="h-fit space-y-5 rounded-xl border p-5 lg:sticky lg:top-0">
                    <div><p class="text-sm font-medium">Output preset</p><p class="mt-1 text-sm text-muted-foreground">{{ preset.label }}<br>{{ preset.width }} × {{ preset.height }}</p></div>
                    <div>
                        <div class="mb-2 flex items-center justify-between"><span class="text-sm font-medium">Zoom</span><span class="text-xs text-muted-foreground">{{ zoom.toFixed(2) }}×</span></div>
                        <div class="flex items-center gap-2">
                            <Button type="button" size="icon" variant="outline" @click="changeZoom(-0.1)"><Minus class="h-4 w-4" /></Button>
                            <input v-model.number="zoom" class="w-full" type="range" min="1" max="4" step="0.01">
                            <Button type="button" size="icon" variant="outline" @click="changeZoom(0.1)"><Plus class="h-4 w-4" /></Button>
                        </div>
                    </div>
                    <Button type="button" variant="outline" class="w-full" @click="reset"><RotateCcw class="mr-2 h-4 w-4" /> Reset crop</Button>
                    <p class="text-xs leading-5 text-muted-foreground">The original upload is retained so the crop can be edited again later.</p>

                    <p
                        v-if="editorError"
                        class="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
                    >
                        {{ editorError }}
                    </p>
                </aside>
            </div>

            <DialogFooter class="shrink-0 border-t px-6 py-4">
                <Button type="button" variant="outline" @click="close">Cancel</Button>
                <Button type="button" :disabled="processing || !sourceUrl" @click="apply">{{ processing ? 'Creating image…' : 'Use edited image' }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
