<script setup lang="ts">
import { Loader2Icon, TriangleAlert } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string | null;
        confirmLabel?: string;
        cancelLabel?: string;
        processingLabel?: string;
        destructive?: boolean;
        loading?: boolean;
        disabled?: boolean;
        showCancel?: boolean;
    }>(),
    {
        description: null,
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        processingLabel: 'Working...',
        destructive: false,
        loading: false,
        disabled: false,
        showCancel: true,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
    cancel: [];
}>();

function handleOpenChange(value: boolean): void {
    if (props.loading && !value) {
        return;
    }

    emit('update:open', value);

    if (!value) {
        emit('cancel');
    }
}

function handleConfirm(): void {
    if (!props.loading && !props.disabled) {
        emit('confirm');
    }
}

function handleCancel(): void {
    if (props.loading) {
        return;
    }

    emit('cancel');
    emit('update:open', false);
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="handleOpenChange"
    >
        <slot name="trigger" />

        <DialogContent
            class="sm:max-w-md"
            :aria-busy="loading || undefined"
            @escape-key-down="loading ? $event.preventDefault() : undefined"
            @pointer-down-outside="loading ? $event.preventDefault() : undefined"
        >
            <DialogHeader>
                <div
                    v-if="destructive"
                    class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-destructive/10 text-destructive"
                    aria-hidden="true"
                >
                    <TriangleAlert class="h-5 w-5" />
                </div>

                <DialogTitle>
                    {{ title }}
                </DialogTitle>

                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="$slots.default" class="py-2">
                <slot />
            </div>

            <p
                v-if="loading"
                class="sr-only"
                role="status"
                aria-live="assertive"
            >
                {{ processingLabel }}
            </p>

            <DialogFooter class="gap-2 sm:gap-2">
                <Button
                    v-if="showCancel"
                    type="button"
                    variant="outline"
                    :disabled="loading"
                    @click="handleCancel"
                >
                    {{ cancelLabel }}
                </Button>

                <Button
                    type="button"
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="disabled || loading"
                    :aria-busy="loading"
                    @click="handleConfirm"
                >
                    <Loader2Icon
                        v-if="loading"
                        class="h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />

                    {{ loading ? processingLabel : confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
