<script setup lang="ts">
import { Download, PackageCheck, PackageOpen } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    type: 'digital' | 'physical' | 'hybrid';
    size?: 'sm' | 'md';
}>(), { size: 'sm' });

const label = computed(() => ({
    digital: 'Digital download',
    physical: 'Ships to you',
    hybrid: 'Digital + physical',
})[props.type]);

const icon = computed(() => ({
    digital: Download,
    physical: PackageCheck,
    hybrid: PackageOpen,
})[props.type]);
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full border font-semibold',
            size === 'md' ? 'gap-2 px-3 py-1.5 text-sm' : 'gap-1.5 px-2.5 py-1 text-xs',
            type === 'digital'
                ? 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-300'
                : type === 'physical'
                    ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200'
                    : 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/60 dark:bg-violet-950/30 dark:text-violet-200',
        ]"
    >
        <component :is="icon" class="h-3.5 w-3.5" />
        {{ label }}
    </span>
</template>
