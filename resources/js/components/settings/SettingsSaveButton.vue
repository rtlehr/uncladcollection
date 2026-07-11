<script setup lang="ts">
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        processing?: boolean;
        label?: string;
        processingLabel?: string;
        testId?: string;
    }>(),
    {
        processing: false,
        label: 'Save',
        processingLabel: 'Saving...',
        testId: undefined,
    },
);
</script>

<template>
    <div class="flex items-center gap-4">
        <Button
            type="submit"
            :loading="processing"
            :loading-label="processingLabel"
            :data-test="testId"
        >
            {{ label }}
        </Button>

        <slot />
    </div>
</template>
