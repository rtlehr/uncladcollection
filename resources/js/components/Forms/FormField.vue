<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        forId?: string;
        description?: string | null;
        error?: string | null;
        required?: boolean;
        layout?: 'vertical' | 'horizontal';
    }>(),
    {
        forId: undefined,
        description: null,
        error: null,
        required: false,
        layout: 'vertical',
    },
);
</script>

<template>
    <div
        :class="[
            layout === 'horizontal'
                ? 'grid gap-3 md:grid-cols-[220px_minmax(0,1fr)] md:items-start'
                : 'space-y-2',
        ]"
    >
        <div class="min-w-0">
            <label
                :for="forId"
                class="text-sm font-semibold leading-none text-foreground"
            >
                {{ label }}

                <span
                    v-if="required"
                    class="ml-0.5 text-destructive"
                    aria-hidden="true"
                >
                    *
                </span>
            </label>

            <p
                v-if="description && layout === 'horizontal'"
                class="mt-1.5 text-xs leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div class="min-w-0 space-y-1.5">
            <slot />

            <p
                v-if="description && layout === 'vertical'"
                class="text-xs leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>

            <p
                v-if="error"
                class="flex items-start gap-1.5 text-sm font-medium text-destructive"
                role="alert"
            >
                <span aria-hidden="true">•</span>
                {{ error }}
            </p>
        </div>
    </div>
</template>
