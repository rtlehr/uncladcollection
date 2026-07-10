<script setup lang="ts">
withDefaults(
    defineProps<{
        src?: string | null;
        alt: string;
        aspect?: 'square' | 'landscape';
        fallbackText?: string;
    }>(),
    {
        src: null,
        aspect: 'square',
        fallbackText: 'No preview',
    },
);
</script>

<template>
    <div
        :class="[
            'overflow-hidden bg-muted',
            aspect === 'square' ? 'aspect-square' : 'aspect-[16/9]',
        ]"
    >
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            loading="lazy"
        />

        <div
            v-else
            class="flex h-full items-center justify-center px-4 text-center text-sm text-muted-foreground"
        >
            {{ fallbackText }}
        </div>
    </div>
</template>
