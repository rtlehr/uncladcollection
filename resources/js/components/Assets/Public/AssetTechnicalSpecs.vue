<script setup lang="ts">
import { computed } from 'vue';
import type { PublicAssetFile } from '@/types/publicAsset';

const props = defineProps<{ files: PublicAssetFile[] }>();
const specs = computed(() => props.files.filter((file) => file.width || file.height || file.duration_seconds || file.page_count));
function duration(seconds: number | null): string { if (!seconds) return '—'; const m = Math.floor(seconds / 60); const s = Math.round(seconds % 60); return `${m}:${String(s).padStart(2, '0')}`; }
</script>
<template>
    <section v-if="specs.length" class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
        <h2 class="text-xl font-semibold">Technical specifications</h2>
        <div class="mt-5 divide-y divide-stone-200 dark:divide-stone-800">
            <div v-for="file in specs" :key="file.id" class="grid gap-2 py-4 sm:grid-cols-[minmax(0,1fr)_auto]">
                <div><p class="font-medium">{{ file.role_label }}</p><p class="text-sm text-stone-500">{{ file.extension }} · {{ file.original_filename }}</p></div>
                <div class="text-sm text-stone-600 sm:text-right dark:text-stone-300">
                    <span v-if="file.width && file.height">{{ file.width }} × {{ file.height }}</span>
                    <span v-if="file.duration_seconds">{{ file.width && file.height ? ' · ' : '' }}{{ duration(file.duration_seconds) }}</span>
                    <span v-if="file.page_count">{{ file.page_count }} pages</span>
                </div>
            </div>
        </div>
    </section>
</template>
