<script setup lang="ts">
withDefaults(defineProps<{ columns?: 1 | 2 | 3 | 4 }>(), { columns: 3 });
</script>

<template>
    <div
        :class="[
            'grid gap-3',
            columns === 1
                ? 'grid-cols-1'
                : columns === 2
                    ? 'grid-cols-1 sm:grid-cols-2'
                    : columns === 4
                        ? 'grid-cols-2 xl:grid-cols-4'
                        : 'grid-cols-1 sm:grid-cols-3',
        ]"
    >
        <slot />
    </div>
</template>
