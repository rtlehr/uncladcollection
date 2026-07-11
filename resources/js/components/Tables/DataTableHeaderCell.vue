<script setup lang="ts">
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        label: string;
        column?: string;
        currentSort?: string;
        currentDirection?: 'asc' | 'desc';
        align?: 'left' | 'right';
        sortable?: boolean;
    }>(),
    {
        column: undefined,
        currentSort: undefined,
        currentDirection: 'asc',
        align: 'left',
        sortable: false,
    },
);

const emit = defineEmits<{
    sort: [column: string];
}>();

function handleSort() {
    if (!props.sortable || !props.column) {
        return;
    }

    emit('sort', props.column);
}
</script>

<template>
    <th
        :class="[
            'p-4 font-medium',
            align === 'right' ? 'text-right' : 'text-left',
        ]"
    >
        <button
            v-if="sortable && column"
            type="button"
            class="inline-flex items-center gap-1.5 transition hover:text-foreground"
            @click="handleSort"
        >
            <span>{{ label }}</span>

            <ArrowUp
                v-if="currentSort === column && currentDirection === 'asc'"
                class="h-3.5 w-3.5"
            />

            <ArrowDown
                v-else-if="currentSort === column && currentDirection === 'desc'"
                class="h-3.5 w-3.5"
            />

            <ArrowUpDown
                v-else
                class="h-3.5 w-3.5 text-muted-foreground"
            />
        </button>

        <span v-else>
            {{ label }}
        </span>
    </th>
</template>
