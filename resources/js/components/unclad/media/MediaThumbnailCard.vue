<script setup lang="ts">
import {
    FileArchive,
    FileText,
    Film,
    Image as ImageIcon,
    PenTool,
    Play,
} from '@lucide/vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

defineProps<{ file: MediaPresentationFile; selected: boolean }>();
defineEmits<{ select: [] }>();

function sizeLabel(bytes: number | null): string {
    if (!bytes) {
return 'Unknown size';
}

    if (bytes >= 1024 ** 3) {
return `${(bytes / 1024 ** 3).toFixed(1)} GB`;
}

    if (bytes >= 1024 ** 2) {
return `${(bytes / 1024 ** 2).toFixed(1)} MB`;
}

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function detailLabel(file: MediaPresentationFile): string {
    if (file.width && file.height) {
return `${file.width} × ${file.height}`;
}

    if (file.duration_seconds !== null) {
        const seconds = Number(file.duration_seconds);

        if (Number.isFinite(seconds)) {
return `${Math.floor(seconds / 60)}:${Math.round(seconds % 60)
                .toString()
                .padStart(2, '0')}`;
}
    }

    if (file.page_count) {
return `${file.page_count} pages`;
}

    return sizeLabel(file.size_bytes);
}
</script>

<template>
    <button
        type="button"
        :aria-pressed="selected"
        :class="[
            'group w-44 shrink-0 overflow-hidden rounded-2xl border bg-white text-left shadow-sm transition duration-200 dark:bg-stone-900',
            selected
                ? 'border-[var(--brand-primary)] ring-2 ring-[var(--brand-primary)]/20'
                : 'border-stone-200 hover:-translate-y-0.5 hover:border-stone-400 hover:shadow-md dark:border-stone-800',
        ]"
        @click="$emit('select')"
    >
        <div
            class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-800"
        >
            <img
                v-if="file.preview_kind === 'image' && file.preview_url"
                :src="file.preview_url"
                alt=""
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                loading="lazy"
            />
            <template v-else-if="file.preview_kind === 'video'">
                <Film class="h-10 w-10 text-stone-500" />
                <span
                    class="absolute inset-0 flex items-center justify-center bg-black/10"
                >
                    <span class="rounded-full bg-black/65 p-2 text-white"
                        ><Play class="h-4 w-4 fill-current"
                    /></span>
                </span>
            </template>
            <FileText
                v-else-if="file.preview_kind === 'document'"
                class="h-10 w-10 text-stone-500"
            />
            <FileArchive
                v-else-if="file.extension === 'ZIP'"
                class="h-10 w-10 text-stone-500"
            />
            <PenTool
                v-else-if="['EPS', 'AI', 'SVG'].includes(file.extension)"
                class="h-10 w-10 text-stone-500"
            />
            <ImageIcon v-else class="h-10 w-10 text-stone-500" />

            <span
                class="absolute top-2 left-2 rounded-full bg-black/70 px-2 py-0.5 text-[10px] font-bold tracking-wide text-white uppercase backdrop-blur"
            >
                {{ file.extension }}
            </span>
        </div>
        <div class="p-3">
            <p class="truncate text-sm font-semibold">
                {{ file.role_label || file.role }}
            </p>
            <p class="mt-1 truncate text-xs text-stone-500">
                {{ detailLabel(file) }}
            </p>
        </div>
    </button>
</template>
