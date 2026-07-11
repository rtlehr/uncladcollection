<script setup lang="ts">
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import { computed } from 'vue';

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

const ariaSort = computed<'ascending' | 'descending' | 'none' | undefined>(() => {
    if (!props.sortable || !props.column) {
        return undefined;
    }

    if (props.currentSort !== props.column) {
        return 'none';
    }

    return props.currentDirection === 'asc'
        ? 'ascending'
        : 'descending';
});

const nextDirectionLabel = computed(() => {
    if (props.currentSort !== props.column) {
        return 'ascending';
    }

    return props.currentDirection === 'asc'
        ? 'descending'
        : 'ascending';
});

function handleSort() {
    if (props.sortable && props.column) {
        emit('sort', props.column);
    }
}
</script>

<template>
    <th
        scope="col"
        :aria-sort="ariaSort"
        :class="[
            'border-b border-border/70 bg-muted/35 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground',
            align === 'right' ? 'text-right' : 'text-left',
        ]"
    >
        <button
            v-if="sortable && column"
            type="button"
            :class="[
                'group inline-flex min-h-8 items-center gap-1.5 rounded-sm px-1 transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                align === 'right' ? 'ml-auto' : '',
            ]"
            :aria-label="`Sort ${label} ${nextDirectionLabel}`"
            @click="handleSort"
        >
            <span>{{ label }}</span>

            <ArrowUp
                v-if="currentSort === column && currentDirection === 'asc'"
                class="h-3.5 w-3.5 text-foreground"
                aria-hidden="true"
            />

            <ArrowDown
                v-else-if="currentSort === column && currentDirection === 'desc'"
                class="h-3.5 w-3.5 text-foreground"
                aria-hidden="true"
            />

            <ArrowUpDown
                v-else
                class="h-3.5 w-3.5 opacity-60 transition group-hover:opacity-100"
                aria-hidden="true"
            />
        </button>

        <span v-else>
            {{ label }}
        </span>
    </th>
</template>
