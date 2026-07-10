<script setup lang="ts">
import { computed } from 'vue';

import { formatCurrency } from '@/lib/formatCurrency';

import type { LicenseType } from '@/types/asset';

const props = defineProps<{
    modelValue: number | null;
    licenseTypes: LicenseType[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const selectedValue = computed({
    get: () => props.modelValue,
    set: (value: number | null) => emit('update:modelValue', value),
});
</script>

<template>
    <select
        v-model.number="selectedValue"
        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
        aria-label="License type"
    >
        <option
            v-for="licenseType in licenseTypes"
            :key="licenseType.id"
            :value="licenseType.id"
        >
            {{ licenseType.name }} -
            {{ formatCurrency(licenseType.price_cents, licenseType.currency) }}
        </option>
    </select>
</template>
