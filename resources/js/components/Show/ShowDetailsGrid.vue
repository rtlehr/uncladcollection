<script setup lang="ts">
withDefaults(defineProps<{ columns?: 1 | 2 | 3 }>(), { columns: 2 });
</script>

<template>
    <div
        :class="[
            'grid gap-4',
            columns === 1
                ? 'grid-cols-1'
                : columns === 3
                    ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3'
                    : 'grid-cols-1 md:grid-cols-2',
        ]"
    >
        <slot />
    </div>
</template>
