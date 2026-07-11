<script setup lang="ts">
withDefaults(defineProps<{
    title?: string;
    description?: string | null;
    compact?: boolean;
}>(), {
    title: undefined,
    description: null,
    compact: false,
});
</script>

<template>
    <section class="rounded-lg border bg-card shadow-sm">
        <div
            v-if="title || description || $slots.actions"
            :class="[
                'flex flex-col gap-3 border-b sm:flex-row sm:items-start sm:justify-between',
                compact ? 'p-4' : 'p-6',
            ]"
        >
            <div>
                <h2 v-if="title" class="text-lg font-semibold">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm leading-6 text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div :class="compact ? 'p-4' : 'p-6'">
            <slot />
        </div>
    </section>
</template>
