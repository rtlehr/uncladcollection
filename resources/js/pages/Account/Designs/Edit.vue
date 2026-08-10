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
    LibraryBig,
    Maximize2,
    Search,
    X,
    Layers3,
    Lock,
    Pipette,
    Redo2,
    RotateCcw,
    Save,
    Square,
    Trash2,
    Type,
    Undo2,
    Unlock,
} from '@lucide/vue';
import { Canvas, FabricImage, FabricObject, IText, Rect } from 'fabric';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

// Fabric 7 only guarantees custom properties survive serialization when they
// are registered globally. These values are needed for stable layer rows,
// saved layer names, uploaded-image references, and nondestructive edits.
FabricObject.customProperties = [
    'layerId',
    'name',
    'uploadUuid',
    'originalUploadUuid',
    'originalSrc',
    'backgroundRemoval',
    'lockedByUser',
    'sourceType',
    'sourceAssetId',
    'sourceLicenseId',
    'sourceAssetFileId',
];

type ExportPreset = { name: string; width: number; height: number };
type LibraryAsset = { license_id: number; asset_id: number; title: string; license_name: string | null; licensed_at: string | null; image_count: number; thumbnail_url: string; files_url: string };
type LibraryFile = { id: number; uuid: string; name: string; role: string | null; format: string | null; width: number | null; height: number | null; thumbnail_url: string; image_url: string };
type ExportRecord = {
    uuid: string;
    width: number;
    height: number;
    format: string;
    fit_mode: string;
    preset_name: string | null;
    size: string | null;
    status: string;
    render_engine: string | null;
    error_message: string | null;
    retryable?: boolean;
    created_at: string;
    status_url: string;
    download_url: string | null;
    delete_url: string;
};
type SavedDesign = { version: number; fabric?: Record<string, unknown>; objects?: unknown[]; canvas_background_fit?: 'cover' | 'contain' };
type Limits = {
    max_layer_count: number;
    max_browser_width: number;
    max_browser_height: number;
    max_browser_pixels: number;
    max_server_width: number;
    max_server_height: number;
    max_server_pixels: number;
    recommended_min_width: number;
};
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
    server_export_url: string;
    preview_upload_url: string;
    library_url: string;
    exports: ExportRecord[];
    uploads: { uuid: string; name: string; url: string }[];
}

const props = defineProps<{ project: Project; export_presets: ExportPreset[]; limits: Limits }>();

const canvasElement = ref<HTMLCanvasElement | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const workspaceElement = ref<HTMLElement | null>(null);
const backgroundPreviewCanvas = ref<HTMLCanvasElement | null>(null);
const libraryOpen = ref(false);
const librarySearch = ref('');
const libraryLoading = ref(false);
const libraryAdding = ref<number | null>(null);
const libraryItems = ref<LibraryAsset[]>([]);
const librarySelectedAsset = ref<LibraryAsset | null>(null);
const libraryFiles = ref<LibraryFile[]>([]);
const libraryFilesLoading = ref(false);
const libraryError = ref('');
const canvasSizeOpen = ref(false);
const canvasWidth = ref(props.project.canvas_width);
const canvasHeight = ref(props.project.canvas_height);
const pendingCanvasWidth = ref(props.project.canvas_width);
const pendingCanvasHeight = ref(props.project.canvas_height);
const canvasResizeBehavior = ref<'keep' | 'scale'>('keep');
const canvasBackgroundFit = ref<'cover' | 'contain'>(props.project.design_json?.canvas_background_fit === 'contain' ? 'contain' : 'cover');
const canvasSizeError = ref('');

const title = ref(props.project.title);
const saving = ref(false);
const uploading = ref(false);
const exporting = ref(false);
const selected = ref<FabricObject | null>(null);
const zoom = ref(1);
const savedMessage = ref('');
const exportStatus = ref('');
const recentExports = ref<ExportRecord[]>([...props.project.exports]);
const exportFormat = ref<'jpeg' | 'png' | 'webp'>('jpeg');
const exportFit = ref<'contain' | 'cover'>('contain');
const customWidth = ref(canvasWidth.value);
const customHeight = ref(canvasHeight.value);
const history = ref<string[]>([]);
const historyIndex = ref(-1);
const layerVersion = ref(0);
const dirty = ref(false);
const snapEnabled = ref(true);
const backgroundColor = ref('#ffffff');
const backgroundTolerance = ref(28);
const backgroundFeather = ref(18);
const backgroundBusy = ref(false);
const backgroundStatus = ref('');
const verticalGuides = ref<number[]>([]);
const horizontalGuides = ref<number[]>([]);
const isSmallScreen = ref(window.innerWidth < props.limits.recommended_min_width);

const selectedIsImage = computed(() => selected.value instanceof FabricImage);
const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(() => historyIndex.value >= 0 && historyIndex.value < history.value.length - 1);
const hasBlockingOperation = computed(() => saving.value || uploading.value || exporting.value || backgroundBusy.value || libraryAdding.value !== null);
const processingNotice = computed(() => {
    if (exporting.value) {
return 'An export is currently being prepared or rendered.';
}

    if (uploading.value) {
return 'An image upload is still in progress.';
}

    if (backgroundBusy.value) {
return 'Background removal is still running.';
}

    if (libraryAdding.value !== null) {
return 'A UC Library image is still being added.';
}

    if (saving.value) {
return 'Your design is still being saved.';
}

    return '';
});

let canvas: Canvas | null = null;
let autosaveTimer: ReturnType<typeof setTimeout> | null = null;
let restoringHistory = false;

const studioWebFonts = ['Caveat', 'Dancing Script', 'Patrick Hand'];

async function ensureStudioFontsLoaded(): Promise<void> {
    if (!('fonts' in document)) {
return;
}

    await Promise.all(
        studioWebFonts.map(async fontFamily => {
            try {
                await document.fonts.load(`48px "${fontFamily}"`);
            } catch {
                // The browser will fall back gracefully if the remote font
                // cannot be loaded.
            }
        }),
    );
}

const viewportWidth = ref(Math.max(360, window.innerWidth - 590));
const viewportHeight = ref(Math.max(360, window.innerHeight - 170));
const fitFactor = computed(() => Math.min(
    1,
    viewportWidth.value / canvasWidth.value,
    viewportHeight.value / canvasHeight.value,
));
const stageDimensions = computed(() => ({
    width: Math.max(1, Math.round(canvasWidth.value * fitFactor.value * zoom.value)),
    height: Math.max(1, Math.round(canvasHeight.value * fitFactor.value * zoom.value)),
}));
const layerObjects = computed(() => {
    void layerVersion.value;

    return canvas ? [...canvas.getObjects()].reverse() : [];
});

function measureWorkspace(): void {
    const element = workspaceElement.value;

    if (!element) {
return;
}

    viewportWidth.value = Math.max(320, element.clientWidth - 48);
    viewportHeight.value = Math.max(320, element.clientHeight - 88);
    isSmallScreen.value = window.innerWidth < props.limits.recommended_min_width;
}

function fabricJson(): Record<string, unknown> {
    return canvas?.toJSON(['layerId', 'name', 'uploadUuid', 'originalUploadUuid', 'originalSrc', 'backgroundRemoval', 'lockedByUser', 'sourceType', 'sourceAssetId', 'sourceLicenseId', 'sourceAssetFileId']) as Record<string, unknown> ?? { objects: [] };
}

function snapshot(): string {
    return JSON.stringify({
        fabric: fabricJson(),
        canvasWidth: canvasWidth.value,
        canvasHeight: canvasHeight.value,
        backgroundFit: canvasBackgroundFit.value,
    });
}

function scheduleAutosave(): void {
    if (autosaveTimer) {
clearTimeout(autosaveTimer);
}

    autosaveTimer = setTimeout(() => save(true), 1200);
}

function pushHistory(): void {
    if (!canvas || restoringHistory) {
return;
}

    const current = snapshot();

    if (history.value[historyIndex.value] === current) {
return;
}

    history.value = history.value.slice(0, historyIndex.value + 1);
    history.value.push(current);

    if (history.value.length > 60) {
history.value.shift();
}

    historyIndex.value = history.value.length - 1;
    layerVersion.value++;
    dirty.value = true;
    scheduleAutosave();
}

async function restoreHistory(index: number): Promise<void> {
    if (!canvas || index < 0 || index >= history.value.length) {
return;
}

    restoringHistory = true;
    const restored = JSON.parse(history.value[index]) as {
        fabric?: Record<string, unknown>;
        canvasWidth?: number;
        canvasHeight?: number;
        backgroundFit?: 'cover' | 'contain';
    };
    canvasWidth.value = Math.max(320, Number(restored.canvasWidth ?? canvasWidth.value));
    canvasHeight.value = Math.max(320, Number(restored.canvasHeight ?? canvasHeight.value));
    canvasBackgroundFit.value = restored.backgroundFit === 'contain' ? 'contain' : 'cover';
    canvas.setDimensions({ width: canvasWidth.value, height: canvasHeight.value });
    await canvas.loadFromJSON(restored.fabric ?? restored);
    canvas.backgroundColor = '#ffffff';
    await refitBackground(canvasBackgroundFit.value);
    canvas.requestRenderAll();
    historyIndex.value = index;
    selected.value = null;
    layerVersion.value++;
    restoringHistory = false;
}

function undo(): void {
 void restoreHistory(historyIndex.value - 1); 
}
function redo(): void {
 void restoreHistory(historyIndex.value + 1); 
}

function selectObject(object: FabricObject | null): void {
    selected.value = object;
    backgroundStatus.value = '';
    void nextTick(() => drawBackgroundPreview());
}

function createLayerId(): string {
    return typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
        ? crypto.randomUUID()
        : `layer-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function ensureLayerMetadata(object: FabricObject, index = 0): void {
    if (!object.get('layerId')) {
        object.set('layerId' as never, createLayerId() as never);
    }

    if (!object.get('name')) {
        object.set('name' as never, defaultLayerName(object, index) as never);
    }
}

function layerKey(object: FabricObject): string {
    ensureLayerMetadata(object);

    return String(object.get('layerId'));
}

function defaultLayerName(object: FabricObject | null, index = 0): string {
    if (!object) {
return 'Layer';
}

    if (object.type === 'i-text') {
return `Text ${index + 1}`;
}

    if (object.type === 'rect') {
return `Shape ${index + 1}`;
}

    return `Image ${index + 1}`;
}

function objectLabel(object: FabricObject, index: number): string {
    return String(object.get('name') || defaultLayerName(object, index));
}

function renameLayer(object: FabricObject, value: string): void {
    if (!canvas) {
return;
}

    ensureLayerMetadata(object);
    const trimmed = value.trim();
    object.set('name' as never, (trimmed || defaultLayerName(object)) as never);
    canvas.setActiveObject(object);
    selectObject(object);
    layerVersion.value++;
    canvas.requestRenderAll();
    pushHistory();
}

function save(silent = false): void {
    if (!canvas || saving.value) {
return;
}

    saving.value = true;
    router.put(props.project.save_url, {
        title: title.value,
        canvas_width: canvasWidth.value,
        canvas_height: canvasHeight.value,
        design_json: { version: 2, fabric: fabricJson(), canvas_background_fit: canvasBackgroundFit.value },
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            dirty.value = false;
            void uploadPreview();
            savedMessage.value = silent ? 'Autosaved' : 'Saved';
            window.setTimeout(() => {
 savedMessage.value = ''; 
}, 1800);
        },
        onFinish: () => {
 saving.value = false; 
},
    });
}

async function uploadPreview(): Promise<void> {
    if (!canvas) {
return;
}

    const maxEdge = 720;
    const multiplier = Math.min(1, maxEdge / Math.max(canvasWidth.value, canvasHeight.value));
    const previewCanvas = canvas.toCanvasElement(multiplier, { enableRetinaScaling: false });
    const blob = await new Promise<Blob | null>(resolve => previewCanvas.toBlob(resolve, 'image/webp', 0.82));

    if (!blob) {
return;
}

    const form = new FormData();
    form.append('preview', blob, 'preview.webp');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    await fetch(props.project.preview_upload_url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        body: form,
    }).catch(() => null);
}

function addText(): void {
    if (!canvas || canvas.getObjects().length >= props.limits.max_layer_count) {
return;
}

    const text = new IText('Double-click to edit', {
        left: canvasWidth.value * 0.1,
        top: canvasHeight.value * 0.1,
        width: canvasWidth.value * 0.45,
        fontSize: Math.max(32, Math.round(canvasWidth.value * 0.035)),
        fill: '#ffffff',
        fontFamily: 'Arial',
        fontWeight: '600',
        textAlign: 'left',
        shadow: 'rgba(0,0,0,.35) 0 2px 6px',
        name: 'Text',
    });
    ensureLayerMetadata(text);
    canvas.add(text);
    canvas.setActiveObject(text);
    canvas.requestRenderAll();
    selectObject(text);
    pushHistory();
}

function addShape(): void {
    if (!canvas || canvas.getObjects().length >= props.limits.max_layer_count) {
return;
}

    const shape = new Rect({
        left: canvasWidth.value * 0.15,
        top: canvasHeight.value * 0.15,
        width: canvasWidth.value * 0.28,
        height: canvasHeight.value * 0.16,
        rx: 18,
        ry: 18,
        fill: 'rgba(15,23,42,0.72)',
        stroke: '#ffffff',
        strokeWidth: 0,
        name: 'Rectangle',
    });
    ensureLayerMetadata(shape);
    canvas.add(shape);
    canvas.setActiveObject(shape);
    canvas.requestRenderAll();
    selectObject(shape);
    pushHistory();
}

function imageSource(image: FabricImage, preferOriginal = false): string {
    if (preferOriginal) {
        const original = image.get('originalSrc');

        if (typeof original === 'string' && original) {
return original;
}
    }

    return image.getSrc();
}

async function loadHtmlImage(src: string): Promise<HTMLImageElement> {
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.src = src;

    if (typeof image.decode === 'function') {
        await image.decode();
    } else {
        await new Promise<void>((resolve, reject) => {
            image.onload = () => resolve();
            image.onerror = () => reject(new Error('The selected image could not be loaded.'));
        });
    }

    return image;
}

function hexToRgb(hex: string): [number, number, number] {
    const normalized = hex.replace('#', '').padEnd(6, '0').slice(0, 6);

    return [
        Number.parseInt(normalized.slice(0, 2), 16),
        Number.parseInt(normalized.slice(2, 4), 16),
        Number.parseInt(normalized.slice(4, 6), 16),
    ];
}

function rgbToHex(red: number, green: number, blue: number): string {
    return `#${[red, green, blue].map(value => Math.max(0, Math.min(255, value)).toString(16).padStart(2, '0')).join('')}`;
}

async function drawBackgroundPreview(): Promise<void> {
    const preview = backgroundPreviewCanvas.value;
    const object = selected.value;

    if (!preview || !(object instanceof FabricImage)) {
return;
}

    try {
        const source = await loadHtmlImage(imageSource(object, true));
        const maxWidth = 260;
        const maxHeight = 170;
        const scale = Math.min(maxWidth / source.naturalWidth, maxHeight / source.naturalHeight, 1);
        preview.width = Math.max(1, Math.round(source.naturalWidth * scale));
        preview.height = Math.max(1, Math.round(source.naturalHeight * scale));
        const context = preview.getContext('2d', { willReadFrequently: true });

        if (!context) {
return;
}

        context.clearRect(0, 0, preview.width, preview.height);
        context.drawImage(source, 0, 0, preview.width, preview.height);
    } catch {
        backgroundStatus.value = 'Preview unavailable';
    }
}

function pickBackgroundColor(event: MouseEvent): void {
    const preview = backgroundPreviewCanvas.value;

    if (!preview) {
return;
}

    const rect = preview.getBoundingClientRect();
    const x = Math.max(0, Math.min(preview.width - 1, Math.floor((event.clientX - rect.left) * preview.width / rect.width)));
    const y = Math.max(0, Math.min(preview.height - 1, Math.floor((event.clientY - rect.top) * preview.height / rect.height)));
    const pixel = preview.getContext('2d', { willReadFrequently: true })?.getImageData(x, y, 1, 1).data;

    if (!pixel) {
return;
}

    backgroundColor.value = rgbToHex(pixel[0], pixel[1], pixel[2]);
    backgroundStatus.value = 'Background color selected';
}

async function createTransparentBackgroundBlob(image: FabricImage): Promise<Blob> {
    const source = await loadHtmlImage(imageSource(image, true));
    const maxPixels = 24_000_000;
    const sourcePixels = source.naturalWidth * source.naturalHeight;
    const scale = sourcePixels > maxPixels ? Math.sqrt(maxPixels / sourcePixels) : 1;
    const width = Math.max(1, Math.round(source.naturalWidth * scale));
    const height = Math.max(1, Math.round(source.naturalHeight * scale));
    const output = document.createElement('canvas');
    output.width = width;
    output.height = height;
    const context = output.getContext('2d', { willReadFrequently: true });

    if (!context) {
throw new Error('Image processing is unavailable in this browser.');
}

    context.drawImage(source, 0, 0, width, height);
    const pixels = context.getImageData(0, 0, width, height);
    const [targetRed, targetGreen, targetBlue] = hexToRgb(backgroundColor.value);
    const tolerance = Number(backgroundTolerance.value);
    const feather = Math.max(0, Number(backgroundFeather.value));
    const featherEnd = tolerance + feather;

    for (let offset = 0; offset < pixels.data.length; offset += 4) {
        const red = pixels.data[offset];
        const green = pixels.data[offset + 1];
        const blue = pixels.data[offset + 2];
        const distance = Math.sqrt(((red - targetRed) ** 2) + ((green - targetGreen) ** 2) + ((blue - targetBlue) ** 2));

        if (distance <= tolerance) {
            pixels.data[offset + 3] = 0;
        } else if (feather > 0 && distance < featherEnd) {
            pixels.data[offset + 3] = Math.round(pixels.data[offset + 3] * ((distance - tolerance) / feather));
        }
    }

    context.putImageData(pixels, 0, 0);
    const blob = await new Promise<Blob | null>(resolve => output.toBlob(resolve, 'image/png'));

    if (!blob) {
throw new Error('The transparent PNG could not be created.');
}

    return blob;
}

async function setImageSourcePreservingLayout(image: FabricImage, src: string): Promise<void> {
    const layout = {
        left: image.left,
        top: image.top,
        scaleX: image.scaleX,
        scaleY: image.scaleY,
        angle: image.angle,
        originX: image.originX,
        originY: image.originY,
        flipX: image.flipX,
        flipY: image.flipY,
        opacity: image.opacity,
    };
    await image.setSrc(src, { crossOrigin: 'anonymous' });
    image.set(layout);
    image.setCoords();
    canvas?.requestRenderAll();
}

async function previewBackgroundRemoval(): Promise<void> {
    const image = selected.value;
    const preview = backgroundPreviewCanvas.value;

    if (!(image instanceof FabricImage) || !preview || backgroundBusy.value) {
return;
}

    backgroundBusy.value = true;
    backgroundStatus.value = 'Preparing preview…';

    try {
        const blob = await createTransparentBackgroundBlob(image);
        const url = URL.createObjectURL(blob);

        try {
            const processed = await loadHtmlImage(url);
            const maxWidth = 260;
            const maxHeight = 170;
            const scale = Math.min(maxWidth / processed.naturalWidth, maxHeight / processed.naturalHeight, 1);
            preview.width = Math.max(1, Math.round(processed.naturalWidth * scale));
            preview.height = Math.max(1, Math.round(processed.naturalHeight * scale));
            const context = preview.getContext('2d', { willReadFrequently: true });
            context?.clearRect(0, 0, preview.width, preview.height);
            context?.drawImage(processed, 0, 0, preview.width, preview.height);
        } finally {
            URL.revokeObjectURL(url);
        }

        backgroundStatus.value = 'Preview shown — apply it to save';
    } catch (error) {
        backgroundStatus.value = '';
        alert(error instanceof Error ? error.message : 'The background could not be removed.');
    } finally {
        backgroundBusy.value = false;
    }
}

async function applyBackgroundRemoval(): Promise<void> {
    const image = selected.value;

    if (!(image instanceof FabricImage) || backgroundBusy.value) {
return;
}

    backgroundBusy.value = true;
    backgroundStatus.value = 'Removing background…';

    try {
        const originalSrc = image.get('originalSrc') || imageSource(image);
        const originalUploadUuid = image.get('originalUploadUuid') || image.get('uploadUuid');
        const blob = await createTransparentBackgroundBlob(image);
        const fileName = `${String(image.get('name') || 'image').replace(/\.[^.]+$/, '')}-transparent.png`;
        const form = new FormData();
        form.append('image', new File([blob], fileName, { type: 'image/png' }));
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(props.project.upload_url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: form,
        });

        if (!response.ok) {
throw new Error('The transparent image could not be saved.');
}

        const uploaded = await response.json() as { url: string; uuid: string; name: string };
        image.set({
            originalSrc,
            originalUploadUuid,
            uploadUuid: uploaded.uuid,
            backgroundRemoval: {
                color: backgroundColor.value,
                tolerance: backgroundTolerance.value,
                feather: backgroundFeather.value,
            },
        });
        await setImageSourcePreservingLayout(image, uploaded.url);
        backgroundStatus.value = 'Background removed';
        pushHistory();
        void drawBackgroundPreview();
    } catch (error) {
        backgroundStatus.value = '';
        alert(error instanceof Error ? error.message : 'The background could not be removed.');
    } finally {
        backgroundBusy.value = false;
    }
}

async function restoreOriginalImage(): Promise<void> {
    const image = selected.value;

    if (!(image instanceof FabricImage) || backgroundBusy.value) {
return;
}

    const originalSrc = image.get('originalSrc');

    if (typeof originalSrc !== 'string' || !originalSrc) {
return;
}

    backgroundBusy.value = true;

    try {
        await setImageSourcePreservingLayout(image, originalSrc);
        image.set({
            uploadUuid: image.get('originalUploadUuid') || image.get('uploadUuid'),
            originalSrc: undefined,
            originalUploadUuid: undefined,
            backgroundRemoval: undefined,
        });
        backgroundStatus.value = 'Original image restored';
        pushHistory();
        void drawBackgroundPreview();
    } finally {
        backgroundBusy.value = false;
    }
}

async function loadLibrary(): Promise<void> {
    if (libraryLoading.value) {
        return;
    }

    libraryLoading.value = true;
    libraryError.value = '';
    librarySelectedAsset.value = null;
    libraryFiles.value = [];

    try {
        const url = new URL(props.project.library_url, window.location.origin);

        if (librarySearch.value.trim()) {
            url.searchParams.set('search', librarySearch.value.trim());
        }

        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            const payload = await response.json().catch(() => null) as { message?: string } | null;
            throw new Error(payload?.message ?? 'Your licensed asset library could not be loaded.');
        }

        const payload = await response.json() as { items: LibraryAsset[] };
        libraryItems.value = payload.items;
    } catch (error) {
        libraryError.value = error instanceof Error ? error.message : 'Your licensed asset library could not be loaded.';
    } finally {
        libraryLoading.value = false;
    }
}

function openLibrary(): void {
    libraryOpen.value = true;
    librarySelectedAsset.value = null;
    libraryFiles.value = [];
    void loadLibrary();
}

function closeLibrary(): void {
    if (libraryAdding.value !== null) {
        return;
    }

    libraryOpen.value = false;
    librarySelectedAsset.value = null;
    libraryFiles.value = [];
}

async function openLibraryAsset(item: LibraryAsset): Promise<void> {
    if (libraryFilesLoading.value) {
        return;
    }

    librarySelectedAsset.value = item;
    libraryFiles.value = [];
    libraryFilesLoading.value = true;
    libraryError.value = '';

    try {
        const response = await fetch(item.files_url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            const payload = await response.json().catch(() => null) as { message?: string } | null;
            throw new Error(payload?.message ?? 'The images in this purchased asset could not be loaded.');
        }

        const payload = await response.json() as { files: LibraryFile[] };
        libraryFiles.value = payload.files;
    } catch (error) {
        libraryError.value = error instanceof Error ? error.message : 'The images in this purchased asset could not be loaded.';
    } finally {
        libraryFilesLoading.value = false;
    }
}

function backToLibraryAssets(): void {
    if (libraryAdding.value !== null) {
        return;
    }

    librarySelectedAsset.value = null;
    libraryFiles.value = [];
    libraryError.value = '';
}

async function addLibraryImage(file: LibraryFile): Promise<void> {
    const asset = librarySelectedAsset.value;
    if (!canvas || !asset || libraryAdding.value !== null) {
        return;
    }

    if (canvas.getObjects().length >= props.limits.max_layer_count) {
        alert(`This design already has the maximum of ${props.limits.max_layer_count} layers.`);
        return;
    }

    libraryAdding.value = file.id;

    try {
        const image = await FabricImage.fromURL(file.image_url, { crossOrigin: 'anonymous' });
        const maxWidth = canvasWidth.value * 0.5;
        const maxHeight = canvasHeight.value * 0.5;
        image.scale(Math.min(maxWidth / (image.width || 1), maxHeight / (image.height || 1), 1));
        image.set({
            left: canvasWidth.value * 0.15,
            top: canvasHeight.value * 0.15,
            name: file.name || asset.title,
            layerId: createLayerId(),
            sourceType: 'licensed_asset',
            sourceAssetId: asset.asset_id,
            sourceLicenseId: asset.license_id,
            sourceAssetFileId: file.id,
        });
        canvas.add(image);
        canvas.setActiveObject(image);
        canvas.requestRenderAll();
        selectObject(image);
        pushHistory();
        libraryOpen.value = false;
        librarySelectedAsset.value = null;
        libraryFiles.value = [];
    } catch (error) {
        alert(error instanceof Error ? error.message : 'The licensed image could not be added to this design.');
    } finally {
        libraryAdding.value = null;
    }
}

async function upload(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file || !canvas) {
return;
}

    if (canvas.getObjects().length >= props.limits.max_layer_count) {
        alert(`This design already has the maximum of ${props.limits.max_layer_count} layers.`);
        input.value = '';

        return;
    }

    uploading.value = true;

    try {
        const form = new FormData();
        form.append('image', file);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(props.project.upload_url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: form,
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null) as { message?: string } | null;

            throw new Error(payload?.message ?? 'The image could not be uploaded.');
        }

        const data = await response.json() as { url: string; uuid: string; name: string };
        const image = await FabricImage.fromURL(data.url, { crossOrigin: 'anonymous' });
        const maxWidth = canvasWidth.value * 0.45;
        const maxHeight = canvasHeight.value * 0.45;
        image.scale(Math.min(maxWidth / (image.width || 1), maxHeight / (image.height || 1), 1));
        image.set({
            left: canvasWidth.value * 0.15,
            top: canvasHeight.value * 0.15,
            name: data.name,
            uploadUuid: data.uuid,
        });
        canvas.add(image);
        canvas.setActiveObject(image);
        canvas.requestRenderAll();
        selectObject(image);
        pushHistory();
    } catch (error) {
        alert(error instanceof Error ? error.message : 'The image could not be uploaded.');
    } finally {
        uploading.value = false;
        input.value = '';
    }
}

function removeSelected(): void {
    if (!canvas) {
return;
}

    const activeObjects = canvas.getActiveObjects();
    const objects = activeObjects.length ? activeObjects : (selected.value ? [selected.value] : []);

    if (!objects.length) {
return;
}

    canvas.discardActiveObject();
    objects.forEach(object => canvas?.remove(object));
    selected.value = null;
    layerVersion.value++;
    canvas.requestRenderAll();
    pushHistory();
}

function duplicateSelected(): void {
    if (!canvas || !selected.value || canvas.getObjects().length >= props.limits.max_layer_count) {
return;
}

    selected.value.clone().then((copy: FabricObject) => {
        copy.set({
            left: (selected.value?.left ?? 0) + 30,
            top: (selected.value?.top ?? 0) + 30,
            layerId: createLayerId(),
            name: `${String(selected.value?.get('name') || objectLabel(selected.value!, 0))} copy`,
        });
        canvas?.add(copy);
        canvas?.setActiveObject(copy);
        canvas?.requestRenderAll();
        selectObject(copy);
        pushHistory();
    });
}

function finishLayerMove(object: FabricObject, moved: boolean): void {
    if (!canvas || !moved) {
return;
}

    canvas.setActiveObject(object);
    selectObject(object);
    layerVersion.value++;
    canvas.requestRenderAll();
    pushHistory();
}

function moveLayer(direction: 'forward' | 'backward'): void {
    if (!canvas || !selected.value) {
return;
}

    const object = selected.value;
    const moved = direction === 'forward'
        ? canvas.bringObjectForward(object)
        : canvas.sendObjectBackwards(object);
    finishLayerMove(object, moved);
}

function moveLayerToEdge(direction: 'front' | 'back'): void {
    if (!canvas || !selected.value) {
return;
}

    const object = selected.value;
    const moved = direction === 'front'
        ? canvas.bringObjectToFront(object)
        : canvas.sendObjectToBack(object);
    finishLayerMove(object, moved);
}

function moveSpecificLayer(object: FabricObject, direction: 'forward' | 'backward'): void {
    if (!canvas) {
return;
}

    canvas.setActiveObject(object);
    selectObject(object);
    const moved = direction === 'forward'
        ? canvas.bringObjectForward(object)
        : canvas.sendObjectBackwards(object);
    finishLayerMove(object, moved);
}

function toggleLock(): void {
    if (!selected.value || !canvas) {
return;
}

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

function flipSelected(axis: 'horizontal' | 'vertical'): void {
    if (!selected.value || !canvas) {
return;
}

    if (axis === 'horizontal') {
        selected.value.set('flipX', !selected.value.flipX);
    } else {
        selected.value.set('flipY', !selected.value.flipY);
    }

    selected.value.setCoords();
    canvas.requestRenderAll();
    pushHistory();
}

async function updateSelectedFont(fontFamily: string): Promise<void> {
    if (!selected.value || !canvas) {
return;
}

    if ('fonts' in document) {
        try {
            await document.fonts.load(`48px "${fontFamily}"`);
        } catch {
            // Keep the selection usable even if the web font cannot load.
        }
    }

    selected.value.set('fontFamily' as never, fontFamily as never);
    selected.value.setCoords();
    canvas.requestRenderAll();
    pushHistory();
}

function updateSelected(property: string, value: unknown): void {
    if (!selected.value || !canvas) {
return;
}

    selected.value.set(property as never, value as never);
    selected.value.setCoords();
    canvas.requestRenderAll();
    pushHistory();
}

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
    if (!canvas || !snapEnabled.value || object.lockMovementX) {
return;
}

    const threshold = Math.max(4, 10 / zoom.value);
    const rect = object.getBoundingRect();
    const currentCanvasWidth = canvasWidth.value;
    const currentCanvasHeight = canvasHeight.value;
    const xTargets = [0, currentCanvasWidth / 2, currentCanvasWidth];
    const yTargets = [0, currentCanvasHeight / 2, currentCanvasHeight];
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

        if (xGuide !== null) {
break;
}
    }

    for (const target of yTargets) {
        for (const point of objectY) {
            if (Math.abs(target - point) <= threshold) {
                dy = target - point;
                yGuide = target;
                break;
            }
        }

        if (yGuide !== null) {
break;
}
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
    if (!canvas || (!verticalGuides.value.length && !horizontalGuides.value.length)) {
return;
}

    const context = canvas.getContext();
    context.save();
    context.strokeStyle = '#38bdf8';
    context.lineWidth = 2;
    context.setLineDash([10, 8]);

    for (const x of verticalGuides.value) {
        context.beginPath();
        context.moveTo(x, 0);
        context.lineTo(x, canvasHeight.value);
        context.stroke();
    }

    for (const y of horizontalGuides.value) {
        context.beginPath();
        context.moveTo(0, y);
        context.lineTo(canvasWidth.value, y);
        context.stroke();
    }

    context.restore();
}

function normalizeFormat(value: string): 'jpeg' | 'png' | 'webp' {
    const lowered = value.toLowerCase();

    if (lowered === 'jpg') {
return 'jpeg';
}

    if (lowered === 'png') {
return 'png';
}

    if (lowered === 'webp') {
return 'webp';
}

    return 'jpeg';
}

function validateRequestedSize(width: number, height: number, mode: 'browser' | 'server'): boolean {
    if (!Number.isFinite(width) || !Number.isFinite(height) || width < 320 || height < 320) {
        alert('Please enter a valid width and height of at least 320 pixels.');

        return false;
    }

    const maxWidth = mode === 'browser' ? props.limits.max_browser_width : props.limits.max_server_width;
    const maxHeight = mode === 'browser' ? props.limits.max_browser_height : props.limits.max_server_height;
    const maxPixels = mode === 'browser' ? props.limits.max_browser_pixels : props.limits.max_server_pixels;

    if (width > maxWidth || height > maxHeight) {
        alert(`The requested ${mode} export exceeds the maximum allowed size of ${maxWidth}×${maxHeight}.`);

        return false;
    }

    if ((width * height) > maxPixels) {
        alert(`The requested ${mode} export exceeds the maximum allowed pixel count.`);

        return false;
    }

    return true;
}

function openCanvasSize(): void {
    pendingCanvasWidth.value = canvasWidth.value;
    pendingCanvasHeight.value = canvasHeight.value;
    canvasResizeBehavior.value = 'keep';
    canvasBackgroundFit.value = 'cover';
    canvasSizeError.value = '';
    canvasSizeOpen.value = true;
}

function applyCanvasPreset(preset: ExportPreset): void {
    pendingCanvasWidth.value = preset.width;
    pendingCanvasHeight.value = preset.height;
}

async function refitBackground(mode: 'cover' | 'contain'): Promise<void> {
    if (!canvas || !(canvas.backgroundImage instanceof FabricImage)) {
return;
}

    const background = canvas.backgroundImage;
    const element = background.getElement() as HTMLImageElement;

    if (typeof element.decode === 'function') {
        try {
 await element.decode(); 
} catch { /* already usable */ }
    }

    const sourceWidth = Math.max(1, element.naturalWidth || element.width || background.width || 1);
    const sourceHeight = Math.max(1, element.naturalHeight || element.height || background.height || 1);
    const scale = mode === 'cover'
        ? Math.max(canvasWidth.value / sourceWidth, canvasHeight.value / sourceHeight)
        : Math.min(canvasWidth.value / sourceWidth, canvasHeight.value / sourceHeight);
    const renderedWidth = sourceWidth * scale;
    const renderedHeight = sourceHeight * scale;
    background.set({
        width: sourceWidth,
        height: sourceHeight,
        originX: 'left',
        originY: 'top',
        left: (canvasWidth.value - renderedWidth) / 2,
        top: (canvasHeight.value - renderedHeight) / 2,
        scaleX: scale,
        scaleY: scale,
    });
    background.setCoords();
}

async function changeCanvasSize(): Promise<void> {
    if (!canvas) {
return;
}

    const nextWidth = Math.round(Number(pendingCanvasWidth.value));
    const nextHeight = Math.round(Number(pendingCanvasHeight.value));
    const maxWidth = props.limits.max_server_width;
    const maxHeight = props.limits.max_server_height;
    const maxPixels = props.limits.max_server_pixels;

    canvasSizeError.value = '';

    if (!Number.isFinite(nextWidth) || !Number.isFinite(nextHeight) || nextWidth < 320 || nextHeight < 320) {
        canvasSizeError.value = 'Canvas dimensions must be at least 320×320 pixels.';

        return;
    }

    if (nextWidth > maxWidth || nextHeight > maxHeight || (nextWidth * nextHeight) > maxPixels) {
        canvasSizeError.value = `Canvas size exceeds the allowed limit of ${maxWidth}×${maxHeight} and ${maxPixels.toLocaleString()} total pixels.`;

        return;
    }

    if (nextWidth === canvasWidth.value && nextHeight === canvasHeight.value) {
        canvasSizeOpen.value = false;

        return;
    }

    const oldWidth = canvasWidth.value;
    const oldHeight = canvasHeight.value;
    const oldRatio = oldWidth / oldHeight;
    const nextRatio = nextWidth / nextHeight;

    if (Math.abs(Math.log(nextRatio / oldRatio)) > 0.35) {
        const proceed = confirm('This changes the canvas aspect ratio significantly. Some layers may need to be repositioned. Continue?');

        if (!proceed) {
return;
}
    }

    if (canvasResizeBehavior.value === 'scale') {
        const scale = Math.min(nextWidth / oldWidth, nextHeight / oldHeight);
        const offsetX = (nextWidth - (oldWidth * scale)) / 2;
        const offsetY = (nextHeight - (oldHeight * scale)) / 2;

        for (const object of canvas.getObjects()) {
            object.set({
                left: ((object.left ?? 0) * scale) + offsetX,
                top: ((object.top ?? 0) * scale) + offsetY,
                scaleX: (object.scaleX ?? 1) * scale,
                scaleY: (object.scaleY ?? 1) * scale,
            });
            object.setCoords();
        }
    }

    canvasWidth.value = nextWidth;
    canvasHeight.value = nextHeight;
    customWidth.value = nextWidth;
    customHeight.value = nextHeight;
    canvas.setDimensions({ width: nextWidth, height: nextHeight });
    canvas.set('backgroundColor', '#ffffff');
    await refitBackground(canvasBackgroundFit.value);
    clearGuides();
    measureWorkspace();
    canvas.requestRenderAll();
    canvasSizeOpen.value = false;
    pushHistory();
}

function leaveDesign(event: MouseEvent): void {
    const messages: string[] = [];

    if (dirty.value) {
messages.push('You have unsaved changes.');
}

    if (hasBlockingOperation.value && processingNotice.value) {
messages.push(processingNotice.value);
}

    if (!messages.length) {
return;
}

    if (!confirm(`${messages.join(' ')} Leave the editor anyway?`)) {
        event.preventDefault();
    }
}

function beforeUnload(event: BeforeUnloadEvent): void {
    if (!dirty.value && !hasBlockingOperation.value) {
return;
}

    event.preventDefault();
    event.returnValue = '';
}

function downloadWithoutLeavingEditor(url: string): void {
    const frame = document.createElement('iframe');
    frame.style.display = 'none';
    frame.setAttribute('aria-hidden', 'true');
    frame.src = url;
    document.body.appendChild(frame);
    window.setTimeout(() => frame.remove(), 60_000);
}

async function exportDesign(width: number, height: number, name: string): Promise<void> {
    if (!canvas || exporting.value) {
return;
}

    if (!validateRequestedSize(width, height, 'browser')) {
return;
}

    exporting.value = true;

    try {
        canvas.discardActiveObject();
        canvas.requestRenderAll();
        const multiplier = Math.max(width / canvasWidth.value, height / canvasHeight.value);
        const source = canvas.toCanvasElement(multiplier, { enableRetinaScaling: false });
        const output = document.createElement('canvas');
        output.width = width;
        output.height = height;
        const context = output.getContext('2d');

        if (!context) {
throw new Error('Canvas export is unavailable.');
}

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

        if (!blob) {
throw new Error('The browser could not create the download.');
}

        exportStatus.value = 'Saving export…';
        const form = new FormData();
        form.append('file', blob, `${name}.${exportFormat.value === 'jpeg' ? 'jpg' : exportFormat.value}`);
        form.append('width', String(width));
        form.append('height', String(height));
        form.append('format', exportFormat.value === 'jpeg' ? 'jpg' : exportFormat.value);
        form.append('fit_mode', exportFit.value);
        form.append('preset_name', name);
        form.append('design_json', JSON.stringify({ version: 2, fabric: fabricJson(), canvas_background_fit: canvasBackgroundFit.value }));
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

        if (record.download_url) {
            downloadWithoutLeavingEditor(record.download_url);
        }
    } catch (error) {
        exportStatus.value = 'Export failed';
        alert(error instanceof Error ? error.message : 'The design could not be exported.');
    } finally {
        exporting.value = false;
        window.setTimeout(() => {
 exportStatus.value = ''; 
}, 2200);
    }
}

async function queueServerExport(width: number, height: number, name: string, format: 'jpeg' | 'png' | 'webp' = exportFormat.value, fitMode: 'contain' | 'cover' = exportFit.value): Promise<void> {
    if (!canvas || exporting.value) {
return;
}

    if (!validateRequestedSize(width, height, 'server')) {
return;
}

    exporting.value = true;
    exportStatus.value = 'Preparing full-resolution overlay…';
    const background = canvas.backgroundImage;

    try {
        canvas.discardActiveObject();
        canvas.backgroundImage = undefined;
        canvas.requestRenderAll();

        const multiplier = Math.max(width / canvasWidth.value, height / canvasHeight.value);
        const source = canvas.toCanvasElement(multiplier, { enableRetinaScaling: false });
        const overlay = document.createElement('canvas');
        overlay.width = width;
        overlay.height = height;
        const context = overlay.getContext('2d');

        if (!context) {
throw new Error('Canvas rendering is unavailable.');
}

        const scale = fitMode === 'cover'
            ? Math.max(width / source.width, height / source.height)
            : Math.min(width / source.width, height / source.height);
        const drawWidth = source.width * scale;
        const drawHeight = source.height * scale;
        context.drawImage(source, (width - drawWidth) / 2, (height - drawHeight) / 2, drawWidth, drawHeight);

        canvas.backgroundImage = background;
        canvas.requestRenderAll();

        const overlayBlob = await new Promise<Blob | null>(resolve => overlay.toBlob(resolve, 'image/png'));

        if (!overlayBlob) {
throw new Error('The editor could not prepare the server-render overlay.');
}

        exportStatus.value = 'Queueing full-resolution render…';
        const form = new FormData();
        form.append('overlay', overlayBlob, 'overlay.png');
        form.append('width', String(width));
        form.append('height', String(height));
        form.append('format', format === 'jpeg' ? 'jpg' : format);
        form.append('fit_mode', fitMode);
        form.append('preset_name', name);
        form.append('design_json', JSON.stringify({ version: 2, fabric: fabricJson(), canvas_background_fit: canvasBackgroundFit.value }));
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(props.project.server_export_url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: form,
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null) as { message?: string } | null;

            throw new Error(payload?.message ?? 'The server render could not be queued.');
        }

        const record = await response.json() as ExportRecord;
        recentExports.value = [record, ...recentExports.value.filter(item => item.uuid !== record.uuid)].slice(0, 12);
        await pollServerExport(record);
    } catch (error) {
        canvas.backgroundImage = background;
        canvas.requestRenderAll();
        exportStatus.value = 'Server render failed';
        alert(error instanceof Error ? error.message : 'The server render could not be queued.');
        exporting.value = false;
    }
}

async function pollServerExport(record: ExportRecord): Promise<void> {
    try {
        for (let attempt = 0; attempt < 120; attempt++) {
            await new Promise(resolve => window.setTimeout(resolve, 2500));
            const response = await fetch(record.status_url, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
continue;
}

            const current = await response.json() as ExportRecord;
            recentExports.value = recentExports.value.map(item => item.uuid === current.uuid ? current : item);

            if (current.status === 'completed') {
                exportStatus.value = 'Full-resolution export ready';

                if (current.download_url) {
downloadWithoutLeavingEditor(current.download_url);
}

                window.setTimeout(() => {
 exportStatus.value = ''; 
}, 3000);

                return;
            }

            if (current.status === 'failed') {
                exportStatus.value = 'Server render failed';

                return;
            }
        }

        exportStatus.value = 'Render is still processing. It will remain in Recent exports.';
    } finally {
        exporting.value = false;
    }
}

async function retryServerExport(record: ExportRecord): Promise<void> {
    exportFormat.value = normalizeFormat(record.format);
    exportFit.value = (record.fit_mode === 'cover' ? 'cover' : 'contain');
    await queueServerExport(record.width, record.height, record.preset_name || 'Retry render', normalizeFormat(record.format), exportFit.value);
}

function deleteExport(record: ExportRecord): void {
    if (!confirm(`Delete the ${record.width}×${record.height} ${record.format} export?`)) {
return;
}

    router.delete(record.delete_url, {
        preserveScroll: true,
        onSuccess: () => {
 recentExports.value = recentExports.value.filter(item => item.uuid !== record.uuid); 
},
    });
}

function keyboard(event: KeyboardEvent): void {
    const target = event.target as HTMLElement;

    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
return;
}

    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();

        if (event.shiftKey) {
            redo();
        } else {
            undo();
        }
    } else if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        save();
    } else if (event.key === 'Delete' || event.key === 'Backspace') {
        event.preventDefault();
        removeSelected();
    }
}

onMounted(async () => {
    await nextTick();

    if (!canvasElement.value) {
return;
}

    await ensureStudioFontsLoaded();
    canvas = new Canvas(canvasElement.value, {
        width: canvasWidth.value,
        height: canvasHeight.value,
        preserveObjectStacking: true,
        selectionColor: 'rgba(56,189,248,.14)',
        selectionBorderColor: '#38bdf8',
    });
    canvas.backgroundColor = '#ffffff';

    const saved = props.project.design_json?.fabric;

    if (saved) {
        await canvas.loadFromJSON(saved);
    } else if (Array.isArray(props.project.design_json?.objects)) {
        for (const legacy of props.project.design_json.objects as Array<Record<string, unknown>>) {
            if (legacy.type === 'text') {
                canvas.add(new IText(String(legacy.text ?? ''), {
                    left: Number(legacy.x ?? 0),
                    top: Number(legacy.y ?? 0),
                    fontSize: Number(legacy.fontSize ?? 48),
                    fill: String(legacy.color ?? '#ffffff'),
                    angle: Number(legacy.rotation ?? 0),
                    name: 'Text',
                }));
            } else if (legacy.type === 'image' && legacy.src) {
                const image = await FabricImage.fromURL(String(legacy.src), { crossOrigin: 'anonymous' });
                image.set({ left: Number(legacy.x ?? 0), top: Number(legacy.y ?? 0), angle: Number(legacy.rotation ?? 0), name: 'Image' });
                image.scaleToWidth(Number(legacy.width ?? 400));
                canvas.add(image);
            }
        }
    }

    canvas.set('backgroundColor', '#ffffff');

    canvas.getObjects().forEach((object, index) => ensureLayerMetadata(object, index));

    if (props.project.source_url) {
        const background = await FabricImage.fromURL(props.project.source_url, { crossOrigin: 'anonymous' });
        const element = background.getElement() as HTMLImageElement;

        if (typeof element.decode === 'function') {
            try {
 await element.decode(); 
} catch { /* noop */ }
        }

        const sourceWidth = Math.max(1, element.naturalWidth || element.width || background.width || 1);
        const sourceHeight = Math.max(1, element.naturalHeight || element.height || background.height || 1);
        const scale = canvasBackgroundFit.value === 'cover'
            ? Math.max(canvasWidth.value / sourceWidth, canvasHeight.value / sourceHeight)
            : Math.min(canvasWidth.value / sourceWidth, canvasHeight.value / sourceHeight);
        const renderedWidth = sourceWidth * scale;
        const renderedHeight = sourceHeight * scale;

        background.set({
            width: sourceWidth,
            height: sourceHeight,
            originX: 'left',
            originY: 'top',
            left: (canvasWidth.value - renderedWidth) / 2,
            top: (canvasHeight.value - renderedHeight) / 2,
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
 if (event.target) {
snapObject(event.target);
} 
});
    canvas.on('object:modified', () => {
 clearGuides(); pushHistory(); 
});
    canvas.on('object:added', event => {
        if (event.target) {
ensureLayerMetadata(event.target, canvas?.getObjects().length ?? 0);
}

        layerVersion.value++;
    });
    canvas.on('object:removed', () => {
 layerVersion.value++; 
});
    canvas.on('mouse:up', clearGuides);
    canvas.on('after:render', renderGuides);
    canvas.on('text:changed', () => {
        dirty.value = true;
        scheduleAutosave();
    });

    window.addEventListener('keydown', keyboard);
    window.addEventListener('beforeunload', beforeUnload);
    window.addEventListener('resize', measureWorkspace);
    await nextTick();
    measureWorkspace();
});

onBeforeUnmount(() => {
    if (autosaveTimer) {
clearTimeout(autosaveTimer);
}

    window.removeEventListener('keydown', keyboard);
    window.removeEventListener('beforeunload', beforeUnload);
    window.removeEventListener('resize', measureWorkspace);
    canvas?.dispose();
});

watch(title, () => {
    dirty.value = true;
    scheduleAutosave();
});

watch(stageDimensions, dimensions => {
    if (!canvas) {
return;
}

    canvas.setDimensions(dimensions, { cssOnly: true });
});
</script>

<template>
    <Head :title="title" />
    <div class="flex h-screen flex-col overflow-hidden bg-stone-950 text-white">
        <header class="shrink-0 flex flex-wrap items-center gap-2 border-b border-white/10 px-4 py-3">
            <Button variant="ghost" as-child>
                <Link href="/account/designs" @click="leaveDesign"><ArrowLeft class="mr-2 h-4 w-4" />My Designs</Link>
            </Button>
            <div class="min-w-0 flex-1">
                <input v-model="title" class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-white outline-none" />
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-400">
                    <span>{{ canvasWidth }}×{{ canvasHeight }}</span>
                    <button type="button" class="inline-flex items-center gap-1 text-sky-300 hover:text-sky-200" @click="openCanvasSize"><Maximize2 class="h-3.5 w-3.5" />Change canvas size</button>
                    <span v-if="dirty" class="text-amber-300">Unsaved changes</span>
                    <span v-if="savedMessage" class="text-emerald-300">{{ savedMessage }}</span>
                    <span v-if="processingNotice" class="text-sky-300">{{ processingNotice }}</span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="secondary" :disabled="!canUndo" @click="undo"><Undo2 class="mr-2 h-4 w-4" />Undo</Button>
                <Button variant="secondary" :disabled="!canRedo" @click="redo"><Redo2 class="mr-2 h-4 w-4" />Redo</Button>
                <Button :disabled="saving || hasBlockingOperation" @click="save()"><Save class="mr-2 h-4 w-4" />{{ saving ? 'Saving…' : 'Save' }}</Button>
            </div>
        </header>

        <div v-if="isSmallScreen" class="border-b border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
            The Design Studio works best on a desktop or tablet. It will still work here, but the layout is optimized for wider screens.
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-[260px_minmax(0,1fr)_320px]">
            <aside class="min-h-0 overflow-y-auto border-r border-white/10 p-4">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-stone-400">Tools</h2>
                <div class="mt-4 grid gap-2">
                    <Button variant="secondary" class="justify-start" @click="addText"><Type class="mr-2 h-4 w-4" />Add text</Button>
                    <Button variant="secondary" class="justify-start" @click="addShape"><Square class="mr-2 h-4 w-4" />Add rectangle</Button>
                    <Button variant="secondary" class="justify-start" :disabled="uploading" @click="fileInput?.click()"><ImagePlus class="mr-2 h-4 w-4" />{{ uploading ? 'Uploading…' : 'Upload image' }}</Button>
                    <Button variant="secondary" class="justify-start" @click="openLibrary"><LibraryBig class="mr-2 h-4 w-4" />Add from My Library</Button>
                    <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="upload" />
                </div>

                <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.03] p-3">
                    <label class="flex items-center justify-between text-sm">
                        <span>Snapping guides</span>
                        <input v-model="snapEnabled" type="checkbox" />
                    </label>
                    <p class="mt-2 text-xs text-stone-400">Objects snap to the canvas edges and center while you move them.</p>
                </div>

                <div class="mt-6">
                    <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-stone-400"><Layers3 class="h-4 w-4" />Layers</h3>
                    <div class="mt-3 space-y-1">
                        <div v-for="(object, index) in layerObjects" :key="layerKey(object)" class="rounded-md border border-white/5 p-2" :class="selected === object ? 'bg-sky-500/15' : 'bg-transparent'" @click="activateLayer(object)">
                            <div class="flex items-center gap-2">
                                <input
                                    :value="objectLabel(object, index)"
                                    class="min-w-0 flex-1 rounded border border-white/10 bg-stone-900/80 px-2 py-1.5 text-sm text-stone-200 outline-none focus:border-sky-400"
                                    aria-label="Layer name"
                                    title="Rename layer"
                                    @click.stop
                                    @focus="activateLayer(object)"
                                    @input="renameLayer(object, ($event.target as HTMLInputElement).value)"
                                    @keydown.enter="($event.target as HTMLInputElement).blur()"
                                />
                                <Lock v-if="object.lockMovementX" class="h-3.5 w-3.5 shrink-0 text-stone-400" />
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-1">
                                <button class="rounded border border-white/10 px-2 py-1.5 text-xs text-stone-300 hover:bg-white/5 hover:text-white" title="Bring forward one layer" @click.stop="moveSpecificLayer(object, 'forward')"><ArrowUp class="mr-1 inline h-3.5 w-3.5" />Forward</button>
                                <button class="rounded border border-white/10 px-2 py-1.5 text-xs text-stone-300 hover:bg-white/5 hover:text-white" title="Send backward one layer" @click.stop="moveSpecificLayer(object, 'backward')"><ArrowDown class="mr-1 inline h-3.5 w-3.5" />Backward</button>
                            </div>
                        </div>
                        <p v-if="layerObjects.length === 0" class="py-3 text-xs text-stone-500">Add text or an image to begin.</p>
                    </div>
                </div>
            </aside>

            <main ref="workspaceElement" class="relative min-h-0 overflow-auto p-6 pb-20">
                <div class="mx-auto flex min-h-full items-center justify-center">
                    <div class="shadow-2xl" :style="{ width: `${stageDimensions.width}px`, height: `${stageDimensions.height}px` }">
                        <canvas ref="canvasElement" />
                    </div>
                </div>
                <div class="sticky bottom-0 z-20 mx-auto mt-4 flex max-w-md items-center gap-3 rounded-xl border border-white/10 bg-stone-950/95 px-4 py-3 text-xs text-stone-300 shadow-xl backdrop-blur">
                    <span>Zoom</span>
                    <input v-model.number="zoom" type="range" min="0.35" max="2" step="0.05" class="w-full" />
                    <span class="w-12 text-right">{{ Math.round(zoom * 100) }}%</span>
                    <Button variant="secondary" size="sm" @click="zoom = 1">100%</Button>
                </div>
            </main>

            <aside class="min-h-0 overflow-y-auto border-l border-white/10 p-4">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-stone-400">Properties</h2>
                <div v-if="selected" class="mt-4 space-y-4">
                    <label class="block text-sm">
                        Layer name
                        <input :value="String(selected.get('name') || '')" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" @input="renameLayer(selected, ($event.target as HTMLInputElement).value)" />
                    </label>

                    <template v-if="selected.type === 'i-text'">
                        <label class="block text-sm">Text<textarea :value="String(selected.get('text') ?? '')" rows="3" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" @input="updateSelected('text', ($event.target as HTMLTextAreaElement).value)" /></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-sm">Font size<input :value="Number(selected.get('fontSize') ?? 48)" type="number" min="8" max="500" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" @change="updateSelected('fontSize', Number(($event.target as HTMLInputElement).value))" /></label>
                            <label class="text-sm">Color<input :value="String(selected.get('fill') ?? '#ffffff')" type="color" class="mt-2 h-10 w-full rounded" @input="updateSelected('fill', ($event.target as HTMLInputElement).value)" /></label>
                        </div>
                        <label class="block text-sm">Font family<select :value="String(selected.get('fontFamily') ?? 'Arial')" class="mt-2 w-full rounded-lg border border-white/10 bg-stone-900 p-2" @change="updateSelectedFont(($event.target as HTMLSelectElement).value)"><option>Arial</option><option>Georgia</option><option>Impact</option><option>Caveat</option><option>Dancing Script</option><option>Patrick Hand</option><option>Tahoma</option><option>Times New Roman</option><option>Trebuchet MS</option><option>Verdana</option></select></label>
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

                    <div v-if="selectedIsImage" class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="flex items-center gap-2 text-sm font-semibold"><Pipette class="h-4 w-4" />Remove solid background</h3>
                            <input v-model="backgroundColor" type="color" class="h-8 w-10 cursor-pointer rounded border-0 bg-transparent" title="Background color" />
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-stone-400">Click the background in the preview, then adjust tolerance and edge softness.</p>
                        <canvas ref="backgroundPreviewCanvas" class="mt-3 max-h-44 w-full cursor-crosshair rounded-lg border border-white/10 bg-[linear-gradient(45deg,#2a2a2a_25%,transparent_25%),linear-gradient(-45deg,#2a2a2a_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#2a2a2a_75%),linear-gradient(-45deg,transparent_75%,#2a2a2a_75%)] bg-[length:16px_16px] bg-[position:0_0,0_8px,8px_-8px,-8px_0px] object-contain" title="Click a color to remove" @click="pickBackgroundColor" />
                        <label class="mt-3 block text-xs text-stone-300">Tolerance: {{ backgroundTolerance }}<input v-model.number="backgroundTolerance" type="range" min="0" max="160" step="1" class="mt-1 w-full" /></label>
                        <label class="mt-3 block text-xs text-stone-300">Edge softness: {{ backgroundFeather }}<input v-model.number="backgroundFeather" type="range" min="0" max="100" step="1" class="mt-1 w-full" /></label>
                        <p v-if="backgroundStatus" class="mt-2 text-xs text-sky-300">{{ backgroundStatus }}</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <Button variant="secondary" size="sm" :disabled="backgroundBusy" @click="previewBackgroundRemoval">{{ backgroundBusy ? 'Working…' : 'Preview' }}</Button>
                            <Button size="sm" :disabled="backgroundBusy" @click="applyBackgroundRemoval">Apply</Button>
                        </div>
                        <Button v-if="selected.get('originalSrc')" variant="ghost" size="sm" class="mt-2 w-full" :disabled="backgroundBusy" @click="restoreOriginalImage"><RotateCcw class="mr-2 h-4 w-4" />Restore original</Button>
                    </div>

                    <label class="block text-sm">Opacity<input :value="Number(selected.get('opacity') ?? 1)" type="range" min="0.05" max="1" step="0.05" class="mt-2 w-full" @input="updateSelected('opacity', Number(($event.target as HTMLInputElement).value))" /></label>
                    <label class="block text-sm">Rotation<input :value="Number(selected.get('angle') ?? 0)" type="range" min="-180" max="180" class="mt-2 w-full" @input="updateSelected('angle', Number(($event.target as HTMLInputElement).value))" /></label>
                    <div class="grid grid-cols-2 gap-2">
                        <Button variant="secondary" @click="duplicateSelected"><Copy class="mr-2 h-4 w-4" />Duplicate</Button>
                        <Button variant="secondary" @click="toggleLock"><component :is="selected.lockMovementX ? Unlock : Lock" class="mr-2 h-4 w-4" />{{ selected.lockMovementX ? 'Unlock' : 'Lock' }}</Button>
                        <Button variant="secondary" @click="flipSelected('horizontal')">Flip horizontal</Button>
                        <Button variant="secondary" @click="flipSelected('vertical')">Flip vertical</Button>
                        <Button variant="secondary" @click="moveLayer('forward')"><ArrowUp class="mr-2 h-4 w-4" />Forward</Button>
                        <Button variant="secondary" @click="moveLayer('backward')"><ArrowDown class="mr-2 h-4 w-4" />Backward</Button>
                        <Button variant="secondary" @click="moveLayerToEdge('front')">To front</Button>
                        <Button variant="secondary" @click="moveLayerToEdge('back')">To back</Button>
                    </div>
                    <Button variant="destructive" class="w-full" @click="removeSelected"><Trash2 class="mr-2 h-4 w-4" />Remove element</Button>
                </div>
                <p v-else class="mt-5 text-sm text-stone-400">Select an element to edit it.</p>

                <div class="mt-8 border-t border-white/10 pt-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold"><Download class="h-4 w-4" />Download design</h3>
                    <p v-if="exportStatus" class="mt-2 text-xs text-sky-300">{{ exportStatus }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <label class="text-xs text-stone-400">Format<select v-model="exportFormat" class="mt-1 w-full rounded-md border border-white/10 bg-stone-900 p-2 text-white"><option value="jpeg">JPEG</option><option value="png">PNG</option><option value="webp">WebP</option></select></label>
                        <label class="text-xs text-stone-400">Fit<select v-model="exportFit" class="mt-1 w-full rounded-md border border-white/10 bg-stone-900 p-2 text-white"><option value="contain">Fit inside</option><option value="cover">Crop to fill</option></select></label>
                    </div>
                    <div class="mt-3 space-y-2">
                        <Button v-for="preset in export_presets" :key="preset.name" variant="secondary" class="w-full justify-between" :disabled="exporting" @click="exportDesign(preset.width, preset.height, preset.name)"><span>{{ preset.name }}</span><span class="text-xs text-stone-400">{{ preset.width }}×{{ preset.height }}</span></Button>
                    </div>
                    <div class="mt-4 rounded-lg border border-white/10 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Custom size</p>
                        <div class="mt-2 grid grid-cols-2 gap-2"><input v-model.number="customWidth" type="number" min="320" :max="props.limits.max_server_width" class="rounded-md border border-white/10 bg-white/5 p-2 text-sm" /><input v-model.number="customHeight" type="number" min="320" :max="props.limits.max_server_height" class="rounded-md border border-white/10 bg-white/5 p-2 text-sm" /></div>
                        <Button class="mt-2 w-full" :disabled="exporting" @click="exportDesign(customWidth, customHeight, 'custom')">{{ exporting ? 'Preparing…' : 'Download custom size' }}</Button>
                        <Button variant="secondary" class="mt-2 w-full" :disabled="exporting" @click="queueServerExport(customWidth, customHeight, 'Full resolution')">{{ exporting ? 'Queueing…' : 'Render full resolution on server' }}</Button>
                        <p class="mt-2 text-xs leading-relaxed text-stone-500">Server rendering uses the highest-resolution licensed source and continues through the queue even if you leave this page.</p>
                    </div>
                    <div v-if="recentExports.length" class="mt-5 border-t border-white/10 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Recent exports</p>
                        <div class="mt-2 space-y-2">
                            <div v-for="record in recentExports" :key="record.uuid" class="rounded-lg border border-white/10 p-3 text-xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-white">{{ record.preset_name || 'Custom' }} · {{ record.width }}×{{ record.height }}</p>
                                        <p class="mt-1 text-stone-400">{{ record.format }}<span v-if="record.size"> · {{ record.size }}</span> · {{ record.status === 'completed' ? record.created_at : record.status }}</p>
                                        <p v-if="record.status === 'failed'" class="mt-1 text-red-300">{{ record.error_message || 'The render failed.' }}</p>
                                    </div>
                                    <button class="text-stone-500 hover:text-red-300" title="Delete export" @click="deleteExport(record)"><Trash2 class="h-4 w-4" /></button>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-3">
                                    <button v-if="record.download_url" type="button" class="inline-flex font-medium text-sky-300 hover:text-sky-200" @click="downloadWithoutLeavingEditor(record.download_url)">Download again</button>
                                    <button v-else-if="record.retryable" class="inline-flex font-medium text-amber-300 hover:text-amber-200" :disabled="exporting" @click="retryServerExport(record)">Retry render</button>
                                    <span v-else class="inline-flex text-stone-500">{{ record.status === 'failed' ? 'Render failed' : 'Rendering…' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div v-if="canvasSizeOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4" @click.self="canvasSizeOpen = false">
            <div class="w-full max-w-xl rounded-2xl border border-white/10 bg-stone-950 shadow-2xl">
                <div class="flex items-center justify-between border-b border-white/10 p-4">
                    <div>
                        <h2 class="text-lg font-semibold">Change Canvas Size</h2>
                        <p class="mt-1 text-sm text-stone-400">Changing aspect ratio may require repositioning some layers.</p>
                    </div>
                    <button class="rounded-lg p-2 text-stone-400 hover:bg-white/5 hover:text-white" @click="canvasSizeOpen = false"><X class="h-5 w-5" /></button>
                </div>
                <div class="space-y-5 p-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Presets</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <Button v-for="preset in export_presets" :key="preset.name" type="button" variant="secondary" class="justify-between" @click="applyCanvasPreset(preset)"><span>{{ preset.name }}</span><span class="text-xs text-stone-400">{{ preset.width }}×{{ preset.height }}</span></Button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-sm">Width<input v-model.number="pendingCanvasWidth" type="number" min="320" :max="props.limits.max_server_width" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" /></label>
                        <label class="text-sm">Height<input v-model.number="pendingCanvasHeight" type="number" min="320" :max="props.limits.max_server_height" class="mt-2 w-full rounded-lg border border-white/10 bg-white/5 p-2" /></label>
                    </div>
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wide text-stone-400">Resize behavior</legend>
                        <label class="mt-2 flex gap-3 rounded-lg border border-white/10 p-3 text-sm"><input v-model="canvasResizeBehavior" type="radio" value="keep" /><span><strong>Keep layer sizes and positions</strong><span class="mt-1 block text-xs text-stone-400">Layers remain unchanged and may extend outside the new canvas.</span></span></label>
                        <label class="mt-2 flex gap-3 rounded-lg border border-white/10 p-3 text-sm"><input v-model="canvasResizeBehavior" type="radio" value="scale" /><span><strong>Scale entire design to fit</strong><span class="mt-1 block text-xs text-stone-400">Layers scale proportionally and remain centered.</span></span></label>
                    </fieldset>
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wide text-stone-400">Background</legend>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label class="flex gap-2 rounded-lg border border-white/10 p-3 text-sm"><input v-model="canvasBackgroundFit" type="radio" value="cover" /><span>Crop to fill</span></label>
                            <label class="flex gap-2 rounded-lg border border-white/10 p-3 text-sm"><input v-model="canvasBackgroundFit" type="radio" value="contain" /><span>Fit inside</span></label>
                        </div>
                    </fieldset>
                    <p v-if="canvasSizeError" class="rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-200">{{ canvasSizeError }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-white/10 p-4">
                    <Button type="button" variant="secondary" @click="canvasSizeOpen = false">Cancel</Button>
                    <Button type="button" @click="changeCanvasSize">Change Canvas</Button>
                </div>
            </div>
        </div>

        <div v-if="libraryOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4" @click.self="closeLibrary">
            <div class="flex max-h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-stone-950 shadow-2xl">
                <div class="flex items-center gap-3 border-b border-white/10 p-4">
                    <button v-if="librarySelectedAsset" class="rounded-lg p-2 text-stone-400 hover:bg-white/5 hover:text-white" title="Back to purchased assets" @click="backToLibraryAssets"><ArrowLeft class="h-5 w-5" /></button>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold">{{ librarySelectedAsset ? librarySelectedAsset.title : 'Add from My Library' }}</h2>
                        <p v-if="librarySelectedAsset" class="mt-1 text-sm text-stone-400">
                            Choose one of the {{ librarySelectedAsset.image_count }} licensed images included with this asset.
                        </p>
                        <p v-else class="mt-1 text-sm text-stone-400">Choose a purchased asset, then select the image you want to add.</p>
                    </div>
                    <button class="rounded-lg p-2 text-stone-400 hover:bg-white/5 hover:text-white" @click="closeLibrary"><X class="h-5 w-5" /></button>
                </div>

                <form v-if="!librarySelectedAsset" class="flex gap-2 border-b border-white/10 p-4" @submit.prevent="loadLibrary">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" />
                        <input v-model="librarySearch" class="w-full rounded-lg border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-sm" placeholder="Search purchased assets…" />
                    </div>
                    <Button type="submit" variant="secondary" :disabled="libraryLoading">{{ libraryLoading ? 'Searching…' : 'Search' }}</Button>
                </form>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <p v-if="libraryError" class="rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-200">{{ libraryError }}</p>

                    <template v-if="librarySelectedAsset">
                        <div v-if="libraryFilesLoading" class="py-12 text-center text-sm text-stone-400">Loading images included with this asset…</div>
                        <div v-else-if="libraryFiles.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <article v-for="file in libraryFiles" :key="file.id" class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.03]">
                                <div class="aspect-[4/3] bg-stone-900">
                                    <img :src="file.thumbnail_url" :alt="file.name" class="h-full w-full object-contain" loading="lazy" />
                                </div>
                                <div class="p-3">
                                    <h3 class="truncate text-sm font-medium text-white" :title="file.name">{{ file.name }}</h3>
                                    <p class="mt-1 text-xs text-stone-400">
                                        <span v-if="file.role">{{ file.role }}</span>
                                        <span v-if="file.format"> · {{ file.format }}</span>
                                        <span v-if="file.width && file.height"> · {{ file.width }}×{{ file.height }}</span>
                                    </p>
                                    <Button class="mt-3 w-full" size="sm" :disabled="libraryAdding !== null" @click="addLibraryImage(file)">{{ libraryAdding === file.id ? 'Adding…' : 'Add to design' }}</Button>
                                </div>
                            </article>
                        </div>
                        <div v-else-if="!libraryError" class="py-12 text-center">
                            <ImagePlus class="mx-auto h-9 w-9 text-stone-600" />
                            <p class="mt-3 text-sm text-stone-300">No currently available image files were found in this purchased asset.</p>
                        </div>
                    </template>

                    <template v-else>
                        <div v-if="libraryLoading" class="py-12 text-center text-sm text-stone-400">Loading your purchased assets…</div>
                        <div v-else-if="libraryItems.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <article v-for="item in libraryItems" :key="item.license_id" class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.03]">
                                <div class="aspect-[4/3] bg-stone-900">
                                    <img :src="item.thumbnail_url" :alt="item.title" class="h-full w-full object-cover" loading="lazy" />
                                </div>
                                <div class="p-3">
                                    <h3 class="truncate text-sm font-medium text-white">{{ item.title }}</h3>
                                    <p class="mt-1 text-xs text-stone-400">{{ item.license_name || 'Licensed asset' }}<span v-if="item.licensed_at"> · {{ item.licensed_at }}</span></p>
                                    <p class="mt-2 text-xs font-medium text-sky-300">{{ item.image_count }} {{ item.image_count === 1 ? 'image' : 'images' }} included</p>
                                    <Button class="mt-3 w-full" size="sm" variant="secondary" @click="openLibraryAsset(item)">View images</Button>
                                </div>
                            </article>
                        </div>
                        <div v-else-if="!libraryError" class="py-12 text-center">
                            <LibraryBig class="mx-auto h-9 w-9 text-stone-600" />
                            <p class="mt-3 text-sm text-stone-300">No matching purchased assets with usable images were found.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
