<script setup lang="ts">
import { AlertCircle, ArrowDown, ArrowUp, CheckCircle2, FileArchive, FileImage, FilePlus2, FileText, Film, Trash2, UploadCloud } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import type {
    PendingAssetFile,
    PendingAssetFileMetadata,
    SelectOption,
} from '@/types/adminAsset';

const props = withDefaults(
    defineProps<{
        modelValue: PendingAssetFile[];
        roles: SelectOption[];
        acceptedExtensions: string[];
        maxUploadKilobytes: number;
        disabled?: boolean;
        primaryPreviewIndex?: number | null;
        posterIndex?: number | null;
        allowPrimarySelection?: boolean;
        allowPosterSelection?: boolean;
    }>(),
    {
        disabled: false,
        primaryPreviewIndex: null,
        posterIndex: null,
        allowPrimarySelection: false,
        allowPosterSelection: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: PendingAssetFile[]];
    'update:primaryPreviewIndex': [value: number | null];
    'update:posterIndex': [value: number | null];
}>();

const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);
const processing = ref(false);
const batchErrors = ref<string[]>([]);

const normalizedExtensions = computed(() =>
    props.acceptedExtensions.map((extension) =>
        extension.toLowerCase().replace(/^\./, ''),
    ),
);

const accept = computed(() =>
    normalizedExtensions.value.map((extension) => `.${extension}`).join(','),
);

const totalBytes = computed(() =>
    props.modelValue.reduce((total, item) => total + item.file.size, 0),
);

const invalidCount = computed(
    () =>
        props.modelValue.filter((item) => item.validationErrors.length > 0)
            .length,
);

const readyCount = computed(
    () => props.modelValue.length - invalidCount.value,
);

function extensionOf(filename: string): string {
    return filename.toLowerCase().split('.').pop() ?? '';
}

function fileKind(file: File): PendingAssetFileMetadata['kind'] {
    const extension = extensionOf(file.name);
    const mime = file.type.toLowerCase();

    if (mime.startsWith('image/') && extension !== 'svg') {
        return 'image';
    }

    if (mime.startsWith('video/')) {
        return 'video';
    }

    if (['eps', 'svg', 'ai'].includes(extension)) {
        return 'vector';
    }

    if (['zip', '7z', 'rar'].includes(extension)) {
        return 'archive';
    }

    if (
        mime === 'application/pdf' ||
        ['pdf', 'txt', 'rtf', 'doc', 'docx'].includes(extension)
    ) {
        return 'document';
    }

    return 'other';
}

function suggestedRole(file: File): string {
    const name = file.name.toLowerCase();
    const extension = extensionOf(name);

    if (name.includes('preview')) {
return 'preview';
}

    if (name.includes('thumb')) {
return 'thumbnail';
}

    if (name.includes('icon')) {
return 'icon';
}

    if (name.includes('poster')) {
return 'poster';
}

    if (name.includes('print') || ['tif', 'tiff'].includes(extension)) {
        return 'print';
    }

    if (
        name.includes('high') ||
        name.includes('hi-res') ||
        name.includes('hires') ||
        name.includes('4k')
    ) {
        return 'high_resolution';
    }

    if (['eps', 'svg', 'ai'].includes(extension)) {
return 'vector';
}

    if (['mp4', 'mov', 'webm'].includes(extension)) {
return 'video';
}

    if (['zip', '7z', 'rar'].includes(extension)) {
return 'bundle';
}

    if (['psd', 'pdf'].includes(extension)) {
return 'source';
}

    return 'primary';
}

function formatBytes(bytes: number): string {
    if (!bytes) {
return '0 B';
}

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(
        units.length - 1,
        Math.floor(Math.log(bytes) / Math.log(1024)),
    );

    return `${(bytes / 1024 ** index).toFixed(index > 1 ? 2 : 0)} ${units[index]}`;
}

function formatDuration(seconds: number | null): string | null {
    if (seconds === null) {
return null;
}

    const minutes = Math.floor(seconds / 60);
    const remaining = Math.round(seconds % 60)
        .toString()
        .padStart(2, '0');

    return `${minutes}:${remaining}`;
}

function duplicateKey(file: File): string {
    return `${file.name.toLowerCase()}::${file.size}::${file.lastModified}`;
}

function validationErrors(file: File): string[] {
    const errors: string[] = [];
    const extension = extensionOf(file.name);
    const maximumBytes = props.maxUploadKilobytes * 1024;

    if (!normalizedExtensions.value.includes(extension)) {
        errors.push(`.${extension || 'unknown'} is not an accepted file type.`);
    }

    if (file.size > maximumBytes) {
        errors.push(
            `File exceeds the ${Math.round(props.maxUploadKilobytes / 1024)} MB limit.`,
        );
    }

    if (file.size === 0) {
        errors.push('File is empty.');
    }

    return errors;
}

async function imageDimensions(
    file: File,
): Promise<{ width: number | null; height: number | null }> {
    const url = URL.createObjectURL(file);

    try {
        return await new Promise((resolve) => {
            const image = new Image();

            image.onload = () =>
                resolve({
                    width: image.naturalWidth,
                    height: image.naturalHeight,
                });
            image.onerror = () => resolve({ width: null, height: null });
            image.src = url;
        });
    } finally {
        URL.revokeObjectURL(url);
    }
}

async function videoMetadata(
    file: File,
): Promise<{
    width: number | null;
    height: number | null;
    durationSeconds: number | null;
}> {
    const url = URL.createObjectURL(file);

    try {
        return await new Promise((resolve) => {
            const video = document.createElement('video');
            video.preload = 'metadata';

            video.onloadedmetadata = () =>
                resolve({
                    width: video.videoWidth || null,
                    height: video.videoHeight || null,
                    durationSeconds: Number.isFinite(video.duration)
                        ? video.duration
                        : null,
                });
            video.onerror = () =>
                resolve({
                    width: null,
                    height: null,
                    durationSeconds: null,
                });
            video.src = url;
        });
    } finally {
        URL.revokeObjectURL(url);
    }
}

async function createPendingFile(file: File): Promise<PendingAssetFile> {
    const kind = fileKind(file);
    let width: number | null = null;
    let height: number | null = null;
    let durationSeconds: number | null = null;

    if (kind === 'image') {
        const dimensions = await imageDimensions(file);
        width = dimensions.width;
        height = dimensions.height;
    }

    if (kind === 'video') {
        const video = await videoMetadata(file);
        width = video.width;
        height = video.height;
        durationSeconds = video.durationSeconds;
    }

    return {
        id: crypto.randomUUID(),
        file,
        role: suggestedRole(file),
        downloadable: true,
        previewUrl:
            kind === 'image' ? URL.createObjectURL(file) : null,
        metadata: {
            extension: extensionOf(file.name),
            mimeType: file.type || 'application/octet-stream',
            sizeBytes: file.size,
            width,
            height,
            durationSeconds,
            kind,
        },
        validationErrors: validationErrors(file),
    };
}

async function addFiles(files: FileList | File[]): Promise<void> {
    const incoming = Array.from(files);

    if (!incoming.length || props.disabled || processing.value) {
        return;
    }

    processing.value = true;
    batchErrors.value = [];

    try {
        const existingKeys = new Set(
            props.modelValue.map((item) => duplicateKey(item.file)),
        );
        const additions: PendingAssetFile[] = [];

        for (const file of incoming) {
            const key = duplicateKey(file);

            if (existingKeys.has(key)) {
                batchErrors.value.push(
                    `${file.name} is already in the upload list.`,
                );
                continue;
            }

            existingKeys.add(key);
            additions.push(await createPendingFile(file));
        }

        const next = [...props.modelValue, ...additions];
        emit('update:modelValue', next);

        if (
            props.allowPrimarySelection &&
            props.primaryPreviewIndex === null
        ) {
            const firstPreview = next.findIndex(
                (item) =>
                    item.metadata.kind === 'image' &&
                    item.validationErrors.length === 0,
            );

            if (firstPreview >= 0) {
                emit('update:primaryPreviewIndex', firstPreview);
            }
        }

        if (props.allowPosterSelection && props.posterIndex === null) {
            const firstPoster = next.findIndex(
                (item) =>
                    item.metadata.kind === 'image' &&
                    item.role === 'poster' &&
                    item.validationErrors.length === 0,
            );

            if (firstPoster >= 0) {
                emit('update:posterIndex', firstPoster);
            }
        }
    } finally {
        processing.value = false;

        if (input.value) {
            input.value.value = '';
        }
    }
}

function remove(index: number): void {
    const next = [...props.modelValue];
    const [removed] = next.splice(index, 1);

    if (removed?.previewUrl) {
        URL.revokeObjectURL(removed.previewUrl);
    }

    emit('update:modelValue', next);

    if (props.primaryPreviewIndex === index) {
        emit('update:primaryPreviewIndex', null);
    } else if (
        props.primaryPreviewIndex !== null &&
        props.primaryPreviewIndex > index
    ) {
        emit(
            'update:primaryPreviewIndex',
            props.primaryPreviewIndex - 1,
        );
    }

    if (props.posterIndex === index) {
        emit('update:posterIndex', null);
    } else if (props.posterIndex !== null && props.posterIndex > index) {
        emit('update:posterIndex', props.posterIndex - 1);
    }
}

function move(index: number, offset: number): void {
    const target = index + offset;

    if (target < 0 || target >= props.modelValue.length) {
        return;
    }

    const next = [...props.modelValue];
    [next[index], next[target]] = [next[target], next[index]];
    emit('update:modelValue', next);

    const translateSelectedIndex = (
        selected: number | null,
    ): number | null => {
        if (selected === index) {
return target;
}

        if (selected === target) {
return index;
}

        return selected;
    };

    emit(
        'update:primaryPreviewIndex',
        translateSelectedIndex(props.primaryPreviewIndex),
    );
    emit(
        'update:posterIndex',
        translateSelectedIndex(props.posterIndex),
    );
}

function onDrop(event: DragEvent): void {
    dragging.value = false;

    if (event.dataTransfer?.files?.length) {
        addFiles(event.dataTransfer.files);
    }
}

function iconFor(item: PendingAssetFile) {
    if (item.metadata.kind === 'image') {
return FileImage;
}

    if (item.metadata.kind === 'video') {
return Film;
}

    if (item.metadata.kind === 'archive') {
return FileArchive;
}

    return FileText;
}

function metadataSummary(item: PendingAssetFile): string {
    const details = [formatBytes(item.metadata.sizeBytes)];

    if (item.metadata.width && item.metadata.height) {
        details.push(`${item.metadata.width} × ${item.metadata.height}`);
    }

    const duration = formatDuration(item.metadata.durationSeconds);

    if (duration) {
        details.push(duration);
    }

    details.push(item.metadata.extension.toUpperCase());

    return details.join(' · ');
}

watch(
    () => props.modelValue,
    (files) => {
        if (
            props.primaryPreviewIndex !== null &&
            props.primaryPreviewIndex >= files.length
        ) {
            emit('update:primaryPreviewIndex', null);
        }

        if (
            props.posterIndex !== null &&
            props.posterIndex >= files.length
        ) {
            emit('update:posterIndex', null);
        }
    },
);

onBeforeUnmount(() =>
    props.modelValue.forEach((item) => {
        if (item.previewUrl) {
            URL.revokeObjectURL(item.previewUrl);
        }
    }),
);
</script>

<template>
    <div class="space-y-5">
        <div
            class="rounded-2xl border-2 border-dashed p-8 text-center transition"
            :class="
                dragging
                    ? 'border-primary bg-primary/5'
                    : 'border-muted-foreground/25 bg-muted/10'
            "
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <UploadCloud class="mx-auto h-11 w-11 text-muted-foreground" />

            <p class="mt-3 text-base font-semibold">
                Drag all related asset files here
            </p>

            <p class="mx-auto mt-1 max-w-2xl text-sm text-muted-foreground">
                Upload previews, source files, vectors, videos, print files,
                documents, and downloadable bundles together. File roles and
                metadata are suggested automatically.
            </p>

            <p class="mt-3 text-xs text-muted-foreground">
                {{ acceptedExtensions.join(', ').toUpperCase() }}
                · up to
                {{ Math.round(maxUploadKilobytes / 1024) }} MB each
            </p>

            <Button
                type="button"
                variant="outline"
                class="mt-5"
                :disabled="disabled || processing"
                @click="input?.click()"
            >
                <FilePlus2 class="mr-2 h-4 w-4" />
                {{ processing ? 'Reading files…' : 'Choose Files' }}
            </Button>

            <input
                ref="input"
                class="hidden"
                type="file"
                multiple
                :accept="accept"
                @change="
                    addFiles(
                        ($event.target as HTMLInputElement).files ?? [],
                    )
                "
            />
        </div>

        <div
            v-if="batchErrors.length"
            class="rounded-xl border border-amber-300/50 bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950/20 dark:text-amber-100"
        >
            <div class="flex items-start gap-2">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <p class="font-medium">Some files were skipped</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        <li v-for="error in batchErrors" :key="error">
                            {{ error }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div
            v-if="modelValue.length"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-muted/20 px-4 py-3"
        >
            <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm">
                <span>
                    <strong>{{ modelValue.length }}</strong>
                    file{{ modelValue.length === 1 ? '' : 's' }}
                </span>
                <span>
                    <strong>{{ formatBytes(totalBytes) }}</strong>
                    total
                </span>
                <span class="text-emerald-700 dark:text-emerald-400">
                    <strong>{{ readyCount }}</strong>
                    ready
                </span>
                <span
                    v-if="invalidCount"
                    class="text-destructive"
                >
                    <strong>{{ invalidCount }}</strong>
                    need attention
                </span>
            </div>

            <p class="text-xs text-muted-foreground">
                Use the arrows to control the final file order.
            </p>
        </div>

        <div class="space-y-3">
            <article
                v-for="(item, index) in modelValue"
                :key="item.id"
                class="rounded-xl border bg-background p-4"
                :class="
                    item.validationErrors.length
                        ? 'border-destructive/40'
                        : ''
                "
            >
                <div
                    class="grid gap-4 xl:grid-cols-[72px_minmax(0,1fr)_210px_190px_auto]"
                >
                    <div
                        class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-lg border bg-muted"
                    >
                        <img
                            v-if="item.previewUrl"
                            :src="item.previewUrl"
                            class="h-full w-full object-cover"
                            alt=""
                        />

                        <component
                            :is="iconFor(item)"
                            v-else
                            class="h-7 w-7 text-muted-foreground"
                        />
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-medium">
                                {{ item.file.name }}
                            </p>

                            <span
                                v-if="
                                    primaryPreviewIndex === index
                                "
                                class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                            >
                                Primary preview
                            </span>

                            <span
                                v-if="posterIndex === index"
                                class="rounded-full bg-violet-500/10 px-2 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-300"
                            >
                                Poster
                            </span>
                        </div>

                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ metadataSummary(item) }}
                        </p>

                        <div
                            v-if="item.validationErrors.length"
                            class="mt-2 space-y-1 text-xs text-destructive"
                        >
                            <p
                                v-for="error in item.validationErrors"
                                :key="error"
                                class="flex items-center gap-1"
                            >
                                <AlertCircle class="h-3.5 w-3.5" />
                                {{ error }}
                            </p>
                        </div>

                        <p
                            v-else
                            class="mt-2 flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            Ready to upload
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-muted-foreground">
                            File role
                        </label>

                        <select
                            v-model="item.role"
                            class="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="item.downloadable"
                                type="checkbox"
                            />
                            Downloadable
                        </label>

                        <label
                            v-if="
                                allowPrimarySelection &&
                                item.metadata.kind === 'image'
                            "
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                type="radio"
                                name="asset-primary-preview"
                                :checked="
                                    primaryPreviewIndex === index
                                "
                                @change="
                                    emit(
                                        'update:primaryPreviewIndex',
                                        index,
                                    )
                                "
                            />
                            Primary preview
                        </label>

                        <label
                            v-if="
                                allowPosterSelection &&
                                item.metadata.kind === 'image'
                            "
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                type="radio"
                                name="asset-poster"
                                :checked="posterIndex === index"
                                @change="
                                    emit('update:posterIndex', index)
                                "
                            />
                            Video poster
                        </label>
                    </div>

                    <div class="flex items-start justify-end gap-1">
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            :disabled="index === 0"
                            title="Move up"
                            @click="move(index, -1)"
                        >
                            <ArrowUp class="h-4 w-4" />
                        </Button>

                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            :disabled="
                                index === modelValue.length - 1
                            "
                            title="Move down"
                            @click="move(index, 1)"
                        >
                            <ArrowDown class="h-4 w-4" />
                        </Button>

                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            title="Remove file"
                            @click="remove(index)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
