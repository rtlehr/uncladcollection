<script setup lang="ts">
withDefaults(defineProps<{
    eyebrow?: string | null;
    title?: string | null;
    description?: string | null;
    tone?: 'default' | 'muted' | 'dark';
    narrow?: boolean;
}>(), {
    eyebrow: null,
    title: null,
    description: null,
    tone: 'default',
    narrow: false,
});
</script>

<template>
    <section
        :class="[
            'py-16 sm:py-20',
            tone === 'muted'
                ? 'bg-[color-mix(in_srgb,var(--brand-secondary)_7%,white)] dark:bg-[color-mix(in_srgb,var(--brand-secondary)_12%,#0c0a09)]'
                : tone === 'dark'
                    ? 'bg-[var(--brand-primary)] text-white'
                    : '',
        ]"
    >
        <div :class="['mx-auto px-5 sm:px-8 lg:px-12', narrow ? 'max-w-4xl' : 'max-w-[1440px]']">
            <div v-if="eyebrow || title || description" class="mb-10 max-w-3xl">
                <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                    {{ eyebrow }}
                </p>
                <h2 v-if="title" class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    :class="[
                        'mt-4 text-base leading-8',
                        tone === 'dark' ? 'text-white/75' : 'text-stone-600 dark:text-stone-300',
                    ]"
                >
                    {{ description }}
                </p>
            </div>

            <slot />
        </div>
    </section>
</template>
