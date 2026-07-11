<script setup lang="ts">
withDefaults(
    defineProps<{
        minWidth?: string;
        stickyHeader?: boolean;
        compact?: boolean;
        caption?: string;
    }>(),
    {
        minWidth: '900px',
        stickyHeader: false,
        compact: false,
        caption: undefined,
    },
);
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-sm"
        role="region"
        :aria-label="caption ?? 'Data table'"
        tabindex="0"
    >
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
                <caption v-if="caption" class="sr-only">
                    {{ caption }}
                </caption>

                <slot />
            </table>
        </div>
    </div>
</template>
