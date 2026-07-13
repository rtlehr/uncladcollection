<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { AdminAssetConfigurationGroup, ConfigurationDisplayTypeOption } from '@/types/adminAsset';

const model = defineModel<AdminAssetConfigurationGroup[]>({ required: true });
const props = defineProps<{ displayTypes: ConfigurationDisplayTypeOption[] }>();

const emptyGroup = (): AdminAssetConfigurationGroup => ({
    id: null, name: '', code: '', display_type: 'select', is_required: false,
    allows_multiple: false, placeholder: '', help_text: '', minimum_value: null,
    maximum_value: null, step_value: null, is_active: true, values: [],
});

function addGroup(): void { model.value.push(emptyGroup()); }
function removeGroup(index: number): void { model.value.splice(index, 1); }
function addValue(group: AdminAssetConfigurationGroup): void {
    group.values.push({ id: null, label: '', value: '', description: '', swatch_color: '#000000', image_path: null, is_default: false, is_active: true, price_adjustment_cents: 0, currency: 'USD' });
}
function removeValue(group: AdminAssetConfigurationGroup, index: number): void { group.values.splice(index, 1); }
function usesValues(type: string): boolean { return !['text', 'number'].includes(type); }
function money(value: number): string { return `${value < 0 ? '-' : '+'}$${Math.abs(value / 100).toFixed(2)}`; }
</script>

<template>
    <div class="space-y-5">
        <div v-if="!model.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
            No product configuration groups yet. Add Size, Color, Resolution, Personalization, or another customer choice.
        </div>

        <article v-for="(group, groupIndex) in model" :key="group.id ?? `new-${groupIndex}`" class="rounded-2xl border bg-background p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Configuration {{ groupIndex + 1 }}</p>
                    <h3 class="mt-1 text-lg font-semibold">{{ group.name || 'Untitled option' }}</h3>
                </div>
                <Button type="button" variant="destructive" size="sm" @click="removeGroup(groupIndex)">Remove</Button>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-1.5 text-sm"><span class="font-medium">Name</span><Input v-model="group.name" placeholder="Size" /></label>
                <label class="space-y-1.5 text-sm"><span class="font-medium">Internal code</span><Input v-model="group.code" placeholder="Generated from name" /></label>
                <label class="space-y-1.5 text-sm"><span class="font-medium">Display type</span><select v-model="group.display_type" class="h-10 w-full rounded-md border bg-background px-3"><option v-for="option in props.displayTypes" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label class="space-y-1.5 text-sm"><span class="font-medium">Placeholder</span><Input v-model="group.placeholder" placeholder="Choose an option" /></label>
            </div>

            <label class="mt-4 block space-y-1.5 text-sm"><span class="font-medium">Help text</span><textarea v-model="group.help_text" rows="2" class="w-full rounded-md border bg-background px-3 py-2" placeholder="Explain this selection to the customer." /></label>

            <div class="mt-4 flex flex-wrap gap-5 text-sm">
                <label class="flex items-center gap-2"><input v-model="group.is_required" type="checkbox" /> Required</label>
                <label v-if="group.display_type === 'checkbox'" class="flex items-center gap-2"><input v-model="group.allows_multiple" type="checkbox" /> Allow multiple selections</label>
                <label class="flex items-center gap-2"><input v-model="group.is_active" type="checkbox" /> Active</label>
            </div>

            <div v-if="group.display_type === 'number'" class="mt-5 grid gap-4 sm:grid-cols-3">
                <label class="space-y-1.5 text-sm"><span class="font-medium">Minimum</span><Input v-model="group.minimum_value" type="number" /></label>
                <label class="space-y-1.5 text-sm"><span class="font-medium">Maximum</span><Input v-model="group.maximum_value" type="number" /></label>
                <label class="space-y-1.5 text-sm"><span class="font-medium">Step</span><Input v-model="group.step_value" type="number" /></label>
            </div>

            <div v-if="usesValues(group.display_type)" class="mt-6 space-y-3 border-t pt-5">
                <div class="flex items-center justify-between gap-3"><div><h4 class="font-semibold">Values</h4><p class="text-sm text-muted-foreground">Add the customer-selectable values and optional price adjustments.</p></div><Button type="button" size="sm" variant="outline" @click="addValue(group)">Add Value</Button></div>
                <div v-for="(value, valueIndex) in group.values" :key="value.id ?? `value-${valueIndex}`" class="grid gap-3 rounded-xl border p-4 lg:grid-cols-[1fr_1fr_150px_120px_auto] lg:items-end">
                    <label class="space-y-1.5 text-sm"><span class="font-medium">Label</span><Input v-model="value.label" placeholder="Large" /></label>
                    <label class="space-y-1.5 text-sm"><span class="font-medium">Internal value</span><Input v-model="value.value" placeholder="large" /></label>
                    <label class="space-y-1.5 text-sm"><span class="font-medium">Price adjustment</span><Input :model-value="(value.price_adjustment_cents / 100).toFixed(2)" type="number" step="0.01" @update:model-value="value.price_adjustment_cents = Math.round(Number($event || 0) * 100)" /><span class="text-xs text-muted-foreground">{{ money(value.price_adjustment_cents) }}</span></label>
                    <label v-if="group.display_type === 'color_swatch'" class="space-y-1.5 text-sm"><span class="font-medium">Color</span><input v-model="value.swatch_color" type="color" class="h-10 w-full rounded border p-1" /></label><div v-else></div>
                    <div class="flex items-center justify-end gap-3"><label class="text-xs"><input v-model="value.is_default" type="checkbox" /> Default</label><Button type="button" size="sm" variant="destructive" @click="removeValue(group, valueIndex)">Remove</Button></div>
                </div>
            </div>
        </article>

        <Button type="button" variant="outline" @click="addGroup">Add Configuration Group</Button>
    </div>
</template>
