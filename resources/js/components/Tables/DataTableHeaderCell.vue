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
    if (props.sortable && props.column) {
        emit('sort', props.column);
    }
}
</script>

<template>
    <th
        scope="col"
        :class="[
            'border-b border-border/70 bg-muted/35 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground',
            align === 'right' ? 'text-right' : 'text-left',
        ]"
    >
        <button
            v-if="sortable && column"
            type="button"
            :class="[
                'group inline-flex items-center gap-1.5 rounded-sm transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                align === 'right' ? 'ml-auto' : '',
            ]"
            :aria-label="`Sort by ${label}`"
            @click="handleSort"
        >
            <span>{{ label }}</span>

            <ArrowUp
                v-if="currentSort === column && currentDirection === 'asc'"
                class="h-3.5 w-3.5 text-foreground"
            />

            <ArrowDown
                v-else-if="currentSort === column && currentDirection === 'desc'"
                class="h-3.5 w-3.5 text-foreground"
            />

            <ArrowUpDown
                v-else
                class="h-3.5 w-3.5 opacity-60 transition group-hover:opacity-100"
            />
        </button>

        <span v-else>
            {{ label }}
        </span>
    </th>
</template>
