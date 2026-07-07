<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    date: string | Date | null;
}>();

const relativeTime = computed(() => {
    if (!props.date) {
        return '';
    }

    const date = new Date(props.date);
    const now = new Date();

    const diffSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffSeconds < 60) {
        return 'just now';
    }

    const diffMinutes = Math.floor(diffSeconds / 60);

    if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`;
    }

    const diffHours = Math.floor(diffMinutes / 60);

    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    }

    const diffDays = Math.floor(diffHours / 24);

    if (diffDays === 1) {
        return 'yesterday';
    }

    if (diffDays < 7) {
        return `${diffDays} days ago`;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
    });
});
</script>

<template>
    <span :title="date ? new Date(date).toLocaleString() : ''">
        {{ relativeTime }}
    </span>
</template>