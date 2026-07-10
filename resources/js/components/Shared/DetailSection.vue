<script setup lang="ts">
defineProps<{
    title: string;
}>();
</script>

<template>
    <section class="rounded-lg border bg-card p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">
            {{ title }}
        </h2>

        <slot />
    </section>
</template>
