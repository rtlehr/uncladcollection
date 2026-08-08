<script setup lang="ts">
import type { MediaPresentationFile } from '@/types/mediaPresentation';

defineProps<{ file: MediaPresentationFile | null }>();

function sizeLabel(bytes: number | null): string {
    if (!bytes) {
return 'Unknown';
}

    if (bytes >= 1024 ** 3) {
return `${(bytes / 1024 ** 3).toFixed(2)} GB`;
}

    if (bytes >= 1024 ** 2) {
return `${(bytes / 1024 ** 2).toFixed(2)} MB`;
}

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function durationLabel(value: number | string | null): string | null {
    if (value === null) {
return null;
}

    const seconds = Number(value);

    if (!Number.isFinite(seconds)) {
return null;
}

    const minutes = Math.floor(seconds / 60);
    const remainder = Math.round(seconds % 60).toString().padStart(2, '0');

    return `${minutes}:${remainder}`;
}
</script>

<template>
    <div v-if="file" class="mt-4 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-sm sm:grid-cols-2 lg:grid-cols-4 dark:border-stone-800 dark:bg-stone-900">
        <div><p class="text-xs uppercase tracking-wide text-stone-500">File</p><p class="mt-1 truncate font-medium" :title="file.original_filename">{{ file.original_filename }}</p></div>
        <div><p class="text-xs uppercase tracking-wide text-stone-500">Format & size</p><p class="mt-1 font-medium">{{ file.extension }} · {{ sizeLabel(file.size_bytes) }}</p></div>
        <div><p class="text-xs uppercase tracking-wide text-stone-500">Dimensions</p><p class="mt-1 font-medium">{{ file.width && file.height ? `${file.width} × ${file.height}` : 'Not available' }}</p></div>
        <div><p class="text-xs uppercase tracking-wide text-stone-500">Duration / pages</p><p class="mt-1 font-medium">{{ durationLabel(file.duration_seconds) || (file.page_count ? `${file.page_count} pages` : 'Not available') }}</p></div>
    </div>
</template>
