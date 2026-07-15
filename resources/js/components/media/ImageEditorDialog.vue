<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import {
    Focus,
    Crop,
    Crosshair,
    Grid3X3,
    Maximize2,
    Minus,
    Plus,
    RotateCcw,
    RotateCw,
    Undo2,
} from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type ImageEditorOverlay =
    | 'none'
    | 'thirds'
    | 'crosshair'
    | 'safe-area'
    | 'mobile-safe-area';

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
    rotation: number;
    focusX: number;
    focusY: number;
    overlay: ImageEditorOverlay;
    outputWidth: number;
    outputHeight: number;
};

const props = withDefaults(
    defineProps<{
        open: boolean;
        source: File | string | null;
        filename?: string;
        preset?: ImageEditorPreset;
        presets?: ImageEditorPreset[];
        allowPresetSelection?: boolean;
        initialEdit?: Partial<ImageEditData> | null;
    }>(),
    {
        filename: 'edited-image.jpg',
        preset: () => ({
            key: 'marketing-hero',
            label: 'Marketing hero',
            width: 1920,
            height: 800,
            outputType: 'image/jpeg',
            quality: 0.9,
        }),
        presets: () => [],
        allowPresetSelection: false,
        initialEdit: null,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    apply: [payload: { file: File; edit: ImageEditData; previewUrl: string }];
}>();

const imageEl = ref<HTMLImageElement | null>(null);
const stageEl = ref<HTMLDivElement | null>(null);
const sourceUrl = ref<string | null>(null);
const naturalWidth = ref(0);
const naturalHeight = ref(0);
const selectedPresetKey = ref(props.initialEdit?.preset ?? props.preset.key);
const zoom = ref(1);
const offsetX = ref(0);
const offsetY = ref(0);
const rotation = ref(0);
const focusX = ref(0.5);
const focusY = ref(0.5);
const overlay = ref<ImageEditorOverlay>('thirds');
const focusMode = ref(false);
const dragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const dragOriginX = ref(0);
const dragOriginY = ref(0);
const processing = ref(false);
const editorError = ref<string | null>(null);

const availablePresets = computed<ImageEditorPreset[]>(() => {
    const presets = props.presets.length ? props.presets : [props.preset];

    return presets.some((item) => item.key === props.preset.key)
        ? presets
        : [props.preset, ...presets];
});

const activePreset = computed<ImageEditorPreset>(() => {
    return (
        availablePresets.value.find(
            (item) => item.key === selectedPresetKey.value,
        ) ?? props.preset
    );
});

const aspectRatio = computed(
    () => activePreset.value.width / activePreset.value.height,
);

const baseScale = computed(() => {
    if (!naturalWidth.value || !naturalHeight.value || !stageEl.value) {
        return 1;
    }

    return Math.max(
        stageEl.value.clientWidth / naturalWidth.value,
        stageEl.value.clientHeight / naturalHeight.value,
    );
});

const renderedWidth = computed(
    () => naturalWidth.value * baseScale.value * zoom.value,
);
const renderedHeight = computed(
    () => naturalHeight.value * baseScale.value * zoom.value,
);

const rotatedBounds = computed(() => {
    const radians = (Math.abs(rotation.value) * Math.PI) / 180;
    const cos = Math.abs(Math.cos(radians));
    const sin = Math.abs(Math.sin(radians));

    return {
        width: renderedWidth.value * cos + renderedHeight.value * sin,
        height: renderedWidth.value * sin + renderedHeight.value * cos,
    };
});

const imageStyle = computed(() => ({
    width: `${renderedWidth.value}px`,
    height: `${renderedHeight.value}px`,
    transform: `translate(calc(-50% + ${offsetX.value}px), calc(-50% + ${offsetY.value}px)) rotate(${rotation.value}deg)`,
}));

const zoomPercent = computed(() => `${Math.round(zoom.value * 100)}%`);
const rotationLabel = computed(() => `${rotation.value.toFixed(1)}°`);

function revokeSourceUrl(): void {
    if (sourceUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(sourceUrl.value);
    }

    sourceUrl.value = null;
}

function sourceCandidates(source: string): string[] {
    const candidates = new Set<string>();
    candidates.add(source);

    try {
        const parsed = new URL(source, window.location.origin);

        candidates.add(
            `${window.location.origin}${parsed.pathname}${parsed.search}`,
        );
    } catch {
        // Keep the original source as the only candidate.
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
    selectedPresetKey.value =
        props.initialEdit?.preset ?? props.preset.key;
    zoom.value = Math.max(1, props.initialEdit?.zoom ?? 1);
    offsetX.value = props.initialEdit?.offsetX ?? 0;
    offsetY.value = props.initialEdit?.offsetY ?? 0;
    rotation.value = props.initialEdit?.rotation ?? 0;
    focusX.value = props.initialEdit?.focusX ?? 0.5;
    focusY.value = props.initialEdit?.focusY ?? 0.5;
    overlay.value = props.initialEdit?.overlay ?? 'thirds';
    focusMode.value = false;

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

    if (!stage) {
        return;
    }

    const maxX = Math.max(
        0,
        (rotatedBounds.value.width - stage.clientWidth) / 2,
    );
    const maxY = Math.max(
        0,
        (rotatedBounds.value.height - stage.clientHeight) / 2,
    );

    offsetX.value = Math.min(maxX, Math.max(-maxX, offsetX.value));
    offsetY.value = Math.min(maxY, Math.max(-maxY, offsetY.value));
}

function beginDrag(event: PointerEvent): void {
    if (focusMode.value) {
        setFocusPoint(event);
        return;
    }

    dragging.value = true;
    dragStartX.value = event.clientX;
    dragStartY.value = event.clientY;
    dragOriginX.value = offsetX.value;
    dragOriginY.value = offsetY.value;

    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function moveDrag(event: PointerEvent): void {
    if (!dragging.value) {
        return;
    }

    offsetX.value =
        dragOriginX.value + event.clientX - dragStartX.value;
    offsetY.value =
        dragOriginY.value + event.clientY - dragStartY.value;

    clampOffsets();
}

function endDrag(): void {
    dragging.value = false;
}

function setFocusPoint(event: PointerEvent): void {
    const stage = stageEl.value;

    if (!stage) {
        return;
    }

    const rect = stage.getBoundingClientRect();

    focusX.value = Math.min(
        1,
        Math.max(0, (event.clientX - rect.left) / rect.width),
    );
    focusY.value = Math.min(
        1,
        Math.max(0, (event.clientY - rect.top) / rect.height),
    );

    focusMode.value = false;
}

function changeZoom(delta: number): void {
    zoom.value = Math.min(
        4,
        Math.max(1, Number((zoom.value + delta).toFixed(2))),
    );

    nextTick(clampOffsets);
}

function onWheel(event: WheelEvent): void {
    event.preventDefault();
    changeZoom(event.deltaY < 0 ? 0.08 : -0.08);
}

function rotateBy(degrees: number): void {
    rotation.value = Number((rotation.value + degrees).toFixed(1));

    if (rotation.value > 180) {
        rotation.value -= 360;
    }

    if (rotation.value < -180) {
        rotation.value += 360;
    }

    nextTick(clampOffsets);
}

function fitImage(): void {
    zoom.value = 1;
    offsetX.value = 0;
    offsetY.value = 0;
    nextTick(clampOffsets);
}

function fillFrame(): void {
    zoom.value = 1.15;
    offsetX.value = 0;
    offsetY.value = 0;
    nextTick(clampOffsets);
}

function centerImage(): void {
    offsetX.value = 0;
    offsetY.value = 0;
    nextTick(clampOffsets);
}

function reset(): void {
    selectedPresetKey.value = props.preset.key;
    zoom.value = 1;
    offsetX.value = 0;
    offsetY.value = 0;
    rotation.value = 0;
    focusX.value = 0.5;
    focusY.value = 0.5;
    overlay.value = 'thirds';
    focusMode.value = false;
    nextTick(clampOffsets);
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        if (focusMode.value) {
            focusMode.value = false;
            return;
        }

        close();
        return;
    }

    const step = event.shiftKey ? 10 : 2;

    if (event.key === 'ArrowLeft') {
        offsetX.value -= step;
    } else if (event.key === 'ArrowRight') {
        offsetX.value += step;
    } else if (event.key === 'ArrowUp') {
        offsetY.value -= step;
    } else if (event.key === 'ArrowDown') {
        offsetY.value += step;
    } else {
        return;
    }

    event.preventDefault();
    clampOffsets();
}

function close(): void {
    emit('update:open', false);
}

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
        const preset = activePreset.value;
        const canvas = document.createElement('canvas');
        canvas.width = preset.width;
        canvas.height = preset.height;

        const ctx = canvas.getContext('2d');

        if (!ctx) {
            throw new Error('Canvas is unavailable.');
        }

        const scaleToOutput = preset.width / stage.clientWidth;
        const outputWidth = renderedWidth.value * scaleToOutput;
        const outputHeight = renderedHeight.value * scaleToOutput;
        const outputCenterX =
            preset.width / 2 + offsetX.value * scaleToOutput;
        const outputCenterY =
            preset.height / 2 + offsetY.value * scaleToOutput;

        ctx.save();
        ctx.translate(outputCenterX, outputCenterY);
        ctx.rotate((rotation.value * Math.PI) / 180);
        ctx.drawImage(
            image,
            -outputWidth / 2,
            -outputHeight / 2,
            outputWidth,
            outputHeight,
        );
        ctx.restore();

        const type = preset.outputType ?? 'image/jpeg';
        const blob = await new Promise<Blob>((resolve, reject) =>
            canvas.toBlob(
                (value) =>
                    value
                        ? resolve(value)
                        : reject(new Error('Image export failed.')),
                type,
                preset.quality ?? 0.9,
            ),
        );

        const extension =
            type === 'image/png'
                ? 'png'
                : type === 'image/webp'
                  ? 'webp'
                  : 'jpg';
        const base =
            props.filename.replace(/\.[^.]+$/, '') || 'edited-image';
        const file = new File([blob], `${base}.${extension}`, {
            type,
            lastModified: Date.now(),
        });
        const previewUrl = URL.createObjectURL(blob);

        emit('apply', {
            file,
            previewUrl,
            edit: {
                preset: preset.key,
                zoom: zoom.value,
                offsetX: offsetX.value,
                offsetY: offsetY.value,
                rotation: rotation.value,
                focusX: focusX.value,
                focusY: focusY.value,
                overlay: overlay.value,
                outputWidth: preset.width,
                outputHeight: preset.height,
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

watch(
    () => props.open,
    (value) => {
        if (value) {
            loadSource();
        }
    },
);
watch(
    () => props.source,
    () => {
        if (props.open) {
            loadSource();
        }
    },
);
watch(
    () => activePreset.value.key,
    () => nextTick(clampOffsets),
);
watch([zoom, rotation], () => nextTick(clampOffsets));

onBeforeUnmount(revokeSourceUrl);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[95vh] w-[97vw] max-w-[1700px] flex-col overflow-hidden p-0 sm:max-w-[1700px]"
            @keydown="handleKeydown"
        >
            <DialogHeader class="shrink-0 border-b px-6 py-5">
                <DialogTitle class="flex items-center gap-2">
                    <Crop class="h-5 w-5" />
                    Universal Image Editor
                </DialogTitle>

                <DialogDescription>
                    Drag to position, use the mouse wheel to zoom, and use
                    arrow keys for precise adjustments.
                </DialogDescription>
            </DialogHeader>

            <div
                class="grid min-h-0 flex-1 gap-6 overflow-y-auto p-6 lg:grid-cols-[minmax(0,1fr)_320px]"
            >
                <div
                    class="flex min-h-[380px] items-center rounded-xl bg-muted p-4 lg:min-h-[570px]"
                >
                    <div
                        ref="stageEl"
                        tabindex="0"
                        class="relative mx-auto w-full touch-none overflow-hidden rounded-lg bg-black/90 shadow-inner outline-none ring-offset-background focus-visible:ring-2 focus-visible:ring-ring"
                        :class="
                            focusMode
                                ? 'cursor-crosshair'
                                : dragging
                                  ? 'cursor-grabbing'
                                  : 'cursor-grab'
                        "
                        :style="{ aspectRatio: String(aspectRatio) }"
                        @dblclick="centerImage"
                        @pointerdown="beginDrag"
                        @pointermove="moveDrag"
                        @pointerup="endDrag"
                        @pointercancel="endDrag"
                        @wheel="onWheel"
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
                            @error="
                                editorError =
                                    'The image could not be displayed in the editor.'
                            "
                        />

                        <div
                            class="pointer-events-none absolute inset-0 border border-white/40"
                        />

                        <template v-if="overlay === 'thirds'">
                            <div
                                class="pointer-events-none absolute inset-x-0 top-1/3 border-t border-dashed border-white/45"
                            />
                            <div
                                class="pointer-events-none absolute inset-x-0 top-2/3 border-t border-dashed border-white/45"
                            />
                            <div
                                class="pointer-events-none absolute inset-y-0 left-1/3 border-l border-dashed border-white/45"
                            />
                            <div
                                class="pointer-events-none absolute inset-y-0 left-2/3 border-l border-dashed border-white/45"
                            />
                        </template>

                        <template v-if="overlay === 'crosshair'">
                            <div
                                class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-white/50"
                            />
                            <div
                                class="pointer-events-none absolute inset-y-0 left-1/2 border-l border-dashed border-white/50"
                            />
                        </template>

                        <div
                            v-if="overlay === 'safe-area'"
                            class="pointer-events-none absolute inset-[8%] rounded border-2 border-dashed border-amber-300/80"
                        />

                        <div
                            v-if="overlay === 'mobile-safe-area'"
                            class="pointer-events-none absolute inset-y-[7%] left-[27%] right-[27%] rounded border-2 border-dashed border-cyan-300/80"
                        />

                        <div
                            class="pointer-events-none absolute h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary shadow-lg"
                            :style="{
                                left: `${focusX * 100}%`,
                                top: `${focusY * 100}%`,
                            }"
                            title="Saved focal point"
                        />

                        <div
                            v-if="focusMode"
                            class="pointer-events-none absolute inset-x-4 bottom-4 rounded-lg bg-black/70 px-4 py-2 text-center text-sm text-white"
                        >
                            Click the most important point in the image.
                        </div>
                    </div>
                </div>

                <aside
                    class="h-fit space-y-5 rounded-xl border p-5 lg:sticky lg:top-0"
                >
                    <div>
                        <p class="text-sm font-medium">Output preset</p>

                        <select
                            v-if="
                                allowPresetSelection &&
                                availablePresets.length > 1
                            "
                            v-model="selectedPresetKey"
                            class="mt-2 h-10 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            <option
                                v-for="item in availablePresets"
                                :key="item.key"
                                :value="item.key"
                            >
                                {{ item.label }} — {{ item.width }} ×
                                {{ item.height }}
                            </option>
                        </select>

                        <p v-else class="mt-1 text-sm text-muted-foreground">
                            {{ activePreset.label }}<br />
                            {{ activePreset.width }} ×
                            {{ activePreset.height }}
                        </p>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-medium">Zoom</span>
                            <span class="text-xs text-muted-foreground">
                                {{ zoomPercent }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                size="icon"
                                variant="outline"
                                @click="changeZoom(-0.1)"
                            >
                                <Minus class="h-4 w-4" />
                            </Button>

                            <input
                                v-model.number="zoom"
                                class="w-full"
                                type="range"
                                min="1"
                                max="4"
                                step="0.01"
                            />

                            <Button
                                type="button"
                                size="icon"
                                variant="outline"
                                @click="changeZoom(0.1)"
                            >
                                <Plus class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-medium">Rotation</span>
                            <span class="text-xs text-muted-foreground">
                                {{ rotationLabel }}
                            </span>
                        </div>

                        <input
                            v-model.number="rotation"
                            class="w-full"
                            type="range"
                            min="-180"
                            max="180"
                            step="0.5"
                        />

                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="rotateBy(-90)"
                            >
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Left
                            </Button>

                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="rotateBy(90)"
                            >
                                <RotateCw class="mr-2 h-4 w-4" />
                                Right
                            </Button>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium">Composition overlay</p>

                        <select
                            v-model="overlay"
                            class="mt-2 h-10 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="none">None</option>
                            <option value="thirds">Rule of thirds</option>
                            <option value="crosshair">
                                Center crosshair
                            </option>
                            <option value="safe-area">Safe area</option>
                            <option value="mobile-safe-area">
                                Mobile safe area
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="fitImage"
                        >
                            <Maximize2 class="mr-2 h-4 w-4" />
                            Fit
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="fillFrame"
                        >
                            <Grid3X3 class="mr-2 h-4 w-4" />
                            Fill
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="centerImage"
                        >
                            <Focus class="mr-2 h-4 w-4" />
                            Center
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            :variant="focusMode ? 'default' : 'outline'"
                            @click="focusMode = !focusMode"
                        >
                            <Crosshair class="mr-2 h-4 w-4" />
                            Focal point
                        </Button>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        class="w-full"
                        @click="reset"
                    >
                        <Undo2 class="mr-2 h-4 w-4" />
                        Reset editor
                    </Button>

                    <p class="text-xs leading-5 text-muted-foreground">
                        The original upload and edit settings are retained so
                        the image can be adjusted again later.
                    </p>

                    <p
                        v-if="editorError"
                        class="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
                    >
                        {{ editorError }}
                    </p>
                </aside>
            </div>

            <DialogFooter class="shrink-0 border-t px-6 py-4">
                <Button type="button" variant="outline" @click="close">
                    Cancel
                </Button>

                <Button
                    type="button"
                    :disabled="processing || !sourceUrl"
                    @click="apply"
                >
                    {{
                        processing
                            ? 'Creating image…'
                            : 'Use edited image'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
