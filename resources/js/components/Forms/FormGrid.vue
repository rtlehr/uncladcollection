<script setup lang="ts">
withDefaults(
    defineProps<{
        columns?: 1 | 2 | 3 | 4;
        gap?: 'sm' | 'md' | 'lg';
    }>(),
    {
        columns: 2,
        gap: 'md',
    },
);
</script>

<template>
    <div
        :class="[
            'grid',
            columns === 1
                ? 'grid-cols-1'
                : columns === 2
                    ? 'grid-cols-1 md:grid-cols-2'
                    : columns === 3
                        ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3'
                        : 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4',
            gap === 'sm'
                ? 'gap-3'
                : gap === 'lg'
                    ? 'gap-8'
                    : 'gap-6',
        ]"
    >
        <slot />
    </div>
</template>
