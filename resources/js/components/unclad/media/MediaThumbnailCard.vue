<script setup lang="ts">
import { FileArchive, FileText, Film, Image as ImageIcon, PenTool } from '@lucide/vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

defineProps<{ file: MediaPresentationFile; selected: boolean }>();
defineEmits<{ select: [] }>();

function sizeLabel(bytes: number | null): string {
    if (!bytes) return 'Unknown size';
    if (bytes >= 1024 ** 3) return `${(bytes / 1024 ** 3).toFixed(1)} GB`;
    if (bytes >= 1024 ** 2) return `${(bytes / 1024 ** 2).toFixed(1)} MB`;
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
</script>

<template>
    <button
        type="button"
        :aria-pressed="selected"
        :class="[
            'w-44 shrink-0 overflow-hidden rounded-2xl border bg-white text-left transition dark:bg-stone-900',
            selected ? 'border-[var(--brand-primary)] ring-2 ring-[var(--brand-primary)]/20' : 'border-stone-200 hover:border-stone-400 dark:border-stone-800',
        ]"
        @click="$emit('select')"
    >
        <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-800">
            <img v-if="file.preview_kind === 'image' && file.preview_url" :src="file.preview_url" alt="" class="h-full w-full object-cover" loading="lazy" />
            <Film v-else-if="file.preview_kind === 'video'" class="h-10 w-10 text-stone-500" />
            <FileText v-else-if="file.preview_kind === 'document'" class="h-10 w-10 text-stone-500" />
            <FileArchive v-else-if="file.extension === 'ZIP'" class="h-10 w-10 text-stone-500" />
            <PenTool v-else-if="['EPS', 'AI', 'SVG'].includes(file.extension)" class="h-10 w-10 text-stone-500" />
            <ImageIcon v-else class="h-10 w-10 text-stone-500" />
        </div>
        <div class="p-3">
            <p class="truncate text-sm font-semibold">{{ file.role_label || file.role }}</p>
            <p class="mt-1 truncate text-xs text-stone-500">{{ file.extension }} · {{ sizeLabel(file.size_bytes) }}</p>
        </div>
    </button>
</template>
