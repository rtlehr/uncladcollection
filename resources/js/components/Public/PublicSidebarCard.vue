<script setup lang="ts">
defineProps<{
    title: string;
    description?: string | null;
}>();
</script>

<template>
    <aside class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
        <h2 class="text-base font-semibold">{{ title }}</h2>
        <p v-if="description" class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
            {{ description }}
        </p>
        <div class="mt-5"><slot /></div>
    </aside>
</template>
