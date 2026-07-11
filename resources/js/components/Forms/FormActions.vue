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
    submit: [];
    cancel: [];
}>();
</script>

<template>
    <div
        :class="[
            'flex flex-col-reverse gap-3 border-t bg-background/95 pt-6 sm:flex-row sm:items-center sm:justify-between',
            sticky
                ? 'sticky bottom-0 z-10 -mx-6 px-6 pb-6 backdrop-blur'
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
                type="button"
                :disabled="disabled || processing"
                @click="emit('submit')"
            >
                {{ processing ? processingLabel : submitLabel }}
            </Button>
        </div>
    </div>
</template>
