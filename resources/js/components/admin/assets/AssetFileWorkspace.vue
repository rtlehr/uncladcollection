<script setup lang="ts">
import {
    Archive,
    CheckCircle2,
    File,
    FileImage,
    FileText,
    Film,
    GripVertical,
    ImageIcon,
    Package,
    Save,
    Search,
    Trash2,
    Upload,
    VectorSquare,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { AdminAssetFile, SelectOption } from '@/types/adminAsset';

const props = defineProps<{
    files: AdminAssetFile[];
    roles: SelectOption[];
    replacingId: number | null;
    acceptedExtensions: string[];
}>();

const emit = defineEmits<{
    save: [file: AdminAssetFile];
    remove: [file: AdminAssetFile];
    replace: [file: AdminAssetFile, event: Event];
    reorder: [files: AdminAssetFile[]];
}>();

const localFiles = ref<AdminAssetFile[]>(props.files.map((file) => ({ ...file })));
const draggedIndex = ref<number | null>(null);
const selectedIds = ref<number[]>([]);
const search = ref('');
const activeGroup = ref('all');
const activeState = ref<'all' | 'active' | 'inactive'>('all');

watch(
    () => props.files,
    (files) => {
        localFiles.value = files.map((file) => ({ ...file }));
        selectedIds.value = selectedIds.value.filter((id) => files.some((file) => file.id === id));
    },
    { deep: true },
);

const selectedFiles = computed(() =>
    localFiles.value.filter((file) => selectedIds.value.includes(file.id)),
);

function normalizedRole(file: AdminAssetFile): string {
    return `${file.role} ${file.media_type} ${file.preview_kind}`.toLowerCase();
}

function fileGroup(file: AdminAssetFile): string {
    const value = normalizedRole(file);

    if (value.includes('preview') || file.is_primary_preview || file.is_poster) {
return 'presentation';
}

    if (value.includes('source') || value.includes('original') || value.includes('master')) {
return 'source';
}

    if (value.includes('video') || file.preview_kind === 'video') {
return 'video';
}

    if (value.includes('archive') || ['zip', 'rar', '7z'].includes(file.extension.toLowerCase())) {
return 'package';
}

    if (file.is_downloadable) {
return 'deliverable';
}

    return 'supporting';
}

const groups = computed(() => {
    const definitions = [
        { value: 'all', label: 'All files' },
        { value: 'presentation', label: 'Presentation' },
        { value: 'source', label: 'Source' },
        { value: 'deliverable', label: 'Deliverables' },
        { value: 'video', label: 'Video' },
        { value: 'package', label: 'Packages' },
        { value: 'supporting', label: 'Supporting' },
    ];

    return definitions.map((group) => ({
        ...group,
        count:
            group.value === 'all'
                ? localFiles.value.length
                : localFiles.value.filter((file) => fileGroup(file) === group.value).length,
    }));
});

const filteredFiles = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return localFiles.value.filter((file) => {
        const matchesGroup = activeGroup.value === 'all' || fileGroup(file) === activeGroup.value;
        const matchesState =
            activeState.value === 'all' ||
            (activeState.value === 'active' ? file.is_active : !file.is_active);
        const matchesSearch =
            !needle ||
            file.original_filename.toLowerCase().includes(needle) ||
            file.extension.toLowerCase().includes(needle) ||
            file.role.toLowerCase().includes(needle) ||
            file.media_type.toLowerCase().includes(needle);

        return matchesGroup && matchesState && matchesSearch;
    });
});

const summary = computed(() => ({
    active: localFiles.value.filter((file) => file.is_active).length,
    downloadable: localFiles.value.filter((file) => file.is_active && file.is_downloadable).length,
    previews: localFiles.value.filter((file) => file.can_preview).length,
    totalBytes: localFiles.value.reduce((sum, file) => sum + (file.size_bytes ?? 0), 0),
}));

function startDrag(file: AdminAssetFile): void {
    draggedIndex.value = localFiles.value.findIndex((item) => item.id === file.id);
}

function dropAt(file: AdminAssetFile): void {
    const targetIndex = localFiles.value.findIndex((item) => item.id === file.id);

    if (draggedIndex.value === null || draggedIndex.value === targetIndex) {
return;
}

    const items = [...localFiles.value];
    const [moved] = items.splice(draggedIndex.value, 1);
    items.splice(targetIndex, 0, moved);
    localFiles.value = items;
    draggedIndex.value = null;
    emit('reorder', items);
}

function toggleAllVisible(): void {
    const visibleIds = filteredFiles.value.map((file) => file.id);
    const allVisibleSelected = visibleIds.length > 0 && visibleIds.every((id) => selectedIds.value.includes(id));

    selectedIds.value = allVisibleSelected
        ? selectedIds.value.filter((id) => !visibleIds.includes(id))
        : Array.from(new Set([...selectedIds.value, ...visibleIds]));
}

function applyDownloadable(value: boolean): void {
    selectedFiles.value.forEach((file) => {
        file.is_downloadable = value;
    });
}

function applyActive(value: boolean): void {
    selectedFiles.value.forEach((file) => {
        file.is_active = value;
    });
}

function formatBytes(bytes: number | null): string {
    if (!bytes) {
return 'Unknown size';
}

    if (bytes >= 1024 ** 3) {
return `${(bytes / 1024 ** 3).toFixed(2)} GB`;
}

    if (bytes >= 1024 ** 2) {
return `${(bytes / 1024 ** 2).toFixed(2)} MB`;
}

    if (bytes >= 1024) {
return `${(bytes / 1024).toFixed(1)} KB`;
}

    return `${bytes} B`;
}

function dimensions(file: AdminAssetFile): string | null {
    if (file.width && file.height) {
return `${file.width} × ${file.height}`;
}

    if (file.duration_seconds) {
return `${Number(file.duration_seconds).toFixed(1)} sec`;
}

    return null;
}

function groupLabel(file: AdminAssetFile): string {
    return groups.value.find((group) => group.value === fileGroup(file))?.label ?? 'Supporting';
}

function roleLabel(role: string): string {
    return props.roles.find((option) => option.value === role)?.label ?? role.replaceAll('_', ' ');
}

function iconFor(file: AdminAssetFile) {
    const extension = file.extension.toLowerCase();

    if (file.preview_kind === 'video' || ['mp4', 'mov', 'webm'].includes(extension)) {
return Film;
}

    if (['eps', 'svg', 'ai'].includes(extension)) {
return VectorSquare;
}

    if (['zip', 'rar', '7z'].includes(extension)) {
return Archive;
}

    if (['pdf', 'doc', 'docx', 'txt'].includes(extension)) {
return FileText;
}

    if (file.preview_kind === 'image' || ['jpg', 'jpeg', 'png', 'webp', 'gif', 'tif', 'tiff'].includes(extension)) {
return FileImage;
}

    return File;
}
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-muted/20 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Active files</div>
                <div class="mt-1 text-2xl font-semibold">{{ summary.active }}</div>
            </div>
            <div class="rounded-xl border bg-muted/20 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Deliverables</div>
                <div class="mt-1 text-2xl font-semibold">{{ summary.downloadable }}</div>
            </div>
            <div class="rounded-xl border bg-muted/20 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Browser previews</div>
                <div class="mt-1 text-2xl font-semibold">{{ summary.previews }}</div>
            </div>
            <div class="rounded-xl border bg-muted/20 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total storage</div>
                <div class="mt-1 text-2xl font-semibold">{{ formatBytes(summary.totalBytes) }}</div>
            </div>
        </div>

        <div class="rounded-xl border bg-background">
            <div class="grid gap-3 border-b p-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" placeholder="Search filename, format, role, or media type…" />
                </div>
                <select v-model="activeState" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="all">All states</option>
                    <option value="active">Active only</option>
                    <option value="inactive">Inactive only</option>
                </select>
            </div>

            <div class="flex gap-2 overflow-x-auto border-b px-4 py-3">
                <button
                    v-for="group in groups"
                    :key="group.value"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition"
                    :class="activeGroup === group.value ? 'border-primary bg-primary text-primary-foreground' : 'bg-background hover:bg-muted'"
                    @click="activeGroup = group.value"
                >
                    {{ group.label }}
                    <span class="rounded-full bg-black/10 px-1.5 text-xs dark:bg-white/15">{{ group.count }}</span>
                </button>
            </div>

            <div v-if="filteredFiles.length" class="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/20 p-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" :checked="filteredFiles.every((file) => selectedIds.includes(file.id))" @change="toggleAllVisible" />
                    Select visible
                </label>
                <div v-if="selectedIds.length" class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-muted-foreground">{{ selectedIds.length }} selected</span>
                    <Button type="button" size="sm" variant="outline" @click="applyDownloadable(true)">Downloadable</Button>
                    <Button type="button" size="sm" variant="outline" @click="applyDownloadable(false)">Private</Button>
                    <Button type="button" size="sm" variant="outline" @click="applyActive(true)">Activate</Button>
                    <Button type="button" size="sm" variant="outline" @click="applyActive(false)">Deactivate</Button>
                </div>
            </div>

            <div class="divide-y">
                <article
                    v-for="file in filteredFiles"
                    :key="file.id"
                    draggable="true"
                    class="group p-4 transition hover:bg-muted/20"
                    @dragstart="startDrag(file)"
                    @dragover.prevent
                    @drop="dropAt(file)"
                >
                    <div class="flex items-start gap-3">
                        <label class="pt-2"><input v-model="selectedIds" type="checkbox" :value="file.id" /></label>
                        <GripVertical class="mt-2 h-5 w-5 shrink-0 cursor-grab text-muted-foreground" />
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted/30">
                            <img v-if="file.preview_url && file.preview_kind === 'image'" :src="file.preview_url" :alt="file.original_filename" class="h-full w-full object-cover" />
                            <component :is="iconFor(file)" v-else class="h-5 w-5 text-muted-foreground" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate font-medium">{{ file.original_filename }}</p>
                                        <span class="rounded-full border bg-muted/30 px-2 py-0.5 text-[11px] font-medium uppercase">{{ file.extension }}</span>
                                        <span class="rounded-full border px-2 py-0.5 text-[11px] text-muted-foreground">{{ groupLabel(file) }}</span>
                                    </div>
                                    <p class="mt-1 text-xs capitalize text-muted-foreground">
                                        {{ roleLabel(file.role) }} · {{ formatBytes(file.size_bytes) }}
                                        <template v-if="dimensions(file)"> · {{ dimensions(file) }}</template>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-if="file.is_primary_preview" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary"><ImageIcon class="h-3 w-3" />Primary preview</span>
                                    <span v-if="file.is_downloadable" class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300"><CheckCircle2 class="h-3 w-3" />Deliverable</span>
                                    <StatusBadge :status="file.processing_status" />
                                    <StatusBadge :status="file.virus_scan_status" />
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(180px,260px)_minmax(0,1fr)_auto] lg:items-end">
                                <label class="text-sm">
                                    <span class="mb-1 block text-muted-foreground">File role</span>
                                    <select v-model="file.role" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                        <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                                    </select>
                                </label>

                                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
                                    <label class="flex items-center gap-2"><input v-model="file.is_downloadable" type="checkbox" /> Downloadable</label>
                                    <label class="flex items-center gap-2"><input v-model="file.is_active" type="checkbox" /> Active</label>
                                    <label class="flex items-center gap-2"><input v-model="file.is_primary_preview" type="checkbox" /> Preview</label>
                                    <label class="flex items-center gap-2"><input v-model="file.is_poster" type="checkbox" /> Poster</label>
                                </div>

                                <div class="flex flex-wrap justify-end gap-2">
                                    <Button type="button" size="sm" variant="outline" @click="emit('save', file)"><Save class="mr-2 h-4 w-4" />Save</Button>
                                    <label class="inline-flex h-9 cursor-pointer items-center rounded-md border px-3 text-sm hover:bg-muted">
                                        <Upload class="mr-2 h-4 w-4" />{{ replacingId === file.id ? 'Replacing…' : 'Replace' }}
                                        <input class="hidden" type="file" :accept="acceptedExtensions.map((ext) => '.' + ext).join(',')" @change="emit('replace', file, $event)" />
                                    </label>
                                    <Button type="button" size="sm" variant="destructive" @click="emit('remove', file)"><Trash2 class="mr-2 h-4 w-4" />Remove</Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="!filteredFiles.length" class="p-10 text-center text-muted-foreground">
                <Package class="mx-auto mb-3 h-8 w-8" />
                <p class="font-medium text-foreground">No files match this view</p>
                <p class="mt-1 text-sm">Try another group, state, or search term.</p>
            </div>
        </div>
    </div>
</template>
