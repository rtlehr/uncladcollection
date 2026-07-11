<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';

type Option = {
    id: number;
    name: string;
};

defineProps<{
    options: Option[];
    selectedIds: number[];
    emptyMessage?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    toggle: [id: number, checked: boolean];
}>();
</script>

<template>
    <div
        v-if="options.length"
        class="grid gap-3 md:grid-cols-2"
    >
        <label
            v-for="option in options"
            :key="option.id"
            class="flex items-center gap-3 rounded-md border p-3 transition hover:bg-muted/30"
        >
            <Checkbox
                :checked="selectedIds.includes(option.id)"
                :disabled="disabled"
                @update:checked="emit('toggle', option.id, Boolean($event))"
            />

            <span class="text-sm font-medium">
                {{ option.name }}
            </span>
        </label>
    </div>

    <p
        v-else
        class="text-sm text-muted-foreground"
    >
        {{ emptyMessage ?? 'No options available.' }}
    </p>
</template>
