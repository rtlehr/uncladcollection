<script setup lang="ts">
import { Crop, ImageIcon, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';

import FormField from '@/Components/Forms/FormField.vue';
import ImageEditorDialog from '@/components/media/ImageEditorDialog.vue';
import type {ImageEditData, ImageEditorPreset} from '@/components/media/ImageEditorDialog.vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(defineProps<{
    id: string;
    label: string;
    description: string;
    preset: ImageEditorPreset;
    currentUrl?: string | null;
    currentOriginalUrl?: string | null;
    initialEdit?: Partial<ImageEditData> | null;
    error?: string;
    previewClass?: string;
    disabled?: boolean;
}>(), {
    currentUrl: null,
    currentOriginalUrl: null,
    initialEdit: null,
    error: undefined,
    previewClass: 'aspect-video w-full',
    disabled: false,
});

const emit = defineEmits<{
    apply: [payload: {
        file: File;
        original: File | null;
        edit: ImageEditData;
        previewUrl: string;
    }];
}>();

const input = ref<HTMLInputElement | null>(null);
const open = ref(false);
const source = ref<File | string | null>(
    props.currentOriginalUrl ?? props.currentUrl,
);
const originalFile = ref<File | null>(null);
const previewUrl = ref<string | null>(props.currentUrl);
const editData = ref<Partial<ImageEditData> | null>(props.initialEdit);

const buttonLabel = computed(() =>
    previewUrl.value ? 'Edit Crop' : 'Choose Image',
);

function choose(): void {
    input.value?.click();
}

function selected(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
return;
}

    originalFile.value = file;
    source.value = file;
    editData.value = null;
    open.value = true;

    if (input.value) {
input.value.value = '';
}
}

function edit(): void {
    if (!source.value && previewUrl.value) {
        source.value = previewUrl.value;
    }

    if (source.value) {
open.value = true;
}
}

function applyEdited(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
}): void {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = payload.previewUrl;
    editData.value = payload.edit;

    emit('apply', {
        ...payload,
        original: originalFile.value,
    });
}
</script>

<template>
    <FormField
        :label="label"
        :for-id="id"
        :description="description"
        :error="error"
    >
        <div
            class="overflow-hidden rounded-xl border bg-muted/20"
            :class="previewClass"
        >
            <img
                v-if="previewUrl"
                :src="previewUrl"
                :alt="label"
                class="h-full w-full object-cover"
            />
            <div
                v-else
                class="flex h-full min-h-32 items-center justify-center text-muted-foreground"
            >
                <ImageIcon class="h-9 w-9" />
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <Button
                type="button"
                variant="outline"
                :disabled="disabled"
                @click="choose"
            >
                <Upload class="mr-2 h-4 w-4" />
                {{ buttonLabel }}
            </Button>

            <Button
                v-if="previewUrl"
                type="button"
                variant="outline"
                :disabled="disabled"
                @click="edit"
            >
                <Crop class="mr-2 h-4 w-4" />
                Edit Crop
            </Button>
        </div>

        <input
            :id="id"
            ref="input"
            type="file"
            accept="image/*"
            class="hidden"
            @change="selected"
        />

        <ImageEditorDialog
            v-model:open="open"
            :source="source"
            :filename="`${id}.jpg`"
            :preset="preset"
            :initial-edit="editData"
            @apply="applyEdited"
        />
    </FormField>
</template>
