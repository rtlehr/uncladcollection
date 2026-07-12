<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps<{
    items: Array<{
        title: string;
        description?: string | null;
        href: string;
    }>;
}>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            class="group rounded-2xl border border-stone-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-stone-800 dark:bg-stone-900"
        >
            <h3 class="font-semibold transition group-hover:text-[var(--brand-accent)]">
                {{ item.title }}
            </h3>

            <p v-if="item.description" class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
                {{ item.description }}
            </p>

            <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]">
                Learn more
                <ArrowRight class="h-4 w-4" />
            </div>
        </Link>
    </div>
</template>
