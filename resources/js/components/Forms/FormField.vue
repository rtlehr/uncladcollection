<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
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

const descriptionId = computed(() =>
    props.forId && props.description
        ? `${props.forId}-description`
        : undefined,
);

const errorId = computed(() =>
    props.forId && props.error
        ? `${props.forId}-error`
        : undefined,
);
</script>

<template>
    <div
        :class="[
            layout === 'horizontal'
                ? 'grid gap-3 md:grid-cols-[220px_minmax(0,1fr)] md:items-start'
                : 'space-y-2',
        ]"
        :data-invalid="Boolean(error) || undefined"
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

                <span v-if="required" class="sr-only">
                    required
                </span>
            </label>

            <p
                v-if="description && layout === 'horizontal'"
                :id="descriptionId"
                class="mt-1.5 text-xs leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div class="min-w-0 space-y-1.5">
            <slot
                :description-id="descriptionId"
                :error-id="errorId"
                :invalid="Boolean(error)"
            />

            <p
                v-if="description && layout === 'vertical'"
                :id="descriptionId"
                class="text-xs leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>

            <p
                v-if="error"
                :id="errorId"
                class="flex items-start gap-1.5 text-sm font-medium text-destructive"
                role="alert"
                aria-live="assertive"
            >
                <span aria-hidden="true">•</span>
                {{ error }}
            </p>
        </div>
    </div>
</template>
