<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { AdminAssetPricingTier } from '@/types/adminAsset';

const model = defineModel<AdminAssetPricingTier[]>({ required: true });
const props = defineProps<{ currency?: string }>();

function addTier(): void {
    const last = model.value.at(-1);
    model.value.push({
        id: null,
        minimum_quantity: last?.maximum_quantity ? last.maximum_quantity + 1 : (last ? last.minimum_quantity + 1 : 2),
        maximum_quantity: null,
        pricing_type: 'fixed_unit_price',
        unit_price_cents: 0,
        percentage_off: null,
        currency: props.currency ?? 'USD',
        is_active: true,
    });
}

function dollars(cents: number | null): string {
 return ((cents ?? 0) / 100).toFixed(2); 
}
function updateDollars(tier: AdminAssetPricingTier, event: Event): void {
    const value = Number((event.target as HTMLInputElement).value);
    tier.unit_price_cents = Number.isFinite(value) ? Math.max(0, Math.round(value * 100)) : 0;
}
</script>

<template>
    <div class="rounded-xl border bg-muted/15 p-4">
        <div class="flex items-start justify-between gap-4">
            <div><h4 class="font-medium">Quantity pricing</h4><p class="mt-1 text-sm text-muted-foreground">Apply price breaks across all configured lines for this asset and offering.</p></div>
            <Button type="button" size="sm" variant="outline" @click="addTier">Add Tier</Button>
        </div>
        <div v-if="model.length" class="mt-4 space-y-3">
            <div v-for="(tier,index) in model" :key="tier.id ?? index" class="grid gap-3 rounded-lg border bg-background p-3 lg:grid-cols-[110px_110px_180px_1fr_auto] lg:items-end">
                <label class="space-y-1 text-sm"><span>From</span><input v-model.number="tier.minimum_quantity" type="number" min="1" class="h-10 w-full rounded-md border px-3" /></label>
                <label class="space-y-1 text-sm"><span>To</span><input v-model.number="tier.maximum_quantity" type="number" :min="tier.minimum_quantity" placeholder="No limit" class="h-10 w-full rounded-md border px-3" /></label>
                <label class="space-y-1 text-sm"><span>Adjustment</span><select v-model="tier.pricing_type" class="h-10 w-full rounded-md border px-3"><option value="fixed_unit_price">Fixed unit price</option><option value="percentage_off">Percentage off</option></select></label>
                <label v-if="tier.pricing_type === 'fixed_unit_price'" class="space-y-1 text-sm"><span>Unit price</span><div class="relative"><span class="absolute inset-y-0 left-3 flex items-center text-muted-foreground">$</span><input :value="dollars(tier.unit_price_cents)" type="number" min="0" step="0.01" class="h-10 w-full rounded-md border pl-7 pr-3" @input="updateDollars(tier,$event)" /></div></label>
                <label v-else class="space-y-1 text-sm"><span>Discount percent</span><div class="relative"><input v-model.number="tier.percentage_off" type="number" min="0" max="100" step="0.01" class="h-10 w-full rounded-md border px-3 pr-8" /><span class="absolute inset-y-0 right-3 flex items-center text-muted-foreground">%</span></div></label>
                <Button type="button" size="sm" variant="destructive" @click="model.splice(index,1)">Remove</Button>
            </div>
        </div>
        <p v-else class="mt-4 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">No quantity discounts configured. Standard offering pricing applies at every quantity.</p>
    </div>
</template>
