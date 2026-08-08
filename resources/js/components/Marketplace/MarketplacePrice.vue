<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    priceCents: number | null;
    currency?: string;
    compact?: boolean;
}>(), {
    currency: 'USD',
    compact: false,
});

const formattedPrice = computed(() => {
    if (props.priceCents === null) {
return null;
}

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 2,
    }).format(props.priceCents / 100);
});
</script>

<template>
    <div v-if="formattedPrice" :class="compact ? 'text-right' : ''">
        <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-stone-400">
            From
        </span>
        <span class="block font-semibold leading-tight text-stone-900 dark:text-white" :class="compact ? 'text-sm' : 'text-lg'">
            {{ formattedPrice }}
        </span>
    </div>
    <span v-else class="text-xs font-medium text-stone-500 dark:text-stone-400">
        Pricing coming soon
    </span>
</template>
