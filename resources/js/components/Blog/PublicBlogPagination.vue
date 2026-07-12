<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';

defineProps<{
    currentPage: number;
    lastPage: number;
    previousUrl: string | null;
    nextUrl: string | null;
}>();
</script>

<template>
    <nav
        v-if="lastPage > 1"
        class="flex flex-col items-center justify-between gap-4 border-t border-stone-200 pt-8 sm:flex-row dark:border-stone-800"
        aria-label="Blog pagination"
    >
        <p class="text-sm text-stone-500 dark:text-stone-400">
            Page {{ currentPage }} of {{ lastPage }}
        </p>

        <div class="flex gap-2">
            <Link
                :href="previousUrl ?? '#'"
                :class="[
                    'inline-flex h-11 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold dark:border-stone-700',
                    !previousUrl ? 'pointer-events-none opacity-40' : '',
                ]"
            >
                <ChevronLeft class="h-4 w-4" />
                Previous
            </Link>

            <Link
                :href="nextUrl ?? '#'"
                :class="[
                    'inline-flex h-11 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white',
                    !nextUrl ? 'pointer-events-none opacity-40' : '',
                ]"
            >
                Next
                <ChevronRight class="h-4 w-4" />
            </Link>
        </div>
    </nav>
</template>
