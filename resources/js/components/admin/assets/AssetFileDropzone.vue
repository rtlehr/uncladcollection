<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { FilePlus2, GripVertical, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { PendingAssetFile, SelectOption } from '@/types/adminAsset';

const props = defineProps<{
    modelValue: PendingAssetFile[];
    roles: SelectOption[];
    acceptedExtensions: string[];
    maxUploadKilobytes: number;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: PendingAssetFile[]];
}>();

const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

const accept = computed(() => props.acceptedExtensions.map((ext) => `.${ext}`).join(','));

function suggestedRole(file: File): string {
    const name = file.name.toLowerCase();
    const ext = name.split('.').pop() ?? '';
    if (name.includes('preview')) return 'preview';
    if (name.includes('thumb')) return 'thumbnail';
    if (name.includes('poster')) return 'poster';
    if (name.includes('print') || ['tif', 'tiff'].includes(ext)) return 'print';
    if (name.includes('high') || name.includes('4k')) return 'high_resolution';
    if (['eps', 'svg', 'ai'].includes(ext)) return 'vector';
    if (['mp4', 'mov', 'webm'].includes(ext)) return 'video';
    if (ext === 'zip') return 'bundle';
    if (['psd', 'pdf'].includes(ext)) return 'source';
    return 'primary';
}

function addFiles(files: FileList | File[]) {
    const additions = Array.from(files).map((file) => ({
        id: crypto.randomUUID(),
        file,
        role: suggestedRole(file),
        downloadable: true,
        previewUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
    }));
    emit('update:modelValue', [...props.modelValue, ...additions]);
}

function remove(index: number) {
    const next = [...props.modelValue];
    const [removed] = next.splice(index, 1);
    if (removed?.previewUrl) URL.revokeObjectURL(removed.previewUrl);
    emit('update:modelValue', next);
}

function move(index: number, offset: number) {
    const target = index + offset;
    if (target < 0 || target >= props.modelValue.length) return;
    const next = [...props.modelValue];
    [next[index], next[target]] = [next[target], next[index]];
    emit('update:modelValue', next);
}

function onDrop(event: DragEvent) {
    dragging.value = false;
    if (event.dataTransfer?.files?.length) addFiles(event.dataTransfer.files);
}

onBeforeUnmount(() => props.modelValue.forEach((item) => item.previewUrl && URL.revokeObjectURL(item.previewUrl)));
</script>

<template>
    <div class="space-y-4">
        <div
            class="rounded-xl border-2 border-dashed p-8 text-center transition"
            :class="dragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25'"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <FilePlus2 class="mx-auto h-10 w-10 text-muted-foreground" />
            <p class="mt-3 font-medium">Drag files here or choose files</p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ acceptedExtensions.join(', ').toUpperCase() }} · up to {{ Math.round(maxUploadKilobytes / 1024) }} MB each
            </p>
            <Button type="button" variant="outline" class="mt-4" :disabled="disabled" @click="input?.click()">
                Choose Files
            </Button>
            <input ref="input" class="hidden" type="file" multiple :accept="accept" @change="addFiles(($event.target as HTMLInputElement).files ?? [])" />
        </div>

        <div v-for="(item, index) in modelValue" :key="item.id" class="grid gap-3 rounded-lg border p-4 md:grid-cols-[48px_minmax(0,1fr)_180px_auto] md:items-center">
            <div class="flex items-center gap-1">
                <GripVertical class="h-5 w-5 text-muted-foreground" />
                <div class="flex flex-col">
                    <button type="button" class="text-xs" :disabled="index === 0" @click="move(index, -1)">▲</button>
                    <button type="button" class="text-xs" :disabled="index === modelValue.length - 1" @click="move(index, 1)">▼</button>
                </div>
            </div>
            <div class="flex min-w-0 items-center gap-3">
                <img v-if="item.previewUrl" :src="item.previewUrl" class="h-14 w-14 rounded object-cover" alt="" />
                <div class="min-w-0">
                    <div class="truncate font-medium">{{ item.file.name }}</div>
                    <div class="text-xs text-muted-foreground">{{ (item.file.size / 1024 / 1024).toFixed(2) }} MB</div>
                </div>
            </div>
            <select v-model="item.role" class="h-10 rounded-md border bg-background px-3 text-sm">
                <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
            </select>
            <div class="flex items-center justify-end gap-3">
                <label class="flex items-center gap-2 text-sm"><input v-model="item.downloadable" type="checkbox" /> Downloadable</label>
                <Button type="button" size="icon" variant="ghost" @click="remove(index)"><Trash2 class="h-4 w-4" /></Button>
            </div>
        </div>
    </div>
</template>
