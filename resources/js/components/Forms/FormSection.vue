<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
        description?: string | null;
        compact?: boolean;
    }>(),
    {
        title: undefined,
        description: null,
        compact: false,
    },
);
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-sm">
        <div
            v-if="title || description || $slots.headerActions"
            :class="[
                'flex flex-col gap-3 border-b border-border/70 bg-muted/10 sm:flex-row sm:items-start sm:justify-between',
                compact ? 'px-4 py-4' : 'px-5 py-5 sm:px-6',
            ]"
        >
            <div class="min-w-0">
                <h2
                    v-if="title"
                    class="text-base font-semibold tracking-tight text-foreground sm:text-lg"
                >
                    {{ title }}
                </h2>

                <p
                    v-if="description"
                    class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>

            <div
                v-if="$slots.headerActions"
                class="flex shrink-0 flex-wrap gap-2"
            >
                <slot name="headerActions" />
            </div>
        </div>

        <div :class="compact ? 'p-4' : 'p-5 sm:p-6'">
            <slot />
        </div>

        <div
            v-if="$slots.footer"
            :class="[
                'border-t border-border/70 bg-muted/15',
                compact ? 'p-4' : 'p-5 sm:p-6',
            ]"
        >
            <slot name="footer" />
        </div>
    </section>
</template>
