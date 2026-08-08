<script setup lang="ts">
import { ImagePlus } from '@lucide/vue';
import { computed, ref } from 'vue';

import ImageEditorDialog from '@/components/media/ImageEditorDialog.vue';
import type {ImageEditData} from '@/components/media/ImageEditorDialog.vue';
import { Button } from '@/components/ui/button';
import { BLOG_CONTENT_PRESETS } from '@/config/imageEditorPresets';
import {
    uploadBlogArticleImage
    
} from '@/lib/blogArticleImageUpload';
import type {UploadedBlogArticleImage} from '@/lib/blogArticleImageUpload';

defineProps<{ disabled?: boolean }>();

const emit = defineEmits<{
    uploaded: [payload: UploadedBlogArticleImage];
}>();

const input = ref<HTMLInputElement | null>(null);
const editorOpen = ref(false);
const source = ref<File | null>(null);
const originalName = ref('');
const uploading = ref(false);
const error = ref<string | null>(null);

const defaultPreset = computed(() => BLOG_CONTENT_PRESETS[0]);

function choose(): void {
    input.value?.click();
}

function selected(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
return;
}

    source.value = file;
    originalName.value = file.name;
    error.value = null;
    editorOpen.value = true;

    if (input.value) {
input.value.value = '';
}
}

async function uploadEdited(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
}): Promise<void> {
    uploading.value = true;
    error.value = null;

    try {
        const alt = originalName.value.replace(/\.[^.]+$/, '');
        const result = await uploadBlogArticleImage(
            payload.file,
            payload.edit,
            alt,
        );

        emit('uploaded', result);
        source.value = null;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Article image upload failed.';
    } finally {
        uploading.value = false;
    }
}

</script>

<template>
    <div>
        <Button
            type="button"
            size="sm"
            variant="outline"
            :disabled="disabled || uploading"
            @click="choose"
        >
            <ImagePlus class="mr-2 h-4 w-4" />
            {{ uploading ? 'Uploading…' : 'Add Edited Image' }}
        </Button>

        <input
            ref="input"
            type="file"
            accept="image/*"
            class="hidden"
            @change="selected"
        />

        <p v-if="error" class="mt-2 text-xs text-destructive">
            {{ error }}
        </p>

        <ImageEditorDialog
            v-model:open="editorOpen"
            :source="source"
            :filename="originalName || 'article-image.jpg'"
            :preset="defaultPreset"
            :presets="BLOG_CONTENT_PRESETS"
            allow-preset-selection
            @apply="uploadEdited"
        />
    </div>
</template>
