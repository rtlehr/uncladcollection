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
    <details
        v-if="items.length"
        class="group rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900 lg:hidden"
    >
        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 font-semibold [&::-webkit-details-marker]:hidden">
            In This Article

            <span
                class="text-xl leading-none transition group-open:rotate-45"
                aria-hidden="true"
            >
                +
            </span>
        </summary>

        <nav
            class="border-t border-stone-200 px-5 py-4 dark:border-stone-800"
            aria-label="Table of contents"
        >
            <ol class="space-y-2 text-sm">
                <li
                    v-for="item in items"
                    :key="item.id"
                    :class="item.level === 3 ? 'pl-4' : ''"
                >
                    <a
                        :href="`#${item.id}`"
                        class="block min-h-10 py-2 leading-5 text-stone-600 dark:text-stone-400"
                    >
                        {{ item.text }}
                    </a>
                </li>
            </ol>
        </nav>
    </details>

    <nav
        v-if="items.length"
        class="hidden rounded-2xl border border-stone-200 bg-white p-5 lg:block dark:border-stone-800 dark:bg-stone-900"
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
