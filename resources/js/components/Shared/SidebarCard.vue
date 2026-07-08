<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <section class="rounded-2xl border bg-card p-5 shadow-sm">
        <div v-if="title || description" class="mb-4">
            <div
                v-if="title"
                class="text-sm font-semibold uppercase tracking-wide text-muted-foreground"
            >
                {{ title }}
            </div>

            <p
                v-if="description"
                class="mt-1 text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <slot />
    </section>
</template>