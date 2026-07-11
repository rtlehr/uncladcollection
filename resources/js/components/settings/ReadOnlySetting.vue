<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        value?: string | string[] | null;
        emptyLabel?: string;
        description?: string | null;
    }>(),
    {
        value: null,
        emptyLabel: 'Not assigned',
        description: null,
    },
);

function displayValue(value: string | string[] | null): string {
    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '';
    }

    return value ?? '';
}
</script>

<template>
    <div class="grid gap-2">
        <div class="text-sm font-medium">
            {{ label }}
        </div>

        <div
            class="rounded-md border bg-muted/40 px-3 py-2 text-sm"
            role="status"
            aria-live="polite"
        >
            <span v-if="displayValue(value)">
                {{ displayValue(value) }}
            </span>

            <span v-else class="text-muted-foreground">
                {{ emptyLabel }}
            </span>
        </div>

        <p
            v-if="description"
            class="text-xs leading-5 text-muted-foreground"
        >
            {{ description }}
        </p>
    </div>
</template>
