<script setup lang="ts">
import SidebarCard from '@/Components/Shared/SidebarCard.vue';

import type { NamedItem } from '@/types/common';

withDefaults(
    defineProps<{
        title: string;
        items: NamedItem[];
        emptyMessage?: string;
        prefix?: string;
    }>(),
    {
        emptyMessage: 'No items assigned.',
        prefix: '',
    },
);
</script>

<template>
    <SidebarCard :title="title">
        <div
            v-if="items.length"
            class="flex flex-wrap gap-2"
        >
            <span
                v-for="item in items"
                :key="item.id"
                class="rounded-full border px-3 py-1 text-sm"
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
    </SidebarCard>
</template>
