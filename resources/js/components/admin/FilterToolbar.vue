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
            'rounded-xl border border-border/80 bg-card shadow-sm',
            compact ? 'p-4' : 'p-5 sm:p-6',
        ]"
    >
        <div
            :class="[
                'grid items-end gap-4',
                columns === 1
                    ? 'grid-cols-1'
                    : columns === 2
                        ? 'md:grid-cols-2'
                        : columns === 3
                            ? 'md:grid-cols-2 xl:grid-cols-3'
                            : columns === 5
                                ? 'md:grid-cols-2 xl:grid-cols-5'
                                : columns === 6
                                    ? 'md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6'
                                    : 'md:grid-cols-2 xl:grid-cols-4',
            ]"
        >
            <slot />
        </div>

        <div
            v-if="$slots.actions"
            class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-border/60 pt-4"
        >
            <slot name="actions" />
        </div>
    </section>
</template>
