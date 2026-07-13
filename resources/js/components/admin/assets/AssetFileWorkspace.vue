<script setup lang="ts">
import { computed, ref } from 'vue';
import { GripVertical, ImageIcon, Save, Trash2, Upload } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
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

const localFiles = ref([...props.files]);
const draggedIndex = ref<number | null>(null);
const selectedIds = ref<number[]>([]);

const selectedFiles = computed(() => localFiles.value.filter((file) => selectedIds.value.includes(file.id)));

function startDrag(index: number): void {
    draggedIndex.value = index;
}

function dropAt(index: number): void {
    if (draggedIndex.value === null || draggedIndex.value === index) return;
    const items = [...localFiles.value];
    const [moved] = items.splice(draggedIndex.value, 1);
    items.splice(index, 0, moved);
    localFiles.value = items;
    draggedIndex.value = null;
    emit('reorder', items);
}

function toggleAll(): void {
    selectedIds.value = selectedIds.value.length === localFiles.value.length
        ? []
        : localFiles.value.map((file) => file.id);
}

function applyDownloadable(value: boolean): void {
    selectedFiles.value.forEach((file) => { file.is_downloadable = value; });
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="files.length" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 p-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" :checked="selectedIds.length === localFiles.length" @change="toggleAll" />
                Select all
            </label>
            <div v-if="selectedIds.length" class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ selectedIds.length }} selected</span>
                <Button type="button" size="sm" variant="outline" @click="applyDownloadable(true)">Mark downloadable</Button>
                <Button type="button" size="sm" variant="outline" @click="applyDownloadable(false)">Mark private</Button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="(file, index) in localFiles"
                :key="file.id"
                draggable="true"
                class="group rounded-xl border bg-background p-4 transition hover:border-primary/40 hover:shadow-sm"
                @dragstart="startDrag(index)"
                @dragover.prevent
                @drop="dropAt(index)"
            >
                <div class="flex items-start gap-3">
                    <label class="pt-1"><input v-model="selectedIds" type="checkbox" :value="file.id" /></label>
                    <GripVertical class="mt-1 h-5 w-5 cursor-grab text-muted-foreground" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ file.original_filename }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ file.extension.toUpperCase() }} · {{ file.media_type }} ·
                                    {{ file.size_bytes ? (file.size_bytes / 1024 / 1024).toFixed(2) + ' MB' : 'Unknown size' }}
                                </p>
                            </div>
                            <div class="flex gap-1.5">
                                <StatusBadge :status="file.processing_status" />
                                <StatusBadge :status="file.virus_scan_status" />
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="text-sm">
                                <span class="mb-1 block text-muted-foreground">Role</span>
                                <select v-model="file.role" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                    <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                                </select>
                            </label>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <label class="flex items-center gap-2"><input v-model="file.is_downloadable" type="checkbox" /> Downloadable</label>
                                <label class="flex items-center gap-2"><input v-model="file.is_active" type="checkbox" /> Active</label>
                                <label class="flex items-center gap-2"><input v-model="file.is_primary_preview" type="checkbox" /> Preview</label>
                                <label class="flex items-center gap-2"><input v-model="file.is_poster" type="checkbox" /> Poster</label>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <Button type="button" size="sm" variant="outline" @click="emit('save', file)"><Save class="mr-2 h-4 w-4" />Save</Button>
                            <label class="inline-flex h-9 cursor-pointer items-center rounded-md border px-3 text-sm">
                                <Upload class="mr-2 h-4 w-4" />{{ replacingId === file.id ? 'Replacing…' : 'Replace' }}
                                <input class="hidden" type="file" :accept="acceptedExtensions.map((ext) => '.' + ext).join(',')" @change="emit('replace', file, $event)" />
                            </label>
                            <Button type="button" size="sm" variant="destructive" @click="emit('remove', file)"><Trash2 class="mr-2 h-4 w-4" />Remove</Button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div v-if="!files.length" class="rounded-xl border border-dashed p-10 text-center text-muted-foreground">
            <ImageIcon class="mx-auto mb-3 h-8 w-8" />
            No active files are attached to this asset.
        </div>
    </div>
</template>
