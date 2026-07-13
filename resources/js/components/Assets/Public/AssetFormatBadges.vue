<script setup lang="ts">
withDefaults(defineProps<{
    formats: string[];
    limit?: number;
}>(), {
    limit: 4,
});
</script>

<template>
    <div v-if="formats.length" class="flex flex-wrap gap-1.5" aria-label="Available file formats">
        <span
            v-for="format in formats.slice(0, limit)"
            :key="format"
            class="rounded-full border border-stone-300 bg-white/90 px-2 py-0.5 text-[10px] font-bold tracking-wide text-stone-700 shadow-sm backdrop-blur dark:border-stone-700 dark:bg-stone-900/90 dark:text-stone-200"
        >
            {{ format }}
        </span>
        <span
            v-if="formats.length > limit"
            class="rounded-full border border-stone-300 bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-stone-600 dark:border-stone-700 dark:bg-stone-900/90 dark:text-stone-300"
        >
            +{{ formats.length - limit }}
        </span>
    </div>
</template>
