<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';

defineProps<{
    items: Array<{
        label: string;
        href?: string | null;
    }>;
}>();
</script>

<template>
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-stone-500 dark:text-stone-400">
            <li
                v-for="(item, index) in items"
                :key="`${item.label}-${index}`"
                class="flex items-center gap-2"
            >
                <ChevronRight v-if="index > 0" class="h-3.5 w-3.5" aria-hidden="true" />

                <Link
                    v-if="item.href && index !== items.length - 1"
                    :href="item.href"
                    class="hover:text-[var(--brand-accent)]"
                >
                    {{ item.label }}
                </Link>

                <span v-else :aria-current="index === items.length - 1 ? 'page' : undefined">
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>
