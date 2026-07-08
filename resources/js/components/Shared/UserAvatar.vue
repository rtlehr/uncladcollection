<script setup lang="ts">
withDefaults(
    defineProps<{
        src?: string | null;
        alt?: string;
        size?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        alt: 'User',
        size: 'md',
    },
);

const sizes = {
    sm: 'h-8 w-8',
    md: 'h-11 w-11',
    lg: 'h-16 w-16',
    xl: 'h-24 w-24',
};
</script>

<template>
    <img
        v-if="src"
        :src="src"
        :alt="alt"
        :class="[
            sizes[size],
            'rounded-full object-cover ring-2 ring-background',
        ]"
        loading="lazy"
    />
</template>