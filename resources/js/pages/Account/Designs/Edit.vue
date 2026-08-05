<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    Copy,
    Download,
    ImagePlus,
    Layers3,
    Lock,
    Redo2,
    Save,
    Square,
    Trash2,
    Type,
    Undo2,
    Unlock,
} from '@lucide/vue';
import { Canvas, FabricImage, IText, Rect, type FabricObject } from 'fabric';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

type ExportPreset = { name: string; width: number; height: number };
type ExportRecord = { uuid: string; width: number; height: number; format: string; fit_mode: string; preset_name: string | null; size: string | null; created_at: string; download_url: string; delete_url: string };
type SavedDesign = { version: number; fabric?: Record<string, unknown>; objects?: unknown[] };
interface Project {
    uuid: string;
    title: string;
    canvas_width: number;
    canvas_height: number;
    design_json: SavedDesign;
    source_url: string | null;
    save_url: string;
    upload_url: string;
    export_url: string;
    preview_upload_url: string;
    exports: ExportRecord[];
    uploads: { uuid: string; name: string; url: string }[];
}

const props = defineProps<{ project: Project; export_presets: ExportPreset[] }>();
const canvasElement = ref<HTMLCanvasElement | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const title = ref(props.project.title);
const saving = ref(false);
const savedMessage = ref('');
const selected = ref<FabricObject | null>(null);
const zoom = ref(1);
const exporting = ref(false);
const exportStatus = ref('');
const recentExports = ref<ExportRecord[]>([...props.project.exports]);
const exportFormat = ref<'jpeg' | 'png' | 'webp'>('jpeg');
const exportFit = ref<'contain' | 'cover'>('contain');
const customWidth = ref(props.project.canvas_width);
const customHeight = ref(props.project.canvas_height);
const history = ref<string[]>([]);
const historyIndex = ref(-1);
const layerVersion = ref(0);
const dirty = ref(false);
const snapEnabled = ref(true);
const verticalGuides = ref<number[]>([]);
const horizontalGuides = ref<number[]>([]);
const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(() => historyIndex.value >= 0 && historyIndex.value < history.value.length - 1);
let canvas: Canvas | null = null;
let autosaveTimer: ReturnType<typeof setTimeout> | null = null;
let restoringHistory = false;

const stageDimensions = computed(() => {
    const maxWidth = Math.max(360, window.innerWidth - 590);
    const maxHeight = Math.max(360, window.innerHeight - 150);
    const factor = Math.min(1, maxWidth / props.project.canvas_width, maxHeight / props.project.canvas_height);
    return {
        width: Math.round(props.project.canvas_width * factor * zoom.value),
        height: Math.round(props.project.canvas_height * factor * zoom.value),
    };
});

function fabricJson(): Record<string, unknown> {
    return canvas?.toJSON(['name', 'uploadUuid', 'lockedByUser']) as Record<string, unknown> ?? { objects: [] };
}

function snapshot(): string {
    return JSON.stringify(fabricJson());
}

function pushHistory(): void {
    if (!canvas || restoringHistory) return;
    const current = snapshot();
    if (history.value[historyIndex.value] === current) return;
    history.value = history.value.slice(0, historyIndex.value + 1);
    history.value.push(current);
    if (history.value.length > 60) history.value.shift();
    historyIndex.value = history.value.length - 1;
    layerVersion.value++;
    dirty.value = true;
    scheduleAutosave();
}

async function restoreHistory(index: number): Promise<void> {
    if (!canvas || index < 0 || index >= history.value.length) return;
    restoringHistory = true;
    await canvas.loadFromJSON(JSON.parse(history.value[index]));
    canvas.requestRenderAll();
    historyIndex.value = index;
    selected.value = null;
    restoringHistory = false;
}

function undo(): void { void restoreHistory(historyIndex.value - 1); }
function redo(): void { void restoreHistory(historyIndex.value + 1); }

function scheduleAutosave(): void {
    if (autosaveTimer) clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => save(true), 1200);
}

function save(silent = false): void {
    if (!canvas || saving.value) return;
    saving.value = true;
    router.put(props.project.save_url, {
        title: title.value,
        canvas_width: props.project.canvas_width,
        canvas_height: props.project.canvas_height,
        design_json: { version: 2, fabric: fabricJson() },
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            dirty.value = false;
            void uploadPreview();
            savedMessage.value = silent ? 'Autosaved' : 'Saved';
            window.setTimeout(() => savedMessage.value = '', 1800);
        },
        onFinish: () => saving.value = false,
    });
}


async function uploadPreview(): Promise<void> {
    if (!canvas) return;
    const maxEdge = 720;
    const multiplier = Math.min(1, maxEdge / Math.max(props.project.canvas_width, props.project.canvas_height));
    const previewCanvas = canvas.toCanvasElement(multiplier, { enableRetinaScaling: false });
    const blob = await new Promise<Blob | null>(resolve => previewCanvas.toBlob(resolve, 'image/webp', 0.82));
    if (!blob) return;

    const form = new FormData();
    form.append('preview', blob, 'preview.webp');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    await fetch(props.project.preview_upload_url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        body: form,
    }).catch(() => null);
}

function selectObject(object: FabricObject | null): void {
    selected.value = object;
}

function addText(): void {
    if (!canvas) return;
    const text = new IText('Double-click to edit', {
        left: props.project.canvas_width * 0.1,
        top: props.project.canvas_height * 0.1,
        width: props.project.canvas_width * 0.45,
        fontSize: Math.max(32, Math.round(props.project.canvas_width * 0.035)),
        fill: '#ffffff',
        fontFamily: 'Arial',
        fontWeight: '600',
        textAlign: 'left',
        shadow: 'rgba(0,0,0,.35) 0 2px 6px',
        name: 'Text',
    });
    canvas.add(text);
    canvas.setActiveObject(text);
    canvas.requestRenderAll();
    selectObject(text);
    pushHistory();
}


function addShape(): void {
    if (!canvas) return;
    const shape = new Rect({
        left: props.project.canvas_width * 0.15,
        top: props.project.canvas_height * 0.15,
        width: props.project.canvas_width * 0.28,
        height: props.project.canvas_height * 0.16,
        rx: 18,
        ry: 18,
        fill: 'rgba(15,23,42,0.72)',
        stroke: '#ffffff',
        strokeWidth: 0,
        name: 'Rectangle',
    });
    canvas.add(shape);
    canvas.setActiveObject(shape);
    canvas.requestRenderAll();
    selectObject(shape);
    pushHistory();
}

async function upload(event: Event): Promise<void> {

    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !canvas) return;
    const form = new FormData();
    form.append('image', file);
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const response = await fetch(props.project.upload_url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        body: form,
    });
    if (!response.ok) {
        alert('The image could not be uploaded.');
        return;
    }
    const data = await response.json() as { url: string; uuid: string; name: string };
    const image = await FabricImage.fromURL(data.url, { crossOrigin: 'anonymous' });
    const maxWidth = props.project.canvas_width * 0.45;
    const maxHeight = props.project.canvas_height * 0.45;
    image.scale(Math.min(maxWidth / (image.width || 1), maxHeight / (image.height || 1), 1));
    image.set({
        left: props.project.canvas_width * 0.15,
        top: props.project.canvas_height * 0.15,
        name: data.name,
        uploadUuid: data.uuid,
    });
    canvas.add(image);
    canvas.setActiveObject(image);
    canvas.requestRenderAll();
    selectObject(image);
    pushHistory();
    input.value = '';
}

function removeSelected(): void {
    if (!canvas || !selected.value) return;
    canvas.remove(selected.value);
    selected.value = null;
    canvas.discardActiveObject();
    canvas.requestRenderAll();
    pushHistory();
}

function duplicateSelected(): void {
    if (!canvas || !selected.value) return;
    selected.value.clone().then((copy: FabricObject) => {
        copy.set({ left: (selected.value?.left ?? 0) + 30, top: (selected.value?.top ?? 0) + 30 });
        canvas?.add(copy);
        canvas?.setActiveObject(copy);
        canvas?.requestRenderAll();
        selectObject(copy);
        pushHistory();
    });
}

function moveLayer(direction: 'up' | 'down'): void {
    if (!canvas || !selected.value) return;
    if (direction === 'up') canvas.bringObjectForward(selected.value);
    else canvas.sendObjectBackwards(selected.value);
    canvas.requestRenderAll();
    pushHistory();
}

function toggleLock(): void {
    if (!selected.value || !canvas) return;
    const lock = !selected.value.lockMovementX;
    selected.value.set({
        lockMovementX: lock,
        lockMovementY: lock,
        lockRotation: lock,
        lockScalingX: lock,
        lockScalingY: lock,
        selectable: true,
        lockedByUser: lock,
    });
    canvas.requestRenderAll();
    pushHistory();
}

function updateSelected(property: string, value: unknown): void {
    if (!selected.value || !canvas) return;
    selected.value.set(property as never, value as never);
    selected.value.setCoords();
    canvas.requestRenderAll();
    pushHistory();
}

function objectLabel(object: FabricObject, index: number): string {
    return String(object.get('name') || (object.type === 'i-text' ? `Text ${index + 1}` : object.type === 'rect' ? `Shape ${index + 1}` : `Image ${index + 1}`));
}

const layerObjects = computed(() => { layerVersion.value; return canvas ? [...canvas.getObjects()].reverse() : []; });

function activateLayer(object: FabricObject): void {
    canvas?.setActiveObject(object);
    canvas?.requestRenderAll();
    selectObject(object);
}


function clearGuides(): void {
    verticalGuides.value = [];
    horizontalGuides.value = [];
}

function snapObject(object: FabricObject): void {
    if (!canvas || !snapEnabled.value || object.lockMovementX) return;
    const threshold = Math.max(4, 10 / zoom.value);
    const rect = object.getBoundingRect();
    const canvasWidth = props.project.canvas_width;
    const canvasHeight = props.project.canvas_height;
    const xTargets = [0, canvasWidth / 2, canvasWidth];
    const yTargets = [0, canvasHeight / 2, canvasHeight];
    const objectX = [rect.left, rect.left + rect.width / 2, rect.left + rect.width];
    const objectY = [rect.top, rect.top + rect.height / 2, rect.top + rect.height];

    let dx = 0;
    let dy = 0;
    let xGuide: number | null = null;
    let yGuide: number | null = null;

    for (const target of xTargets) {
        for (const point of objectX) {
            if (Math.abs(target - point) <= threshold) {
                dx = target - point;
                xGuide = target;
                break;
            }
        }
        if (xGuide !== null) break;
    }
    for (const target of yTargets) {
        for (const point of objectY) {
            if (Math.abs(target - point) <= threshold) {
                dy = target - point;
                yGuide = target;
                break;
            }
        }
        if (yGuide !== null) break;
    }

    if (dx || dy) {
        object.set({
            left: (object.left ?? 0) + dx,
            top: (object.top ?? 0) + dy,
        });
        object.setCoords();
    }
    verticalGuides.value = xGuide === null ? [] : [xGuide];
    horizontalGuides.value = yGuide === null ? [] : [yGuide];
}

function renderGuides(): void {
    if (!canvas || (!verticalGuides.value.length && !horizontalGuides.value.length)) return;
    const context = canvas.getContext();
    context.save();
    context.strokeStyle = '#38bdf8';
    context.lineWidth = 2;
    context.setLineDash([10, 8]);
    for (const x of verticalGuides.value) {
        context.beginPath();
        context.moveTo(x, 0);
        context.lineTo(x, props.project.canvas_height);
        context.stroke();
    }
    for (const y of horizontalGuides.value) {
        context.beginPath();
        context.moveTo(0, y);
        context.lineTo(props.project.canvas_width, y);
        context.stroke();
    }
    context.restore();
}

function leaveDesign(event: MouseEvent): void {
    if (dirty.value && !confirm('You have unsaved changes. Leave the editor anyway?')) {
        event.preventDefault();
    }
}

function beforeUnload(event: BeforeUnloadEvent): void {
    if (!dirty.value) return;
    event.preventDefault();
    event.returnValue = '';
}

async function exportDesign(width: number, height: number, name: string): Promise<void> {

    if (!canvas || exporting.value) return;
    exporting.value = true;
    try {
        canvas.discardActiveObject();
        canvas.requestRenderAll();
        const multiplier = Math.max(width / props.project.canvas_width, height / props.project.canvas_height);
        const source = canvas.toCanvasElement(multiplier, { enableRetinaScaling: false });
        const output = document.createElement('canvas');
        output.width = width;
        output.height = height;
        const context = output.getContext('2d');
        if (!context) throw new Error('Canvas export is unavailable.');
        if (exportFormat.value === 'jpeg') {
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, width, height);
        }
        const scale = exportFit.value === 'cover'
            ? Math.max(width / source.width, height / source.height)
            : Math.min(width / source.width, height / source.height);
        const drawWidth = source.width * scale;
        const drawHeight = source.height * scale;
        context.drawImage(source, (width - drawWidth) / 2, (height - drawHeight) / 2, drawWidth, drawHeight);
        const mime = exportFormat.value === 'jpeg' ? 'image/jpeg' : `image/${exportFormat.value}`;
        const blob = await new Promise<Blob | null>(resolve => output.toBlob(resolve, mime, 0.92));
        if (!blob) throw new Error('The browser could not create the download.');
        exportStatus.value = 'Saving export…';
        const form = new FormData();
        form.append('file', blob, `${name}.${exportFormat.value === 'jpeg' ? 'jpg' : exportFormat.value}`);
        form.append('width', String(width));
        form.append('height', String(height));
        form.append('format', exportFormat.value === 'jpeg' ? 'jpg' : exportFormat.value);
        form.append('fit_mode', exportFit.value);
        form.append('preset_name', name);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(props.project.export_url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: form,
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => null) as { message?: string } | null;
            throw new Error(payload?.message ?? 'The completed export could not be saved.');
        }
        const record = await response.json() as ExportRecord;
        recentExports.value = [record, ...recentExports.value.filter(item => item.uuid !== record.uuid)].slice(0, 12);
        exportStatus.value = 'Export saved';
        const anchor = document.createElement('a');
        anchor.href = record.download_url;
        anchor.click();
    } catch (error) {
        alert(error instanceof Error ? error.message : 'The design could not be exported.');
    } finally {
        exporting.value = false;
        window.setTimeout(() => exportStatus.value = '', 2200);
    }
}

function deleteExport(record: ExportRecord): void {
    if (!confirm(`Delete the ${record.width}×${record.height} ${record.format} export?`)) return;
    router.delete(record.delete_url, {
        preserveScroll: true,
        onSuccess: () => recentExports.value = recentExports.value.filter(item => item.uuid !== record.uuid),
    });
}

function keyboard(event: KeyboardEvent): void {
    const target = event.target as HTMLElement;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        event.shiftKey ? redo() : undo();
    } else if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault(); save();
    } else if (event.key === 'Delete' || event.key === 'Backspace') {
        event.preventDefault(); removeSelected();
    }
}

onMounted(async () => {
    await nextTick();
    if (!canvasElement.value) return;
    canvas = new Canvas(canvasElement.value, {
        width: props.project.canvas_width,
        height: props.project.canvas_height,
        preserveObjectStacking: true,
        selectionColor: 'rgba(56,189,248,.14)',
        selectionBorderColor: '#38bdf8',
    });
    canvas.backgroundColor = '#1c1917';
    const saved = props.project.design_json?.fabric;
    if (saved) {
        await canvas.loadFromJSON(saved);
    } else if (Array.isArray(props.project.design_json?.objects)) {
        for (const legacy of props.project.design_json.objects as Array<Record<string, unknown>>) {
            if (legacy.type === 'text') {
                canvas.add(new IText(String(legacy.text ?? ''), {
                    left: Number(legacy.x ?? 0), top: Number(legacy.y ?? 0),
                    fontSize: Number(legacy.fontSize ?? 48), fill: String(legacy.color ?? '#ffffff'),
                    angle: Number(legacy.rotation ?? 0), name: 'Text',
                }));
            } else if (legacy.type === 'image' && legacy.src) {
                const image = await FabricImage.fromURL(String(legacy.src), { crossOrigin: 'anonymous' });
                image.set({ left: Number(legacy.x ?? 0), top: Number(legacy.y ?? 0), angle: Number(legacy.rotation ?? 0), name: 'Image' });
                image.scaleToWidth(Number(legacy.width ?? 400));
                canvas.add(image);
            }
        }
    }
    if (props.project.source_url) {
        const background = await FabricImage.fromURL(props.project.source_url, {
            crossOrigin: 'anonymous',
        });

        // Fabric can occasionally retain dimensions from image metadata that do
        // not match the browser-decoded bitmap. Use the decoded element size so
        // the source image always fills the design canvas correctly.
        const element = background.getElement() as HTMLImageElement;
        if (typeof element.decode === 'function') {
            try {
                await element.decode();
            } catch {
                // The image is already usable when decode() is unsupported or
                // rejects after the browser has completed loading it.
            }
        }

        const sourceWidth = Math.max(1, element.naturalWidth || element.width || background.width || 1);
        const sourceHeight = Math.max(1, element.naturalHeight || element.height || background.height || 1);
        const scale = Math.max(
            props.project.canvas_width / sourceWidth,
            props.project.canvas_height / sourceHeight,
        );
        const renderedWidth = sourceWidth * scale;
        const renderedHeight = sourceHeight * scale;

        background.set({
            width: sourceWidth,
            height: sourceHeight,
            originX: 'left',
            originY: 'top',
            left: (props.project.canvas_width - renderedWidth) / 2,
            top: (props.project.canvas_height - renderedHeight) / 2,
            scaleX: scale,
            scaleY: scale,
            selectable: false,
            evented: false,
            excludeFromExport: false,
        });
        background.setCoords();
        canvas.backgroundImage = background;
    }
    layerVersion.value++;
    canvas.requestRenderAll();
    const initial = snapshot();
    history.value = [initial];
    historyIndex.value = 0;
    canvas.on('selection:created', event => selectObject(event.selected?.[0] ?? null));
    canvas.on('selection:updated', event => selectObject(event.selected?.[0] ?? null));
    canvas.on('selection:cleared', () => selectObject(null));
    canvas.on('object:moving', event => {
        if (event.target) snapObject(event.target);
    });
    canvas.on('object:modified', () => {
        clearGuides();
        pushHistory();
    });
    canvas.on('mouse:up', clearGuides);
    canvas.on('after:render', renderGuides);
    canvas.on('text:changed', () => {
        dirty.value = true;
        scheduleAutosave();
    });
    window.addEventListener('keydown', keyboard);
    window.addEventListener('beforeunload', beforeUnload);
});

onBeforeUnmount(() => {
    if (autosaveTimer) clearTimeout(autosaveTimer);
    window.removeEventListener('keydown', keyboard);
    window.removeEventListener('beforeunload', beforeUnload);
    canvas?.dispose();
});

watch(title, () => { dirty.value = true; scheduleAutosave(); });
watch(stageDimensions, dimensions => {
    if (!canvas) return;
    canvas.setDimensions(dimensions, { cssOnly: true });
});
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-stone-950 text-white">
        <header class="flex flex-wrap items-center gap-2 border-b border-white/10 px-4 py-3">
            <Button variant="ghost" as-child><Link href="/account/designs" @click="leaveDesign"><ArrowLeft class="mr-2 h-4 w-4" />My Designs</Link></Button>
            <input v-model="title" class="min-w-56 flex-1 rounded-lg border border-white/10 bg-white/5 px-3 py-2 font-semibold" />
            <span class="min-w-20 text-right text-xs text-stone-400">{{ savedMessage || (dirty ? 'Unsaved changes' : `${project.canvas_width} × ${project.canvas_height}`) }}</span>
            <Button variant="ghost" size="icon" :disabled="!canUndo" title="Undo" @click="undo"><Undo2 class="h-4 w-4" /></Button>
            <Button variant="ghost" size="icon" :disabled="!canRedo" title="Redo" @click="redo"><Redo2 class="h-4 w-4" /></Button>
            <Button :disabled="saving" @click="save(false)"><Save class="mr-2 h-4 w-4" />{{ saving ? 'Saving…' : 'Save' }}</Button>
        </header>

        <div class="grid min-h-[calc(100vh-65px)] lg:grid-cols-[250px_minmax(0,1fr)_310px]">
            <aside class="border-r border-white/10 p-4">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-stone-400">Add</h2>
                <div class="mt-4 grid gap-2">
                    <Button variant="secondary" class="justify-start" @click="addText"><Type class="mr-2 h-4 w-4" />Add text</Button>
                    <Button variant="secondary" class="justify-start" @click="addShape"><Square class="mr-2 h-4 w-4" />Add shape</Button>
                    <Button variant="secondary" class="justify-start" @click="fileInput?.click()"><ImagePlus class="mr-2 h-4 w-4" />Upload image</Button>
                    <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="upload" />
                </div>

                <label class="mt-5 flex items-center justify-between rounded-lg border border-white/10 px-3 py-2 text-sm text-stone-300">
                    <span>Alignment snapping</span>
                    <input v-model="snapEnabled" type="checkbox" class="h-4 w-4" />
                </label>

                <div class="mt-7 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-stone-400"><Layers3 class="h-4 w-4" />Layers</h2>
                </div>
                <div class="mt-3 space-y-1">
                    <button v-for="(object, index) in layerObjects" :key="index" class="flex w-full items-center justify-between rounded-md px-2 py-2 text-left text-sm hover:bg-white/5" :class="selected === object ? 'bg-sky-500/15 text-sky-200' : 'text-stone-300'" @click="activateLayer(object)">
                        <span class="truncate">{{ objectLabel(object, index) }}</span>
                        <Lock v-if="object.lockMovementX" class="h-3.5 w-3.5" />
                    </button>
                    <p v-if="layerObjects.length === 0" class="py-3 text-xs text-stone-500">Add text or an image to begin.</p>
                </div>
            </aside>

            <main class="overflow-auto p-6">
                <div class="mx-auto flex min-h-full items-center justify-center">
                    <div class="shadow-2xl" :style="{ width: `${stageDimensions.width}px`, height: `${stageDimensions.height}px` }">
                        <canvas ref="canvasElement" />
                    </div>
                </div>
                <div class="mx-auto mt-4 flex max-w-md items-center gap-3 text-xs text-stone-400">
                    <span>Zoom</span><input v-model.number="zoom" type="range" min="0.5" max="1.5" step="0.05" class="w-full" /><span>{{ Math.round(zoom * 100) }}%</span>
                </div>
            </main>

            <aside class="border-l border-white/10 p-4">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-stone-400">Properties</h2>
                <div v-if="selected" class="mt-4 space-y-4">
                    <template v-if="selected.type === 'i-text'">
                        <label class="block text-sm">Text<textarea :value="String(selected.get('text') ?? '')" rows="3" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" @input="updateSelected('text', ($event.target as HTMLTextAreaElement).value)" /></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-sm">Font size<input :value="Number(selected.get('fontSize') ?? 48)" type="number" min="8" max="500" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" @change="updateSelected('fontSize', Number(($event.target as HTMLInputElement).value))" /></label>
                            <label class="text-sm">Color<input :value="String(selected.get('fill') ?? '#ffffff')" type="color" class="mt-2 h-10 w-full rounded" @input="updateSelected('fill', ($event.target as HTMLInputElement).value)" /></label>
                        </div>
                        <label class="block text-sm">Font family<select :value="String(selected.get('fontFamily') ?? 'Arial')" class="mt-2 w-full rounded-lg border border-white/10 bg-stone-900 p-2" @change="updateSelected('fontFamily', ($event.target as HTMLSelectElement).value)"><option>Arial</option><option>Georgia</option><option>Impact</option><option>Tahoma</option><option>Times New Roman</option><option>Trebuchet MS</option><option>Verdana</option></select></label>
                        <div class="grid grid-cols-3 gap-2">
                            <Button variant="secondary" size="sm" @click="updateSelected('textAlign', 'left')"><AlignLeft class="h-4 w-4" /></Button>
                            <Button variant="secondary" size="sm" @click="updateSelected('textAlign', 'center')"><AlignCenter class="h-4 w-4" /></Button>
                            <Button variant="secondary" size="sm" @click="updateSelected('textAlign', 'right')"><AlignRight class="h-4 w-4" /></Button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-sm">Text background<input :value="String(selected.get('textBackgroundColor') || '#000000')" type="color" class="mt-2 h-10 w-full rounded" @input="updateSelected('textBackgroundColor', ($event.target as HTMLInputElement).value)" /></label>
                            <label class="text-sm">Outline color<input :value="String(selected.get('stroke') || '#000000')" type="color" class="mt-2 h-10 w-full rounded" @input="updateSelected('stroke', ($event.target as HTMLInputElement).value)" /></label>
                        </div>
                        <label class="block text-sm">Outline width<input :value="Number(selected.get('strokeWidth') ?? 0)" type="range" min="0" max="12" step="0.5" class="mt-2 w-full" @input="updateSelected('strokeWidth', Number(($event.target as HTMLInputElement).value))" /></label>
                        <label class="flex items-center justify-between rounded-lg border border-white/10 px-3 py-2 text-sm"><span>Text shadow</span><input type="checkbox" :checked="Boolean(selected.get('shadow'))" @change="updateSelected('shadow', ($event.target as HTMLInputElement).checked ? 'rgba(0,0,0,.55) 0 4px 12px' : null)" /></label>
                    </template>
                    <template v-if="selected.type === 'rect'">
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-sm">Fill<input :value="String(selected.get('fill') ?? '#0f172a')" type="color" class="mt-2 h-10 w-full rounded" @input="updateSelected('fill', ($event.target as HTMLInputElement).value)" /></label>
                            <label class="text-sm">Border<input :value="String(selected.get('stroke') ?? '#ffffff')" type="color" class="mt-2 h-10 w-full rounded" @input="updateSelected('stroke', ($event.target as HTMLInputElement).value)" /></label>
                        </div>
                        <label class="block text-sm">Border width<input :value="Number(selected.get('strokeWidth') ?? 0)" type="range" min="0" max="20" step="1" class="mt-2 w-full" @input="updateSelected('strokeWidth', Number(($event.target as HTMLInputElement).value))" /></label>
                        <label class="block text-sm">Corner rounding<input :value="Number(selected.get('rx') ?? 0)" type="range" min="0" max="120" step="2" class="mt-2 w-full" @input="updateSelected('rx', Number(($event.target as HTMLInputElement).value)); updateSelected('ry', Number(($event.target as HTMLInputElement).value))" /></label>
                    </template>
                    <label class="block text-sm">Opacity<input :value="Number(selected.get('opacity') ?? 1)" type="range" min="0.05" max="1" step="0.05" class="mt-2 w-full" @input="updateSelected('opacity', Number(($event.target as HTMLInputElement).value))" /></label>
                    <label class="block text-sm">Rotation<input :value="Number(selected.get('angle') ?? 0)" type="range" min="-180" max="180" class="mt-2 w-full" @input="updateSelected('angle', Number(($event.target as HTMLInputElement).value))" /></label>
                    <div class="grid grid-cols-2 gap-2">
                        <Button variant="secondary" @click="duplicateSelected"><Copy class="mr-2 h-4 w-4" />Duplicate</Button>
                        <Button variant="secondary" @click="toggleLock"><component :is="selected.lockMovementX ? Unlock : Lock" class="mr-2 h-4 w-4" />{{ selected.lockMovementX ? 'Unlock' : 'Lock' }}</Button>
                        <Button variant="secondary" @click="moveLayer('up')"><ArrowUp class="mr-2 h-4 w-4" />Forward</Button>
                        <Button variant="secondary" @click="moveLayer('down')"><ArrowDown class="mr-2 h-4 w-4" />Backward</Button>
                    </div>
                    <Button variant="destructive" class="w-full" @click="removeSelected"><Trash2 class="mr-2 h-4 w-4" />Remove element</Button>
                </div>
                <p v-else class="mt-5 text-sm text-stone-400">Select an element to edit it.</p>

                <div class="mt-8 border-t border-white/10 pt-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold"><Download class="h-4 w-4" />Download design</h3><p v-if="exportStatus" class="mt-2 text-xs text-sky-300">{{ exportStatus }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <label class="text-xs text-stone-400">Format<select v-model="exportFormat" class="mt-1 w-full rounded-md border border-white/10 bg-stone-900 p-2 text-white"><option value="jpeg">JPEG</option><option value="png">PNG</option><option value="webp">WebP</option></select></label>
                        <label class="text-xs text-stone-400">Fit<select v-model="exportFit" class="mt-1 w-full rounded-md border border-white/10 bg-stone-900 p-2 text-white"><option value="contain">Fit inside</option><option value="cover">Crop to fill</option></select></label>
                    </div>
                    <div class="mt-3 space-y-2">
                        <Button v-for="preset in export_presets" :key="preset.name" variant="secondary" class="w-full justify-between" :disabled="exporting" @click="exportDesign(preset.width, preset.height, preset.name)"><span>{{ preset.name }}</span><span class="text-xs text-stone-400">{{ preset.width }}×{{ preset.height }}</span></Button>
                    </div>
                    <div class="mt-4 rounded-lg border border-white/10 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Custom size</p>
                        <div class="mt-2 grid grid-cols-2 gap-2"><input v-model.number="customWidth" type="number" min="320" max="12000" class="rounded-md border border-white/10 bg-white/5 p-2 text-sm" /><input v-model.number="customHeight" type="number" min="320" max="12000" class="rounded-md border border-white/10 bg-white/5 p-2 text-sm" /></div>
                        <Button class="mt-2 w-full" :disabled="exporting" @click="exportDesign(customWidth, customHeight, 'custom')">{{ exporting ? 'Preparing…' : 'Download custom size' }}</Button>
                    </div>
                    <div v-if="recentExports.length" class="mt-5 border-t border-white/10 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Recent exports</p>
                        <div class="mt-2 space-y-2">
                            <div v-for="record in recentExports" :key="record.uuid" class="rounded-lg border border-white/10 p-3 text-xs">
                                <div class="flex items-start justify-between gap-2"><div><p class="font-medium text-white">{{ record.preset_name || 'Custom' }} · {{ record.width }}×{{ record.height }}</p><p class="mt-1 text-stone-400">{{ record.format }}<span v-if="record.size"> · {{ record.size }}</span> · {{ record.created_at }}</p></div><button class="text-stone-500 hover:text-red-300" title="Delete export" @click="deleteExport(record)"><Trash2 class="h-4 w-4" /></button></div>
                                <a :href="record.download_url" class="mt-2 inline-flex font-medium text-sky-300 hover:text-sky-200">Download again</a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
