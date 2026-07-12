<script setup lang="ts">
import { X } from '@lucide/vue';

import type { PublicActiveFilter } from '@/types/publicSearch';

defineProps<{
    items: PublicActiveFilter[];
}>();

const emit = defineEmits<{
    remove: [key: string];
    clear: [];
}>();
</script>

<template>
    <div
        v-if="items.length"
        class="flex flex-wrap items-center gap-2"
        aria-label="Active filters"
    >
        <span class="text-sm text-stone-500 dark:text-stone-400">
            Active filters:
        </span>

        <button
            v-for="item in items"
            :key="item.key"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full bg-stone-200 px-3 py-1.5 text-xs font-medium transition hover:bg-stone-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-accent)] dark:bg-stone-800 dark:hover:bg-stone-700"
            @click="emit('remove', item.key)"
        >
            {{ item.label }}
            <X class="h-3.5 w-3.5" />
        </button>

        <button
            type="button"
            class="text-xs font-semibold text-[var(--brand-accent)] underline-offset-4 hover:underline"
            @click="emit('clear')"
        >
            Clear all
        </button>
    </div>
</template>
