<script setup lang="ts">
import { FileImage, FileText, Film } from '@lucide/vue';
import { computed } from 'vue';
import type { PublicAssetFile } from '@/types/publicAsset';

const props = defineProps<{ files: PublicAssetFile[] }>();
const imageFiles = computed(() => props.files.filter(file => ['image','vector'].includes(file.media_type) && (file.width || file.height)));
const videoFiles = computed(() => props.files.filter(file => file.media_type === 'video'));
const documentFiles = computed(() => props.files.filter(file => file.media_type === 'document'));
function duration(seconds: number | string | null): string {
 const value = Number(seconds || 0);

 if (!value) {
return '—';
}

 const m = Math.floor(value / 60); const s = Math.round(value % 60);

 return `${m}:${String(s).padStart(2, '0')}`; 
}
</script>
<template>
    <section v-if="imageFiles.length || videoFiles.length || documentFiles.length" class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Specifications</p>
        <h2 class="mt-2 text-2xl font-semibold">Technical details</h2>
        <div class="mt-6 grid gap-5 lg:grid-cols-3">
            <div v-if="imageFiles.length" class="rounded-2xl bg-stone-50 p-5 dark:bg-stone-800/60"><FileImage class="h-5 w-5"/><h3 class="mt-3 font-semibold">Images & vectors</h3><div class="mt-4 space-y-3 text-sm"><div v-for="file in imageFiles" :key="file.id"><p class="font-medium">{{ file.role_label }}</p><p class="text-stone-500">{{ file.extension.toUpperCase() }} · {{ file.width }} × {{ file.height }}</p></div></div></div>
            <div v-if="videoFiles.length" class="rounded-2xl bg-stone-50 p-5 dark:bg-stone-800/60"><Film class="h-5 w-5"/><h3 class="mt-3 font-semibold">Video</h3><div class="mt-4 space-y-3 text-sm"><div v-for="file in videoFiles" :key="file.id"><p class="font-medium">{{ file.role_label }}</p><p class="text-stone-500">{{ file.extension.toUpperCase() }}<span v-if="file.width && file.height"> · {{ file.width }} × {{ file.height }}</span><span v-if="file.duration_seconds"> · {{ duration(file.duration_seconds) }}</span></p></div></div></div>
            <div v-if="documentFiles.length" class="rounded-2xl bg-stone-50 p-5 dark:bg-stone-800/60"><FileText class="h-5 w-5"/><h3 class="mt-3 font-semibold">Documents</h3><div class="mt-4 space-y-3 text-sm"><div v-for="file in documentFiles" :key="file.id"><p class="font-medium">{{ file.role_label }}</p><p class="text-stone-500">{{ file.extension.toUpperCase() }}<span v-if="file.page_count"> · {{ file.page_count }} pages</span></p></div></div></div>
        </div>
    </section>
</template>
