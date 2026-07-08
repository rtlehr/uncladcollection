<script setup lang="ts">
defineProps<{
    eyebrow?: string;
    title: string;
    description?: string | null;
    align?: 'left' | 'center';
}>();
</script>

<template>
    <header
        :class="[
            'mx-auto',
            align === 'center' ? 'max-w-4xl text-center' : 'max-w-7xl',
        ]"
    >
        <slot name="before" />

        <p
            v-if="eyebrow"
            class="text-sm font-semibold uppercase tracking-wide text-primary"
        >
            {{ eyebrow }}
        </p>

        <h1
            :class="[
                eyebrow ? 'mt-2' : '',
                'text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl',
            ]"
        >
            {{ title }}
        </h1>

        <p
            v-if="description"
            class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-muted-foreground"
        >
            {{ description }}
        </p>

        <slot />
    </header>
</template>