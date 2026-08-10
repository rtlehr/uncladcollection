<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';

import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import { Input } from '@/components/ui/input';
import { appDialogState, resolveAppDialog } from '@/lib/appDialogState';

const promptValue = ref('');
const promptInput = ref<any>(null);

watch(
    () => appDialogState.request,
    async (request) => {
        promptValue.value = request?.defaultValue ?? '';

        if (request?.mode === 'prompt') {
            await nextTick();
            const element = (promptInput.value as any)?.$el as HTMLInputElement | undefined;
            element?.focus();
            element?.select?.();
        }
    },
);

function confirm(): void {
    const request = appDialogState.request;
    if (!request) return;

    if (request.mode === 'prompt') {
        resolveAppDialog(promptValue.value);
        return;
    }

    resolveAppDialog(true);
}

function handleOpenChange(value: boolean): void {
    if (!value) cancel();
}

function cancel(): void {
    const request = appDialogState.request;
    if (!request) return;

    resolveAppDialog(request.mode === 'prompt' ? null : false);
}
</script>

<template>
    <ConfirmActionDialog
        v-if="appDialogState.request"
        :open="appDialogState.open"
        :title="appDialogState.request.title"
        :description="appDialogState.request.message"
        :confirm-label="appDialogState.request.confirmLabel"
        :cancel-label="appDialogState.request.cancelLabel"
        :destructive="appDialogState.request.destructive"
        :show-cancel="appDialogState.request.mode !== 'alert'"
        @update:open="handleOpenChange"
        @confirm="confirm"
    >
        <form
            v-if="appDialogState.request.mode === 'prompt'"
            class="space-y-2"
            @submit.prevent="confirm"
        >
            <Input
                ref="promptInput"
                v-model="promptValue"
                :type="appDialogState.request.inputType"
                :placeholder="appDialogState.request.placeholder"
                autocomplete="off"
            />
        </form>
    </ConfirmActionDialog>
</template>
