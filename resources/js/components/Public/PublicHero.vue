<script setup lang="ts">
withDefaults(defineProps<{
    eyebrow?: string | null;
    title: string;
    description?: string | null;
    compact?: boolean;
}>(), {
    eyebrow: null,
    description: null,
    compact: false,
});
</script>

<template>
    <section
        :class="[
            'relative overflow-hidden border-b border-stone-200 dark:border-stone-800',
            compact ? 'py-12 sm:py-16' : 'py-16 sm:py-24',
        ]"
    >
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_10%_10%,color-mix(in_srgb,var(--brand-accent)_12%,transparent),transparent_32%),radial-gradient(circle_at_85%_15%,color-mix(in_srgb,var(--brand-secondary)_14%,transparent),transparent_38%)]" />

        <div class="relative mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="max-w-3xl">
                <slot name="breadcrumbs" />

                <p v-if="eyebrow" class="mt-6 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                    {{ eyebrow }}
                </p>

                <h1 :class="['font-semibold leading-[1.08] tracking-[-0.035em]', compact ? 'mt-4 text-4xl sm:text-5xl' : 'mt-5 text-4xl sm:text-6xl']">
                    {{ title }}
                </h1>

                <p v-if="description" class="mt-5 text-base leading-8 text-stone-600 sm:text-lg dark:text-stone-300">
                    {{ description }}
                </p>

                <div v-if="$slots.actions" class="mt-7 flex flex-wrap gap-3">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </section>
</template>
