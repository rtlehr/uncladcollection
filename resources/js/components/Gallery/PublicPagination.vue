<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

import type { PaginatedGalleryImages } from '@/types/gallery';

defineProps<{
    pagination: PaginatedGalleryImages;
}>();
</script>

<template>
    <nav
        v-if="pagination.last_page > 1"
        class="flex flex-col items-center justify-between gap-4 border-t border-stone-200 pt-8 sm:flex-row dark:border-stone-800"
        aria-label="Gallery pagination"
    >
        <p class="text-sm text-stone-500 dark:text-stone-400">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </p>

        <div class="flex items-center gap-2">
            <Link
                :href="pagination.prev_page_url ?? '#'"
                :class="[
                    'inline-flex h-11 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold dark:border-stone-700',
                    !pagination.prev_page_url ? 'pointer-events-none opacity-40' : '',
                ]"
                :aria-disabled="!pagination.prev_page_url"
                preserve-scroll
            >
                <ChevronLeft class="h-4 w-4" />
                Previous
            </Link>

            <Link
                :href="pagination.next_page_url ?? '#'"
                :class="[
                    'inline-flex h-11 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white',
                    !pagination.next_page_url ? 'pointer-events-none opacity-40' : '',
                ]"
                :aria-disabled="!pagination.next_page_url"
                preserve-scroll
            >
                Next
                <ChevronRight class="h-4 w-4" />
            </Link>
        </div>
    </nav>
</template>
