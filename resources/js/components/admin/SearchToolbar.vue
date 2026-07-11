<script setup lang="ts">
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        searchLabel?: string;
        resetLabel?: string;
        disabled?: boolean;
        showReset?: boolean;
    }>(),
    {
        placeholder: 'Search...',
        searchLabel: 'Search',
        resetLabel: 'Reset',
        disabled: false,
        showReset: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    search: [];
    reset: [];
}>();

const value = computed({
    get: () => props.modelValue,
    set: (nextValue: string) => emit('update:modelValue', nextValue),
});

function handleReset() {
    emit('update:modelValue', '');
    emit('reset');
}
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <Input
            v-model="value"
            :placeholder="placeholder"
            :disabled="disabled"
            class="sm:max-w-xl"
            @keyup.enter="emit('search')"
        />

        <div class="flex flex-wrap gap-2">
            <Button
                type="button"
                :disabled="disabled"
                @click="emit('search')"
            >
                {{ searchLabel }}
            </Button>

            <Button
                v-if="showReset"
                type="button"
                variant="outline"
                :disabled="disabled"
                @click="handleReset"
            >
                {{ resetLabel }}
            </Button>

            <slot name="actions" />
        </div>
    </div>
</template>
