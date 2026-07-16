<script setup lang="ts">
import { computed, ref } from 'vue';
import { Crop, ImageIcon, RefreshCw, Trash2 } from '@lucide/vue';

import ImageEditorDialog, {
    type ImageEditData,
} from '@/components/media/ImageEditorDialog.vue';
import { MARKETPLACE_CARD_PRESET } from '@/config/imageEditorPresets';
import { Button } from '@/components/ui/button';

export type AssetPresentationSource = {
    key: string;
    label: string;
    source: File | string;
    sourceAssetFileId?: number | null;
};

const props = withDefaults(
    defineProps<{
        sources: AssetPresentationSource[];
        sourceKey: string | null;
        previewUrl: string | null;
        editData: Partial<ImageEditData> | null;
        title?: string;
        creator?: string | null;
        assetTypeLabel?: string;
        formats?: string[];
        priceLabel?: string;
        disabled?: boolean;
        allowClear?: boolean;
    }>(),
    {
        title: 'Marketplace Asset',
        creator: null,
        assetTypeLabel: 'Asset',
        formats: () => [],
        priceLabel: 'From $0.00',
        disabled: false,
        allowClear: false,
    },
);

const emit = defineEmits<{
    'update:sourceKey': [value: string | null];
    apply: [
        payload: {
            file: File;
            edit: ImageEditData;
            previewUrl: string;
            sourceKey: string;
            sourceAssetFileId: number | null;
        },
    ];
    clear: [];
}>();

const open = ref(false);

const selectedSource = computed(() =>
    props.sources.find((source) => source.key === props.sourceKey),
);

function openEditor(): void {
    if (!selectedSource.value || props.disabled) {
        return;
    }

    open.value = true;
}

function applyEdited(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
}): void {
    if (!selectedSource.value) {
        return;
    }

    emit('apply', {
        ...payload,
        sourceKey: selectedSource.value.key,
        sourceAssetFileId:
            selectedSource.value.sourceAssetFileId ?? null,
    });
}
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium">Source image</label>

                <select
                    :value="sourceKey ?? ''"
                    class="mt-2 h-10 w-full rounded-md border bg-background px-3 text-sm"
                    :disabled="disabled || !sources.length"
                    @change="
                        emit(
                            'update:sourceKey',
                            ($event.target as HTMLSelectElement).value || null,
                        )
                    "
                >
                    <option value="">Choose an uploaded image…</option>
                    <option
                        v-for="source in sources"
                        :key="source.key"
                        :value="source.key"
                    >
                        {{ source.label }}
                    </option>
                </select>

                <p class="mt-2 text-xs leading-5 text-muted-foreground">
                    Choose the image used as the crop source. The generated
                    marketplace image is separate from the primary asset
                    preview and can be re-edited later.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    :disabled="disabled || !selectedSource"
                    @click="openEditor"
                >
                    <Crop class="mr-2 h-4 w-4" />
                    {{ previewUrl ? 'Edit Marketplace Crop' : 'Create Marketplace Crop' }}
                </Button>

                <Button
                    v-if="previewUrl"
                    type="button"
                    variant="outline"
                    :disabled="disabled || !selectedSource"
                    @click="openEditor"
                >
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Re-edit
                </Button>

                <Button
                    v-if="allowClear && previewUrl"
                    type="button"
                    variant="outline"
                    :disabled="disabled"
                    @click="emit('clear')"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Use Automatic Preview
                </Button>
            </div>

            <div
                v-if="!sources.length"
                class="rounded-xl border border-dashed p-5 text-sm text-muted-foreground"
            >
                Add at least one browser-previewable image before creating a
                marketplace crop.
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-background shadow-sm">
            <div class="relative aspect-video overflow-hidden bg-muted">
                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    :alt="title"
                    class="h-full w-full object-cover"
                />

                <div
                    v-else
                    class="flex h-full items-center justify-center text-muted-foreground"
                >
                    <ImageIcon class="h-10 w-10" />
                </div>

                <div class="absolute left-3 top-3 flex gap-2">
                    <span
                        class="rounded-full bg-black/75 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white"
                    >
                        {{ assetTypeLabel }}
                    </span>
                </div>

                <div
                    v-if="formats.length"
                    class="absolute bottom-3 right-3 flex max-w-[85%] flex-wrap justify-end gap-1.5"
                >
                    <span
                        v-for="format in formats.slice(0, 4)"
                        :key="format"
                        class="rounded-full bg-white/90 px-2 py-1 text-[10px] font-medium text-foreground shadow"
                    >
                        {{ format }}
                    </span>
                </div>
            </div>

            <div class="space-y-3 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-primary">
                    Marketplace Preview
                </p>

                <h3 class="line-clamp-2 text-lg font-semibold">
                    {{ title || 'Untitled Asset' }}
                </h3>

                <div class="flex items-end justify-between gap-4">
                    <p class="line-clamp-1 text-sm text-muted-foreground">
                        By {{ creator || 'Unclad Collection' }}
                    </p>

                    <p class="shrink-0 text-sm font-semibold">
                        {{ priceLabel }}
                    </p>
                </div>
            </div>
        </div>

        <ImageEditorDialog
            v-model:open="open"
            :source="selectedSource?.source ?? null"
            :filename="`${title || 'asset'}-marketplace.jpg`"
            :preset="MARKETPLACE_CARD_PRESET"
            :initial-edit="editData"
            @apply="applyEdited"
        />
    </div>
</template>
