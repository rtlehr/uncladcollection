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
            'rounded-lg border shadow-sm',
            emphasized
                ? 'border-primary/30 bg-primary/5'
                : 'bg-card',
            size === 'sm'
                ? 'p-4'
                : size === 'lg'
                    ? 'p-6'
                    : 'p-5',
        ]"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="text-sm font-medium text-muted-foreground">
                    {{ label }}
                </div>

                <div
                    :class="[
                        'mt-2 break-words font-semibold tracking-tight',
                        size === 'sm'
                            ? 'text-xl'
                            : size === 'lg'
                                ? 'text-3xl'
                                : 'text-2xl',
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
                        'mt-2 text-sm font-medium',
                        trendClasses[trendTone],
                    ]"
                >
                    {{ trend }}
                </div>
            </div>

            <div
                v-if="$slots.icon"
                :class="[
                    'flex shrink-0 items-center justify-center rounded-md border bg-background',
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
