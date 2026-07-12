<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';

defineProps<{
    previousPost?: {
        title: string;
        slug: string;
    } | null;
    nextPost?: {
        title: string;
        slug: string;
    } | null;
}>();
</script>

<template>
    <nav
        v-if="previousPost || nextPost"
        class="grid gap-3 sm:grid-cols-2"
        aria-label="Article navigation"
    >
        <Link
            v-if="previousPost"
            :href="`/blog/${previousPost.slug}`"
            class="group flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
        >
            <ChevronLeft class="h-5 w-5 shrink-0 text-stone-400 group-hover:text-[var(--brand-accent)]" />

            <div class="min-w-0">
                <div class="text-xs uppercase tracking-wider text-stone-500">
                    Previous article
                </div>

                <div class="line-clamp-2 font-semibold">
                    {{ previousPost.title }}
                </div>
            </div>
        </Link>

        <div v-else />

        <Link
            v-if="nextPost"
            :href="`/blog/${nextPost.slug}`"
            class="group flex items-center justify-end gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-right transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="min-w-0">
                <div class="text-xs uppercase tracking-wider text-stone-500">
                    Next article
                </div>

                <div class="line-clamp-2 font-semibold">
                    {{ nextPost.title }}
                </div>
            </div>

            <ChevronRight class="h-5 w-5 shrink-0 text-stone-400 group-hover:text-[var(--brand-accent)]" />
        </Link>
    </nav>
</template>
