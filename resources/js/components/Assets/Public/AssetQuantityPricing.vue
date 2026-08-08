<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { PublicAssetPricingTier } from '@/types/publicAsset';

const props = defineProps<{ baseUnitPriceCents: number; configurationAdjustmentCents?: number; tiers: PublicAssetPricingTier[]; currency?: string; initialQuantity?: number }>();
const emit = defineEmits<{ change: [payload: { quantity: number; final_unit_price_cents: number; line_total_cents: number; active_tier_id: number | null }] }>();
const quantity = ref(Math.max(1, props.initialQuantity ?? 1));
const configuredUnit = computed(() => Math.max(0, props.baseUnitPriceCents + (props.configurationAdjustmentCents ?? 0)));
const activeTier = computed(() => props.tiers.find(t => quantity.value >= t.minimum_quantity && (t.maximum_quantity === null || quantity.value <= t.maximum_quantity)) ?? null);
const finalUnit = computed(() => {
    const tier = activeTier.value;

    if (!tier) {
return configuredUnit.value;
}

    if (tier.pricing_type === 'fixed_unit_price') {
return Math.min(configuredUnit.value, tier.unit_price_cents ?? configuredUnit.value);
}

    return Math.max(0, Math.round(configuredUnit.value * (1 - ((tier.percentage_off ?? 0) / 100))));
});
const lineTotal = computed(() => finalUnit.value * quantity.value);
const nextTier = computed(() => props.tiers.find(t => t.minimum_quantity > quantity.value) ?? null);
const price = (cents:number) => new Intl.NumberFormat('en-US',{style:'currency',currency:props.currency ?? 'USD'}).format(cents/100);
watch([quantity, finalUnit, lineTotal], () => emit('change',{quantity:quantity.value,final_unit_price_cents:finalUnit.value,line_total_cents:lineTotal.value,active_tier_id:activeTier.value?.id ?? null}), {immediate:true});
</script>

<template>
    <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Quantity</p><h2 class="mt-2 text-xl font-semibold">How many do you need?</h2></div><input v-model.number="quantity" type="number" min="1" class="h-11 w-24 rounded-xl border bg-transparent px-3 text-center font-semibold" /></div>
        <div v-if="tiers.length" class="mt-5"><h3 class="text-sm font-semibold">Buy more & save</h3><div class="mt-2 overflow-hidden rounded-xl border"><div v-for="tier in tiers" :key="tier.id" class="flex items-center justify-between border-b px-3 py-2 text-sm last:border-b-0" :class="activeTier?.id === tier.id ? 'bg-[var(--brand-primary)]/10 font-semibold' : ''"><span>{{ tier.minimum_quantity }}{{ tier.maximum_quantity ? `–${tier.maximum_quantity}` : '+' }}</span><span v-if="tier.pricing_type === 'fixed_unit_price'">{{ price(tier.unit_price_cents ?? 0) }} each</span><span v-else>{{ tier.percentage_off }}% off</span></div></div><p v-if="nextTier" class="mt-3 text-sm text-stone-500">Add {{ nextTier.minimum_quantity - quantity }} more to reach the next price break.</p></div>
        <div class="mt-5 flex items-end justify-between border-t pt-5"><div><p class="text-sm text-stone-500">{{ price(finalUnit) }} each</p><p v-if="activeTier" class="text-xs font-medium text-emerald-600">Quantity pricing applied</p></div><div class="text-right"><p class="text-sm text-stone-500">Line total</p><p class="text-2xl font-semibold">{{ price(lineTotal) }}</p></div></div>
    </section>
</template>
