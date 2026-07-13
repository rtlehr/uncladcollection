<script setup lang="ts">
import { FileArchive, FileQuestion, FileText, PenTool } from '@lucide/vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

defineProps<{ file: MediaPresentationFile | null; title?: string }>();
</script>

<template>
    <div class="flex min-h-[360px] flex-col items-center justify-center gap-4 p-8 text-center text-stone-500 dark:text-stone-400">
        <FileArchive v-if="file?.extension === 'ZIP'" class="h-16 w-16" />
        <PenTool v-else-if="['EPS', 'AI', 'SVG'].includes(file?.extension ?? '')" class="h-16 w-16" />
        <FileText v-else-if="file?.media_type === 'document'" class="h-16 w-16" />
        <FileQuestion v-else class="h-16 w-16" />
        <div>
            <p class="font-semibold text-stone-800 dark:text-stone-100">Preview unavailable</p>
            <p class="mt-1 max-w-lg text-sm">{{ file?.preview_note || `No browser preview is available for ${title || 'this file'}.` }}</p>
        </div>
    </div>
</template>
