<script setup lang="ts">
import { FileArchive, FileText, Film, Image as ImageIcon } from '@lucide/vue';
import type { PublicAsset } from '@/types/publicAsset';

defineProps<{ asset: PublicAsset }>();
</script>
<template>
    <div class="overflow-hidden rounded-3xl border border-stone-200 bg-stone-100 shadow-sm dark:border-stone-800 dark:bg-stone-900">
        <video
            v-if="asset.preview?.media_type === 'video' && asset.preview.url"
            class="max-h-[72vh] w-full bg-black object-contain"
            controls
            preload="metadata"
            :poster="asset.poster?.url"
        >
            <source :src="asset.preview.url" :type="asset.preview.mime_type || undefined" />
        </video>
        <img
            v-else-if="asset.preview?.url"
            :src="asset.preview.url"
            :alt="asset.title"
            class="max-h-[72vh] w-full object-contain"
        />
        <div v-else class="flex min-h-[420px] flex-col items-center justify-center gap-4 text-stone-400">
            <Film v-if="asset.asset_type === 'video'" class="h-16 w-16" />
            <FileArchive v-else-if="asset.asset_type === 'bundle'" class="h-16 w-16" />
            <FileText v-else-if="asset.asset_type === 'document'" class="h-16 w-16" />
            <ImageIcon v-else class="h-16 w-16" />
            <p class="text-sm font-medium">Preview not available</p>
        </div>
    </div>
</template>
