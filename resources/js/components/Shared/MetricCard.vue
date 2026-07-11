<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        description?: string | null;
        trend?: string | null;
        trendTone?: 'positive' | 'negative' | 'neutral' | 'info';
        size?: 'sm' | 'md' | 'lg';
        emphasized?: boolean;
    }>(),
    {
        description: null,
        trend: null,
        trendTone: 'neutral',
        size: 'md',
        emphasized: false,
    },
);

const trendClasses = {
    positive: 'text-emerald-700 dark:text-emerald-300',
    negative: 'text-red-700 dark:text-red-300',
    neutral: 'text-muted-foreground',
    info: 'text-sky-700 dark:text-sky-300',
};
</script>

<template>
    <article
        :class="[
            'group relative overflow-hidden rounded-xl border shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md',
            emphasized
                ? 'border-primary/25 bg-gradient-to-br from-primary/10 via-card to-card'
                : 'border-border/80 bg-card',
            size === 'sm'
                ? 'p-4'
                : size === 'lg'
                    ? 'p-6'
                    : 'p-5',
        ]"
    >
        <div
            v-if="emphasized"
            class="absolute inset-x-0 top-0 h-0.5 bg-primary/70"
            aria-hidden="true"
        />

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ label }}
                </div>

                <div
                    :class="[
                        'mt-2 break-words font-semibold tracking-tight text-foreground',
                        size === 'sm'
                            ? 'text-xl'
                            : size === 'lg'
                                ? 'text-3xl sm:text-4xl'
                                : 'text-2xl sm:text-3xl',
                    ]"
                >
                    {{ value }}
                </div>

                <p
                    v-if="description"
                    class="mt-2 text-sm leading-5 text-muted-foreground"
                >
                    {{ description }}
                </p>

                <div
                    v-if="trend"
                    :class="[
                        'mt-3 inline-flex items-center rounded-full bg-muted/70 px-2 py-1 text-xs font-semibold',
                        trendClasses[trendTone],
                    ]"
                >
                    {{ trend }}
                </div>
            </div>

            <div
                v-if="$slots.icon"
                :class="[
                    'flex shrink-0 items-center justify-center rounded-lg border border-border/70 bg-background/80 text-muted-foreground shadow-sm transition group-hover:text-foreground',
                    size === 'sm'
                        ? 'h-9 w-9'
                        : size === 'lg'
                            ? 'h-12 w-12'
                            : 'h-10 w-10',
                ]"
            >
                <slot name="icon" />
            </div>
        </div>
    </article>
</template>
