<script setup lang="ts">
defineProps<{
    from: number | null;
    to: number | null;
    total: number;
    itemLabel: string;
    filtered?: boolean;
}>();
</script>

<template>
    <div>
        <p class="text-sm text-stone-500 dark:text-stone-400" aria-live="polite">
            Showing {{ from ?? 0 }}–{{ to ?? 0 }} of
            {{ Number(total ?? 0).toLocaleString() }}
            {{ itemLabel }}
        </p>

        <h2 class="mt-1 text-2xl font-semibold tracking-tight">
            {{ filtered ? 'Filtered results' : 'Explore all results' }}
        </h2>
    </div>
</template>
