<script setup lang="ts">
defineProps<{
    items: Array<{
        id: string;
        text: string;
        level: number;
    }>;
}>();
</script>

<template>
    <nav
        v-if="items.length"
        class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
        aria-label="Table of contents"
    >
        <h2 class="font-semibold">
            In This Article
        </h2>

        <ol class="mt-4 space-y-2 text-sm">
            <li
                v-for="item in items"
                :key="item.id"
                :class="item.level === 3 ? 'pl-4' : ''"
            >
                <a
                    :href="`#${item.id}`"
                    class="block leading-5 text-stone-600 transition hover:text-[var(--brand-accent)] dark:text-stone-400"
                >
                    {{ item.text }}
                </a>
            </li>
        </ol>
    </nav>
</template>
