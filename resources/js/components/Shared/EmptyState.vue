<script setup lang="ts">
defineProps<{
    title: string;
    description?: string | null;
}>();
</script>

<template>
    <div
        class="rounded-xl border border-dashed border-border bg-muted/15 px-6 py-12 text-center sm:px-10"
        role="status"
        aria-live="polite"
    >
        <div
            v-if="$slots.icon"
            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl border border-border/70 bg-background text-muted-foreground shadow-sm"
            aria-hidden="true"
        >
            <slot name="icon" />
        </div>

        <h3 class="text-base font-semibold tracking-tight text-foreground sm:text-lg">
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mx-auto mt-2 max-w-lg text-sm leading-6 text-muted-foreground"
        >
            {{ description }}
        </p>

        <div
            v-if="$slots.actions"
            class="mt-6 flex flex-wrap justify-center gap-2"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
