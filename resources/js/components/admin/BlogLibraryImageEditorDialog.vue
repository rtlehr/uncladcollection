<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import type { LibraryImage } from '@/components/admin/ImagePickerDialog.vue';
import ImageEditorDialog from '@/components/media/ImageEditorDialog.vue';
import type {ImageEditData} from '@/components/media/ImageEditorDialog.vue';
import { BLOG_CONTENT_PRESETS } from '@/config/imageEditorPresets';
import {
    uploadBlogArticleImage
    
} from '@/lib/blogArticleImageUpload';
import type {UploadedBlogArticleImage} from '@/lib/blogArticleImageUpload';

const props = defineProps<{
    open: boolean;
    image: LibraryImage | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    uploaded: [payload: UploadedBlogArticleImage];
}>();

const uploading = ref(false);
const error = ref<string | null>(null);

const source = computed(
    () =>
        props.image?.high_res_url
        ?? props.image?.thumbnail_url
        ?? props.image?.icon_url
        ?? null,
);

const defaultPreset = computed(() => BLOG_CONTENT_PRESETS[0]);

watch(
    () => props.open,
    (value) => {
        if (value) {
            error.value = null;
        }
    },
);

async function applyEdited(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
}): Promise<void> {
    if (!props.image) {
return;
}

    uploading.value = true;
    error.value = null;

    try {
        const result = await uploadBlogArticleImage(
            payload.file,
            payload.edit,
            props.image.title,
            {
                assetId: props.image.id,
                assetSlug: props.image.slug,
                photographer: props.image.photographer,
                publicUrl: props.image.public_url,
                title: props.image.title,
            },
        );

        emit('uploaded', result);
        emit('update:open', false);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Asset image could not be prepared.';
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <div>
        <p
            v-if="error"
            class="mb-3 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
        >
            {{ error }}
        </p>

        <ImageEditorDialog
            :open="open"
            :source="source"
            :filename="`${image?.slug ?? 'asset'}-article.jpg`"
            :preset="defaultPreset"
            :presets="BLOG_CONTENT_PRESETS"
            allow-preset-selection
            @update:open="emit('update:open', $event)"
            @apply="applyEdited"
        />

        <span v-if="uploading" class="sr-only">
            Uploading edited Asset image
        </span>
    </div>
</template>
