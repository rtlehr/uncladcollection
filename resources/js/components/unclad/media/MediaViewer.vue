<script setup lang="ts">
import MediaPreviewUnavailable from './MediaPreviewUnavailable.vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';

defineProps<{ file: MediaPresentationFile | null; assetTitle: string }>();
</script>

<template>
    <div class="relative overflow-hidden rounded-3xl border border-stone-200 bg-stone-100 shadow-sm dark:border-stone-800 dark:bg-stone-950">
        <img
            v-if="file?.preview_kind === 'image' && file.preview_url"
            :src="file.preview_url"
            :alt="`${assetTitle} — ${file.role_label || file.original_filename}`"
            class="max-h-[72vh] min-h-[360px] w-full object-contain"
        />

        <video
            v-else-if="file?.preview_kind === 'video' && file.preview_url"
            :key="file.id"
            class="max-h-[72vh] min-h-[360px] w-full bg-black object-contain"
            controls
            playsinline
            preload="metadata"
            :poster="file.poster_url || undefined"
        >
            <source :src="file.preview_url" :type="file.mime_type || undefined" />
            Your browser does not support this video format.
        </video>

        <iframe
            v-else-if="file?.preview_kind === 'document' && file.preview_url"
            :key="file.id"
            :src="file.preview_url"
            :title="`${assetTitle} document preview`"
            class="h-[70vh] min-h-[520px] w-full bg-white"
            sandbox
        />

        <MediaPreviewUnavailable v-else :file="file" :title="assetTitle" />

        <div v-if="file" class="absolute left-4 top-4 flex flex-wrap gap-2">
            <span class="rounded-full bg-black/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ file.role_label || file.role }}</span>
            <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-stone-900 backdrop-blur">{{ file.extension }}</span>
        </div>
    </div>
</template>
