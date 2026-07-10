<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
}>();
</script>

<template>
    <div class="rounded-md border p-3">
        <div class="text-xs text-muted-foreground">
            {{ label }}
        </div>

        <div class="text-xl font-semibold">
            {{ value }}
        </div>
    </div>
</template>
