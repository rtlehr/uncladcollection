<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import type { PublicAssetConfigurationGroup } from '@/types/publicAsset';

const props = defineProps<{ groups: PublicAssetConfigurationGroup[]; basePriceCents: number; currency?: string }>();
const emit = defineEmits<{ change: [payload: { selections: Record<string, unknown>; adjustment_cents: number; total_price_cents: number }] }>();
const selections = reactive<Record<string, any>>({});

for (const group of props.groups) {
    if (group.display_type === 'checkbox') selections[group.code] = group.values.filter(v => v.is_default).map(v => v.value);
    else if (group.display_type === 'text' || group.display_type === 'number') selections[group.code] = '';
    else selections[group.code] = group.values.find(v => v.is_default)?.value ?? '';
}

const adjustment = computed(() => props.groups.reduce((total, group) => {
    const selected = Array.isArray(selections[group.code]) ? selections[group.code] : [selections[group.code]];
    return total + group.values.filter(v => selected.includes(v.value)).reduce((sum, value) => sum + value.price_adjustment_cents, 0);
}, 0));
const total = computed(() => Math.max(0, props.basePriceCents + adjustment.value));
const price = (cents: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: props.currency ?? 'USD' }).format(cents / 100);
watch([() => ({ ...selections }), adjustment, total], () => emit('change', { selections: { ...selections }, adjustment_cents: adjustment.value, total_price_cents: total.value }), { deep: true, immediate: true });
</script>

<template>
    <section v-if="groups.length" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Configure your purchase</p>
        <h2 class="mt-2 text-xl font-semibold">Choose your options</h2>
        <div class="mt-6 space-y-6">
            <fieldset v-for="group in groups" :key="group.id" class="space-y-3">
                <legend class="font-semibold">{{ group.name }} <span v-if="group.is_required" class="text-red-500">*</span></legend>
                <p v-if="group.help_text" class="text-sm text-stone-500">{{ group.help_text }}</p>
                <select v-if="group.display_type === 'select'" v-model="selections[group.code]" class="h-11 w-full rounded-xl border bg-transparent px-3"><option value="">{{ group.placeholder || 'Choose an option' }}</option><option v-for="value in group.values" :key="value.id" :value="value.value">{{ value.label }}{{ value.price_adjustment_cents ? ` (${value.price_adjustment_cents > 0 ? '+' : ''}${price(value.price_adjustment_cents)})` : '' }}</option></select>
                <div v-else-if="group.display_type === 'radio'" class="grid gap-2 sm:grid-cols-2"><label v-for="value in group.values" :key="value.id" class="flex cursor-pointer items-center justify-between rounded-xl border p-3"><span class="flex items-center gap-2"><input v-model="selections[group.code]" type="radio" :value="value.value" />{{ value.label }}</span><span v-if="value.price_adjustment_cents" class="text-sm text-stone-500">{{ value.price_adjustment_cents > 0 ? '+' : '' }}{{ price(value.price_adjustment_cents) }}</span></label></div>
                <div v-else-if="group.display_type === 'checkbox'" class="grid gap-2 sm:grid-cols-2"><label v-for="value in group.values" :key="value.id" class="flex cursor-pointer items-center justify-between rounded-xl border p-3"><span class="flex items-center gap-2"><input v-model="selections[group.code]" type="checkbox" :value="value.value" />{{ value.label }}</span><span v-if="value.price_adjustment_cents" class="text-sm text-stone-500">{{ value.price_adjustment_cents > 0 ? '+' : '' }}{{ price(value.price_adjustment_cents) }}</span></label></div>
                <div v-else-if="group.display_type === 'color_swatch'" class="flex flex-wrap gap-3"><label v-for="value in group.values" :key="value.id" class="cursor-pointer"><input v-model="selections[group.code]" type="radio" :value="value.value" class="peer sr-only" /><span class="flex min-w-20 items-center gap-2 rounded-xl border p-2 peer-checked:ring-2 peer-checked:ring-[var(--brand-primary)]"><span class="h-7 w-7 rounded-full border" :style="{ backgroundColor: value.swatch_color || '#ddd' }" />{{ value.label }}</span></label></div>
                <input v-else-if="group.display_type === 'number'" v-model="selections[group.code]" type="number" :min="group.minimum_value ?? undefined" :max="group.maximum_value ?? undefined" :step="group.step_value ?? 1" :placeholder="group.placeholder || undefined" class="h-11 w-full rounded-xl border bg-transparent px-3" />
                <input v-else v-model="selections[group.code]" type="text" :placeholder="group.placeholder || undefined" class="h-11 w-full rounded-xl border bg-transparent px-3" />
            </fieldset>
        </div>
        <div class="mt-6 flex items-end justify-between border-t pt-5"><div><p class="text-sm text-stone-500">Configured price</p><p v-if="adjustment" class="text-xs text-stone-500">Includes {{ adjustment > 0 ? '+' : '' }}{{ price(adjustment) }} in options</p></div><p class="text-2xl font-semibold">{{ price(total) }}</p></div>
    </section>
</template>
