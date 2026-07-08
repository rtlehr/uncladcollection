<script setup lang="ts">
defineProps<{
    eyebrow?: string;
    title: string;
    description?: string | null;
}>();
</script>

<template>
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <p
                v-if="eyebrow"
                class="text-sm font-semibold uppercase tracking-wide text-primary"
            >
                {{ eyebrow }}
            </p>

            <h2 class="mt-2 text-3xl font-bold tracking-tight">
                {{ title }}
            </h2>

            <p
                v-if="description"
                class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>