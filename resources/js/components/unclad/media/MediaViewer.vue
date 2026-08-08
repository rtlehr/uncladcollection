<script setup lang="ts">
import { Maximize2, Play } from '@lucide/vue';
import type { MediaPresentationFile } from '@/types/mediaPresentation';
import MediaPreviewUnavailable from './MediaPreviewUnavailable.vue';

defineProps<{
    file: MediaPresentationFile | null;
    assetTitle: string;
    interactive?: boolean;
}>();
defineEmits<{ open: [] }>();
</script>

<template>
    <div
        class="group relative isolate flex min-h-[420px] items-center justify-center overflow-hidden rounded-3xl bg-stone-950 sm:min-h-[520px] lg:min-h-[620px]"
    >
        <template v-if="file?.preview_kind === 'image' && file.preview_url">
            <img
                :src="file.preview_url"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 h-full w-full scale-110 object-cover opacity-35 blur-3xl saturate-75"
            />
            <div
                class="absolute inset-0 bg-gradient-to-b from-black/10 via-black/15 to-black/40"
            />
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,.18)_72%,rgba(0,0,0,.42)_100%)]"
            />

            <button
                type="button"
                class="relative z-10 flex h-full w-full items-center justify-center p-4 sm:p-7 lg:p-10"
                :aria-label="`Open full-screen preview of ${assetTitle}`"
                @click="$emit('open')"
            >
                <img
                    :src="file.preview_url"
                    :alt="`${assetTitle} — ${file.role_label || file.original_filename}`"
                    class="max-h-[76vh] max-w-full rounded-xl object-contain shadow-[0_28px_80px_rgba(0,0,0,.5)] ring-1 ring-white/10 transition duration-300 group-hover:scale-[1.005]"
                />
            </button>
        </template>

        <template
            v-else-if="file?.preview_kind === 'video' && file.preview_url"
        >
            <video
                :key="file.id"
                class="max-h-[76vh] min-h-[420px] w-full bg-black object-contain sm:min-h-[520px]"
                controls
                playsinline
                preload="metadata"
                :poster="file.poster_url || undefined"
            >
                <source
                    :src="file.preview_url"
                    :type="file.mime_type || undefined"
                />
                Your browser does not support this video format.
            </video>
            <button
                type="button"
                class="absolute top-4 right-4 z-20 rounded-full border border-white/15 bg-black/55 p-2.5 text-white backdrop-blur transition hover:bg-black/75"
                aria-label="Open full-screen video"
                @click="$emit('open')"
            >
                <Maximize2 class="h-5 w-5" />
            </button>
        </template>

        <iframe
            v-else-if="file?.preview_kind === 'document' && file.preview_url"
            :key="file.id"
            :src="file.preview_url"
            :title="`${assetTitle} document preview`"
            class="h-[70vh] min-h-[560px] w-full bg-white"
            sandbox
        />

        <MediaPreviewUnavailable v-else :file="file" :title="assetTitle" />

        <div
            v-if="file"
            class="pointer-events-none absolute top-4 left-4 z-30 flex flex-wrap gap-2"
        >
            <span
                class="rounded-full bg-black/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur"
                >{{ file.role_label || file.role }}</span
            >
            <span
                class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-stone-900 backdrop-blur"
                >{{ file.extension }}</span
            >
        </div>

        <button
            v-if="file?.can_preview"
            type="button"
            class="absolute right-4 bottom-4 z-30 inline-flex items-center gap-2 rounded-full border border-white/15 bg-black/60 px-4 py-2 text-xs font-semibold text-white opacity-0 backdrop-blur transition group-hover:opacity-100 focus:opacity-100"
            @click="$emit('open')"
        >
            <Play v-if="file.preview_kind === 'video'" class="h-4 w-4" />
            <Maximize2 v-else class="h-4 w-4" />
            Full screen
        </button>
    </div>
</template>
