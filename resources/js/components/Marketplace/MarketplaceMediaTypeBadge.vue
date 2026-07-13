<script setup lang="ts">
import { Camera, FileText, Film, Layers3, Package, PenTool } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    type: string;
    label: string;
}>();

const icon = computed(() => {
    return {
        image: Camera,
        vector: PenTool,
        video: Film,
        document: FileText,
        bundle: Package,
        mixed_media: Layers3,
    }[props.type] ?? Layers3;
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-black/60 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-white shadow-sm backdrop-blur">
        <component :is="icon" class="h-3.5 w-3.5" aria-hidden="true" />
        {{ label }}
    </span>
</template>
