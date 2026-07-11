<script setup lang="ts">
withDefaults(
    defineProps<{
        align?: 'start' | 'between' | 'end';
    }>(),
    {
        align: 'between',
    },
);
</script>

<template>
    <div
        :class="[
            'flex min-h-10 flex-col gap-3 sm:flex-row sm:items-center',
            align === 'start'
                ? 'sm:justify-start'
                : align === 'end'
                    ? 'sm:justify-end'
                    : 'sm:justify-between',
        ]"
    >
        <div
            v-if="$slots.primary"
            class="flex min-w-0 flex-1 flex-wrap items-center gap-2"
        >
            <slot name="primary" />
        </div>

        <div
            v-if="$slots.secondary"
            class="flex shrink-0 flex-wrap items-center gap-2"
        >
            <slot name="secondary" />
        </div>
    </div>
</template>
