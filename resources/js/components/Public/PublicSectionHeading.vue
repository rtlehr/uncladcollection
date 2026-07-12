<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string | null;
        title: string;
        description?: string | null;
        align?: 'left' | 'center';
    }>(),
    {
        eyebrow: null,
        description: null,
        align: 'left',
    },
);
</script>

<template>
    <div :class="[align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl']">
        <p
            v-if="eyebrow"
            class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
        >
            {{ eyebrow }}
        </p>

        <h2 class="mt-4 text-3xl font-semibold tracking-[-0.03em] sm:text-5xl">
            {{ title }}
        </h2>

        <p
            v-if="description"
            class="mt-4 text-base leading-8 text-stone-600 dark:text-stone-300"
        >
            {{ description }}
        </p>
    </div>
</template>
