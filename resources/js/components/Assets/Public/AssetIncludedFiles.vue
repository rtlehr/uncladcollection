<script setup lang="ts">
import { File, FileArchive, FileImage, FileText, Film } from '@lucide/vue';
import type { PublicAssetFile } from '@/types/publicAsset';

defineProps<{ files: PublicAssetFile[] }>();

function bytes(value: number | null): string {
    if (!value) {
return 'Unknown size';
}

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);

    return `${(value / 1024 ** index).toFixed(index ? 1 : 0)} ${units[index]}`;
}

function iconFor(file: PublicAssetFile) {
    if (file.media_type === 'video') {
return Film;
}

    if (file.media_type === 'image' || file.media_type === 'vector') {
return FileImage;
}

    if (file.media_type === 'archive') {
return FileArchive;
}

    if (file.media_type === 'document') {
return FileText;
}

    return File;
}
</script>

<template>
    <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Included files</p>
                <h2 class="mt-2 text-2xl font-semibold">Everything in this asset</h2>
            </div>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold dark:bg-stone-800">{{ files.length }} files</span>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2">
            <article v-for="file in files" :key="file.id" class="flex gap-4 rounded-2xl border border-stone-200 p-4 dark:border-stone-800">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-stone-100 dark:bg-stone-800">
                    <component :is="iconFor(file)" class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="font-medium">{{ file.role_label || file.role }}</p>
                    <p class="truncate text-sm text-stone-500">{{ file.original_filename }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        {{ file.extension.toUpperCase() }} · {{ bytes(file.size_bytes) }}
                        <span v-if="file.width && file.height"> · {{ file.width }} × {{ file.height }}</span>
                    </p>
                </div>
            </article>
        </div>
    </section>
</template>
