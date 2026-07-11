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
                ? 'grid gap-2 md:grid-cols-[220px_1fr] md:items-start'
                : 'space-y-2',
        ]"
    >
        <div>
            <label
                :for="forId"
                class="text-sm font-medium leading-none"
            >
                {{ label }}

                <span
                    v-if="required"
                    class="text-destructive"
                    aria-hidden="true"
                >
                    *
                </span>
            </label>

            <p
                v-if="description && layout === 'horizontal'"
                class="mt-1 text-xs leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div class="space-y-1.5">
            <slot />

            <p
                v-if="description && layout === 'vertical'"
                class="text-xs leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>

            <p
                v-if="error"
                class="text-sm text-destructive"
                role="alert"
            >
                {{ error }}
            </p>
        </div>
    </div>
</template>
