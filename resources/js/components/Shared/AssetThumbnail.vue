<script setup lang="ts">
withDefaults(
    defineProps<{
        src?: string | null;
        alt: string;
        size?: 'sm' | 'md' | 'lg';
        rounded?: 'md' | 'lg' | 'full';
        fit?: 'cover' | 'contain';
        fallback?: string;
        lazy?: boolean;
    }>(),
    {
        src: null,
        size: 'md',
        rounded: 'md',
        fit: 'cover',
        fallback: 'No image',
        lazy: true,
    },
);

const sizeClasses = {
    sm: 'h-12 w-12',
    md: 'h-16 w-16',
    lg: 'h-24 w-24',
};

const roundedClasses = {
    md: 'rounded-md',
    lg: 'rounded-lg',
    full: 'rounded-full',
};
</script>

<template>
    <div
        :class="[
            'shrink-0 overflow-hidden border border-border bg-muted',
            sizeClasses[size],
            roundedClasses[rounded],
        ]"
    >
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            :loading="lazy ? 'lazy' : 'eager'"
            decoding="async"
            :class="[
                'h-full w-full',
                fit === 'contain' ? 'object-contain' : 'object-cover',
            ]"
        />

        <div
            v-else
            class="flex h-full w-full items-center justify-center px-1 text-center text-[10px] leading-tight text-muted-foreground"
            role="img"
            :aria-label="`${alt}: ${fallback}`"
        >
            {{ fallback }}
        </div>
    </div>
</template>
