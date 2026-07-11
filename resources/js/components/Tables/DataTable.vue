<script setup lang="ts">
withDefaults(
    defineProps<{
        minWidth?: string;
    }>(),
    {
        minWidth: '900px',
    },
);
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-card shadow-sm">
        <div class="overflow-x-auto">
            <table
                class="w-full text-sm"
                :style="{ minWidth }"
            >
                <slot />
            </table>
        </div>
    </div>
</template>
