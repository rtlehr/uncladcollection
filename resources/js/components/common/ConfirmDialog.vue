<script setup lang="ts">
import { Button } from '@/components/ui/button';

withDefaults(defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    confirmVariant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    loading?: boolean;
}>(), {
    title: 'Are you sure?',
    description: 'This action cannot be undone.',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    confirmVariant: 'destructive',
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

function close() {
    emit('update:open', false);
}

function confirm() {
    emit('confirm');
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg">
                <h2 class="text-lg font-semibold">
                    {{ title }}
                </h2>

                <p class="mt-2 text-sm text-muted-foreground">
                    {{ description }}
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="close"
                    >
                        {{ cancelText }}
                    </Button>

                    <Button
                        type="button"
                        :variant="confirmVariant"
                        :disabled="loading"
                        @click="confirm"
                    >
                        {{ loading ? 'Working...' : confirmText }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>