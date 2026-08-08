<script setup lang="ts">
import { ImageIcon, Pencil, Trash2, Upload } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';

import ImageEditorDialog from '@/components/media/ImageEditorDialog.vue';
import type {ImageEditData} from '@/components/media/ImageEditorDialog.vue';
import { Button } from '@/components/ui/button';
import { COLLECTION_COVER_PRESET } from '@/config/imageEditorPresets';

const props = withDefaults(
    defineProps<{
        initialImageUrl?: string | null;
        initialOriginalUrl?: string | null;
        initialEditData?: ImageEditData | null;
        error?: string | null;
    }>(),
    {
        initialImageUrl: null,
        initialOriginalUrl: null,
        initialEditData: null,
        error: null,
    },
);

const emit = defineEmits<{
    apply: [payload: {
        original: File | null;
        rendered: File;
        edit: ImageEditData;
    }];
    remove: [];
}>();

const editorOpen = ref(false);
const selectedOriginal = ref<File | null>(null);
const source = ref<File | string | null>(
    props.initialOriginalUrl ?? props.initialImageUrl,
);
const previewUrl = ref<string | null>(props.initialImageUrl);
const editData = ref<ImageEditData | null>(props.initialEditData);
const markedForRemoval = ref(false);
const fileInputKey = ref(0);

const hasImage = computed(() => Boolean(previewUrl.value) && !markedForRemoval.value);
const editorFilename = computed(
    () => selectedOriginal.value?.name ?? 'collection-cover.jpg',
);

function chooseImage(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (! file) {
        return;
    }

    selectedOriginal.value = file;
    source.value = file;
    markedForRemoval.value = false;
    editorOpen.value = true;
}

function applyEdited(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
}): void {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    const rendered = new File(
        [payload.file],
        payload.file.name || 'collection-cover.jpg',
        {
            type: payload.file.type || COLLECTION_COVER_PRESET.outputType,
            lastModified: Date.now(),
        },
    );

    previewUrl.value = payload.previewUrl;
    editData.value = payload.edit;
    markedForRemoval.value = false;

    emit('apply', {
        original: selectedOriginal.value,
        rendered,
        edit: payload.edit,
    });
}

function editCrop(): void {
    source.value = selectedOriginal.value
        ?? props.initialOriginalUrl
        ?? props.initialImageUrl;
    editorOpen.value = true;
}

function removeImage(): void {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = null;
    source.value = null;
    selectedOriginal.value = null;
    editData.value = null;
    markedForRemoval.value = true;
    fileInputKey.value += 1;
    emit('remove');
}

onBeforeUnmount(() => {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <ImageEditorDialog
        v-model:open="editorOpen"
        :source="source"
        :filename="editorFilename"
        :preset="COLLECTION_COVER_PRESET"
        :initial-edit="editData"
        @apply="applyEdited"
    />

    <div class="space-y-4">
        <div
            v-if="hasImage"
            class="overflow-hidden rounded-xl border bg-muted"
        >
            <img
                :src="previewUrl ?? ''"
                alt="Collection cover preview"
                class="aspect-[16/10] w-full object-cover"
            />

            <div class="flex flex-wrap items-center justify-between gap-3 p-3">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <ImageIcon class="h-4 w-4" aria-hidden="true" />
                    {{ COLLECTION_COVER_PRESET.width }} × {{ COLLECTION_COVER_PRESET.height }}
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="editCrop"
                    >
                        <Pencil class="mr-2 h-4 w-4" aria-hidden="true" />
                        Edit crop
                    </Button>

                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="removeImage"
                    >
                        <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" />
                        Remove
                    </Button>
                </div>
            </div>
        </div>

        <label
            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed p-6 text-sm font-medium transition hover:bg-muted/40"
        >
            <Upload class="h-4 w-4" aria-hidden="true" />
            {{ hasImage ? 'Replace cover image' : 'Choose cover image' }}
            <input
                :key="fileInputKey"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                @change="chooseImage"
            />
        </label>

        <p class="text-sm text-muted-foreground">
            The original upload is retained, while the edited 16:10 version is used on collection cards.
        </p>

        <p
            v-if="error"
            class="text-sm text-destructive"
        >
            {{ error }}
        </p>
    </div>
</template>
