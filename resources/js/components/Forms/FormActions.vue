<script setup lang="ts">
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        submitLabel?: string;
        cancelLabel?: string;
        processingLabel?: string;
        processing?: boolean;
        disabled?: boolean;
        showCancel?: boolean;
        sticky?: boolean;
    }>(),
    {
        submitLabel: 'Save',
        cancelLabel: 'Cancel',
        processingLabel: 'Saving...',
        processing: false,
        disabled: false,
        showCancel: true,
        sticky: false,
    },
);

const emit = defineEmits<{
    cancel: [];
}>();
</script>

<template>
    <div
        :class="[
            'flex flex-col-reverse gap-3 border-t border-border/70 pt-5 sm:flex-row sm:items-center sm:justify-between',
            sticky
                ? 'sticky bottom-0 z-20 -mx-4 border-y bg-background/90 px-4 py-4 shadow-[0_-8px_24px_rgba(0,0,0,0.04)] backdrop-blur sm:-mx-6 sm:px-6'
                : '',
        ]"
    >
        <div class="flex flex-wrap gap-2">
            <slot name="destructive" />
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <Button
                v-if="showCancel"
                type="button"
                variant="outline"
                :disabled="processing"
                @click="emit('cancel')"
            >
                {{ cancelLabel }}
            </Button>

            <slot name="secondary" />

            <Button
                type="submit"
                :disabled="disabled || processing"
            >
                {{ processing ? processingLabel : submitLabel }}
            </Button>
        </div>
    </div>
</template>