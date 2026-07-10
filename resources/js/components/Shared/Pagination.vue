<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import type { PaginationLink } from '@/types/common';

withDefaults(
    defineProps<{
        links: PaginationLink[];
        from?: number | null;
        to?: number | null;
        total?: number | null;
        itemLabel?: string;
        showSummary?: boolean;
        preserveScroll?: boolean;
    }>(),
    {
        from: null,
        to: null,
        total: null,
        itemLabel: 'items',
        showSummary: false,
        preserveScroll: true,
    },
);
</script>

<template>
    <div v-if="links.length > 3" class="space-y-4">
        <p
            v-if="showSummary && total !== null"
            class="text-center text-sm text-muted-foreground"
        >
            Showing
            <span class="font-medium text-foreground">{{ from ?? 0 }}</span>
            –
            <span class="font-medium text-foreground">{{ to ?? 0 }}</span>
            of
            <span class="font-medium text-foreground">{{ total }}</span>
            {{ itemLabel }}
        </p>

        <nav aria-label="Pagination" class="flex flex-wrap justify-center gap-2">
            <Link
                v-for="link in links"
                :key="`${link.label}-${link.url}`"
                :href="link.url ?? '#'"
                :preserve-scroll="preserveScroll"
                class="rounded-md border px-3 py-2 text-sm transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="[
                    link.active
                        ? 'bg-primary text-primary-foreground hover:bg-primary'
                        : '',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
                :aria-current="link.active ? 'page' : undefined"
                :aria-disabled="!link.url ? 'true' : undefined"
                v-html="link.label"
            />
        </nav>
    </div>
</template>
