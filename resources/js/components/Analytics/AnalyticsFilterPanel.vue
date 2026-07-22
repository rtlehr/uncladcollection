<script setup lang="ts">
withDefaults(defineProps<{ title?: string; description?: string; contentClass?: string }>(), {
    title: 'Report filters',
    description: 'Refine the reporting period and results shown below.',
    contentClass: 'grid gap-4 lg:grid-cols-4 lg:items-end',
});

defineEmits<{ submit: [] }>();
</script>

<template>
    <section class="analytics-filter-panel rounded-xl border bg-background shadow-sm" aria-labelledby="analytics-filter-title">
        <div class="border-b px-4 py-3">
            <h2 id="analytics-filter-title" class="text-sm font-semibold">{{ title }}</h2>
            <p class="mt-0.5 text-xs text-muted-foreground">{{ description }}</p>
        </div>
        <form :class="['p-4', contentClass]" @submit.prevent="$emit('submit')">
            <slot />
        </form>
    </section>
</template>
