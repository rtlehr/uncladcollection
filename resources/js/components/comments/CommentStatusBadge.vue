<script setup lang="ts">
const props = defineProps<{
    status: string;
}>();

function statusClasses(status: string): string {
    switch (status) {
        case 'approved':
            return 'border-green-200 bg-green-50 text-green-700';
        case 'pending':
            return 'border-yellow-200 bg-yellow-50 text-yellow-700';
        case 'hidden':
            return 'border-orange-200 bg-orange-50 text-orange-700';
        case 'spam':
            return 'border-red-200 bg-red-50 text-red-700';
        case 'deleted':
            return 'border-red-200 bg-red-50 text-red-700';
        default:
            return 'border-muted bg-muted text-muted-foreground';
    }
}

function label(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
        :class="statusClasses(props.status)"
    >
        {{ label(props.status) }}
    </span>
</template>