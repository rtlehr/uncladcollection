<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { PaginationLink } from '@/types/common';

const props = withDefaults(
    defineProps<{
        links: PaginationLink[];
        from?: number | null;
        to?: number | null;
        total?: number | null;
        itemLabel?: string;
        showSummary?: boolean;
        preserveScroll?: boolean;
        preserveState?: boolean;
    }>(),
    {
        from: null,
        to: null,
        total: null,
        itemLabel: 'items',
        showSummary: false,
        preserveScroll: true,
        preserveState: true,
    },
);

function plainLabel(label: string): string {
    return label
        .replace(/&laquo;/gi, 'Previous')
        .replace(/&raquo;/gi, 'Next')
        .replace(/<[^>]*>/g, '')
        .trim();
}

function ariaLabel(link: PaginationLink): string {
    const label = plainLabel(link.label);

    if (link.active) {
        return `Page ${label}, current page`;
    }

    if (/^\d+$/.test(label)) {
        return `Go to page ${label}`;
    }

    return label;
}

const summaryText = computed(() => {
    if (!props.showSummary || props.total === null) {
        return '';
    }

    return `Showing ${props.from ?? 0} through ${props.to ?? 0} of ${props.total} ${props.itemLabel}`;
});
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p
            v-if="showSummary && total !== null"
            class="text-sm text-muted-foreground"
            role="status"
            aria-live="polite"
        >
            <span class="sr-only">{{ summaryText }}</span>

            <span aria-hidden="true">
                Showing
                <span class="font-medium text-foreground">{{ from ?? 0 }}</span>
                –
                <span class="font-medium text-foreground">{{ to ?? 0 }}</span>
                of
                <span class="font-medium text-foreground">{{ total }}</span>
                {{ itemLabel }}
            </span>
        </p>

        <nav
            aria-label="Pagination"
            class="flex flex-wrap items-center justify-center gap-1 sm:ml-auto"
        >
            <Link
                v-for="link in links"
                :key="`${link.label}-${link.url}`"
                :href="link.url ?? '#'"
                :preserve-scroll="preserveScroll"
                :preserve-state="preserveState"
                :class="[
                    'inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border px-3 py-1.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                        : 'border-border bg-background hover:bg-muted',
                    !link.url
                        ? 'pointer-events-none opacity-45'
                        : '',
                ]"
                :aria-label="ariaLabel(link)"
                :aria-current="link.active ? 'page' : undefined"
                :aria-disabled="!link.url ? 'true' : undefined"
                v-html="link.label"
            />
        </nav>
    </div>
</template>
