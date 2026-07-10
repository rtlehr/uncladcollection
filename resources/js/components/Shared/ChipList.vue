<script setup lang="ts">
import type { NamedItem } from '@/types/common';

withDefaults(
    defineProps<{
        items: NamedItem[];
        emptyMessage?: string;
        prefix?: string;
        size?: 'sm' | 'md';
    }>(),
    {
        emptyMessage: 'No items assigned.',
        prefix: '',
        size: 'md',
    },
);
</script>

<template>
    <div
        v-if="items.length"
        class="flex flex-wrap gap-2"
    >
        <span
            v-for="item in items"
            :key="item.id"
            :class="[
                'rounded-full border',
                size === 'sm'
                    ? 'px-2 py-0.5 text-xs'
                    : 'px-3 py-1 text-sm',
            ]"
        >
            {{ prefix }}{{ item.name }}
        </span>
    </div>

    <p
        v-else
        class="text-sm text-muted-foreground"
    >
        {{ emptyMessage }}
    </p>
</template>
