<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';

defineProps<{
    previousImage?: {
        title: string;
        slug: string;
    } | null;
    nextImage?: {
        title: string;
        slug: string;
    } | null;
}>();
</script>

<template>
    <nav
        v-if="previousImage || nextImage"
        class="grid gap-3 sm:grid-cols-2"
        aria-label="Image navigation"
    >
        <Link
            v-if="previousImage"
            :href="`/images/${previousImage.slug}`"
            class="group flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
        >
            <ChevronLeft class="h-5 w-5 shrink-0 text-stone-400 transition group-hover:text-[var(--brand-accent)]" />

            <div class="min-w-0">
                <div class="text-xs uppercase tracking-wider text-stone-500">
                    Previous
                </div>

                <div class="truncate font-semibold">
                    {{ previousImage.title }}
                </div>
            </div>
        </Link>

        <div v-else />

        <Link
            v-if="nextImage"
            :href="`/images/${nextImage.slug}`"
            class="group flex items-center justify-end gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-right transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="min-w-0">
                <div class="text-xs uppercase tracking-wider text-stone-500">
                    Next
                </div>

                <div class="truncate font-semibold">
                    {{ nextImage.title }}
                </div>
            </div>

            <ChevronRight class="h-5 w-5 shrink-0 text-stone-400 transition group-hover:text-[var(--brand-accent)]" />
        </Link>
    </nav>
</template>
