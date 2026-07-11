<script setup lang="ts">
import { Search, X } from '@lucide/vue';
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
        inputLabel?: string;
    }>(),
    {
        placeholder: 'Search...',
        searchLabel: 'Search',
        resetLabel: 'Reset',
        disabled: false,
        showReset: true,
        inputLabel: 'Search records',
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
    <div
        class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center"
        role="search"
    >
        <div class="relative min-w-0 flex-1">
            <label class="sr-only" :for="`search-${inputLabel.replace(/\s+/g, '-').toLowerCase()}`">
                {{ inputLabel }}
            </label>

            <Search
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />

            <Input
                :id="`search-${inputLabel.replace(/\s+/g, '-').toLowerCase()}`"
                v-model="value"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full pl-9 sm:min-w-64"
                @keyup.enter="emit('search')"
            />
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            <Button
                type="button"
                :disabled="disabled"
                @click="emit('search')"
            >
                <Search class="mr-2 h-4 w-4" aria-hidden="true" />
                {{ searchLabel }}
            </Button>

            <Button
                v-if="showReset"
                type="button"
                variant="outline"
                :disabled="disabled"
                @click="handleReset"
            >
                <X class="mr-2 h-4 w-4" aria-hidden="true" />
                {{ resetLabel }}
            </Button>

            <slot name="actions" />
        </div>
    </div>
</template>
