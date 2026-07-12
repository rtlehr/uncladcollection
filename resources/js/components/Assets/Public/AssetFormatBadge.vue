<script setup lang="ts">
defineProps<{ format: string }>();
</script>
<template>
    <span class="inline-flex rounded-md border border-stone-300 bg-white px-2.5 py-1 text-xs font-bold tracking-wide text-stone-700 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200">
        {{ format.toUpperCase() }}
    </span>
</template>
