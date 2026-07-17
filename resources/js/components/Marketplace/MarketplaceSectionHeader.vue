<script setup lang="ts">
withDefaults(defineProps<{
    eyebrow?: string;
    title: string;
    description?: string | null;
    align?: 'left' | 'center';
}>(), {
    eyebrow: undefined,
    description: undefined,
    align: 'left',
});
</script>

<template>
    <div :class="align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl'">
        <p
            v-if="eyebrow"
            class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
        >
            {{ eyebrow }}
        </p>

        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-950 sm:text-3xl dark:text-white">
            {{ title }}
        </h2>

        <p
            v-if="description"
            class="mt-3 text-sm leading-7 text-stone-600 sm:text-base dark:text-stone-400"
        >
            {{ description }}
        </p>

        <div v-if="$slots.actions" class="mt-5 flex flex-wrap gap-3" :class="align === 'center' ? 'justify-center' : ''">
            <slot name="actions" />
        </div>
    </div>
</template>
