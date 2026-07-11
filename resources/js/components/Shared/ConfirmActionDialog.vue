<script setup lang="ts">
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string | null;
        confirmLabel?: string;
        cancelLabel?: string;
        destructive?: boolean;
        loading?: boolean;
        disabled?: boolean;
    }>(),
    {
        description: null,
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        destructive: false,
        loading: false,
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
    cancel: [];
}>();

function handleConfirm() {
    emit('confirm');
}

function handleCancel() {
    emit('cancel');
    emit('update:open', false);
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <slot name="trigger" />

        <DialogContent class="sm:max-w-md">
            <DialogHeader>
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

            <DialogFooter class="gap-2 sm:gap-0">
                <DialogClose as-child>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="loading"
                        @click="handleCancel"
                    >
                        {{ cancelLabel }}
                    </Button>
                </DialogClose>

                <Button
                    type="button"
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="disabled || loading"
                    @click="handleConfirm"
                >
                    {{ loading ? 'Working...' : confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
