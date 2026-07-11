<script setup lang="ts">
import PageHeader from '@/Components/Shared/PageHeader.vue';

withDefaults(defineProps<{
    title: string;
    description?: string | null;
    eyebrow?: string | null;
}>(), {
    description: null,
    eyebrow: null,
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <PageHeader
            :title="title"
            :description="description"
            :eyebrow="eyebrow ?? undefined"
        />

        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
