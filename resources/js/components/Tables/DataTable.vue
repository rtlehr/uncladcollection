<script setup lang="ts">
withDefaults(
    defineProps<{
        minWidth?: string;
        stickyHeader?: boolean;
        compact?: boolean;
    }>(),
    {
        minWidth: '900px',
        stickyHeader: false,
        compact: false,
    },
);
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-sm">
        <div class="overflow-x-auto">
            <table
                :class="[
                    'w-full border-collapse text-sm',
                    compact ? '[&_td]:py-2.5 [&_th]:py-2.5' : '',
                    stickyHeader
                        ? '[&_thead]:sticky [&_thead]:top-0 [&_thead]:z-10'
                        : '',
                ]"
                :style="{ minWidth }"
            >
                <slot />
            </table>
        </div>
    </div>
</template>
