<script setup lang="ts">
defineProps<{
    title: string;
    description?: string | null;
}>();
</script>

<template>
    <div class="rounded-2xl border border-dashed bg-card px-6 py-12 text-center">
        <div
            v-if="$slots.icon"
            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <slot name="icon" />
        </div>

        <h3 class="text-lg font-semibold">
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground"
        >
            {{ description }}
        </p>

        <div
            v-if="$slots.actions"
            class="mt-6 flex justify-center"
        >
            <slot name="actions" />
        </div>
    </div>
</template>