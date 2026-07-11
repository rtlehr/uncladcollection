<script setup lang="ts">
withDefaults(
    defineProps<{
        columns?: number;
        compact?: boolean;
    }>(),
    {
        columns: 4,
        compact: false,
    },
);
</script>

<template>
    <section
        :class="[
            'rounded-lg border bg-card shadow-sm',
            compact ? 'p-4' : 'p-6',
        ]"
    >
        <div
            :class="[
                'grid gap-4',
                columns === 2
                    ? 'md:grid-cols-2'
                    : columns === 3
                        ? 'md:grid-cols-3'
                        : columns === 5
                            ? 'md:grid-cols-2 xl:grid-cols-5'
                            : columns === 6
                                ? 'md:grid-cols-2 xl:grid-cols-6'
                                : 'md:grid-cols-2 xl:grid-cols-4',
            ]"
        >
            <slot />
        </div>

        <div
            v-if="$slots.actions"
            class="mt-4 flex flex-wrap justify-end gap-2"
        >
            <slot name="actions" />
        </div>
    </section>
</template>
