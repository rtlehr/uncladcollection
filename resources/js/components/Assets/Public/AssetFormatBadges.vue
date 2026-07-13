<script setup lang="ts">
import { FileArchive, FileImage, FileText, FileVideo, Shapes } from '@lucide/vue';

withDefaults(defineProps<{
    formats: string[];
    limit?: number;
}>(), {
    limit: 4,
});

function iconFor(format: string) {
    const value = format.toLowerCase();

    if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'tif', 'tiff'].includes(value)) return FileImage;
    if (['mp4', 'mov', 'webm', 'ogg'].includes(value)) return FileVideo;
    if (['eps', 'svg', 'ai'].includes(value)) return Shapes;
    if (['zip', 'rar', '7z'].includes(value)) return FileArchive;

    return FileText;
}
</script>

<template>
    <div v-if="formats.length" class="flex flex-wrap gap-1.5" aria-label="Available file formats">
        <span
            v-for="format in formats.slice(0, limit)"
            :key="format"
            class="inline-flex items-center gap-1 rounded-full border border-white/55 bg-white/95 px-2 py-1 text-[10px] font-bold tracking-wide text-stone-700 shadow-sm backdrop-blur dark:border-stone-700 dark:bg-stone-900/95 dark:text-stone-200"
        >
            <component :is="iconFor(format)" class="h-3 w-3" aria-hidden="true" />
            {{ format.toUpperCase() }}
        </span>

        <span
            v-if="formats.length > limit"
            class="inline-flex items-center rounded-full border border-white/55 bg-white/95 px-2 py-1 text-[10px] font-semibold text-stone-600 shadow-sm backdrop-blur dark:border-stone-700 dark:bg-stone-900/95 dark:text-stone-300"
            :aria-label="`${formats.length - limit} more formats`"
        >
            +{{ formats.length - limit }}
        </span>
    </div>
</template>
