<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string;
        title: string;
        description?: string | null;
        align?: 'left' | 'center';
    }>(),
    {
        eyebrow: undefined,
        description: null,
        align: 'left',
    },
);
</script>

<template>
    <header
        :class="[
            'min-w-0',
            align === 'center'
                ? 'mx-auto max-w-4xl text-center'
                : 'w-full',
        ]"
    >
        <slot name="before" />

        <div
            :class="[
                align === 'center'
                    ? ''
                    : 'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between',
            ]"
        >
            <div class="min-w-0">
                <p
                    v-if="eyebrow"
                    class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-primary"
                >
                    {{ eyebrow }}
                </p>

                <h1
                    class="break-words text-2xl font-semibold tracking-tight text-foreground sm:text-3xl"
                >
                    {{ title }}
                </h1>

                <p
                    v-if="description"
                    :class="[
                        'mt-2 text-sm leading-6 text-muted-foreground sm:text-base',
                        align === 'center'
                            ? 'mx-auto max-w-3xl'
                            : 'max-w-4xl',
                    ]"
                >
                    {{ description }}
                </p>
            </div>

            <div
                v-if="$slots.actions"
                class="flex shrink-0 flex-wrap items-center gap-3"
            >
                <slot name="actions" />
            </div>
        </div>

        <div
            v-if="$slots.default"
            class="mt-4"
        >
            <slot />
        </div>
    </header>
</template>