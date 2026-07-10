<script setup lang="ts">
defineProps<{
    label: string;
    value?: string | number | null;
    fallback?: string;
    breakAll?: boolean;
}>();
</script>

<template>
    <div>
        <div class="text-sm font-medium text-muted-foreground">
            {{ label }}
        </div>

        <div
            :class="[
                'mt-1',
                breakAll ? 'break-all text-sm' : '',
            ]"
        >
            {{ value ?? fallback ?? '—' }}
        </div>
    </div>
</template>
