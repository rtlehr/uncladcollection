<script setup lang="ts">
withDefaults(
    defineProps<{
        items: Array<{
            question: string;
            answer: string;
        }>;
        allowMultiple?: boolean;
    }>(),
    {
        allowMultiple: true,
    },
);

function handleToggle(
    event: Event,
    index: number,
): void {
    const current = event.currentTarget as HTMLDetailsElement;

    if (!current.open) {
        return;
    }

    if (index < 0) {
        return;
    }
}
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
    >
        <details
            v-for="(item, index) in items"
            :key="item.question"
            class="group border-b border-stone-200 last:border-b-0 dark:border-stone-800"
            @toggle="handleToggle($event, index)"
        >
            <summary
                class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-left text-base font-semibold outline-none transition hover:bg-stone-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[var(--brand-accent)] dark:hover:bg-stone-800/60 [&::-webkit-details-marker]:hidden"
            >
                <span>
                    {{ item.question }}
                </span>

                <span
                    class="relative h-5 w-5 shrink-0 text-stone-500 transition-transform duration-200 group-open:rotate-45"
                    aria-hidden="true"
                >
                    <span
                        class="absolute left-1/2 top-1/2 h-0.5 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-current"
                    />

                    <span
                        class="absolute left-1/2 top-1/2 h-4 w-0.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-current"
                    />
                </span>
            </summary>

            <div class="px-5 pb-5">
                <p class="text-sm leading-7 text-stone-600 dark:text-stone-400">
                    {{ item.answer }}
                </p>
            </div>
        </details>
    </div>
</template>
