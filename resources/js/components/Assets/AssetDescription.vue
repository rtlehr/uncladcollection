<script setup lang="ts">
defineProps<{
    description?: string | null;
}>();
</script>

<template>
    <section
        v-if="description"
        class="rounded-lg border bg-card p-6 shadow-sm"
    >
        <h2 class="mb-4 text-lg font-semibold">
            Description
        </h2>

        <div class="whitespace-pre-line text-sm leading-7">
            {{ description }}
        </div>
    </section>
</template>
