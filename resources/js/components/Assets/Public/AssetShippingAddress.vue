<script setup lang="ts">
import { CheckCircle2, MapPin } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';

export type ShippingAddressPayload = {
    full_name: string;
    company: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    region: string;
    postal_code: string;
    country_code: string;
    phone: string;
    delivery_instructions: string;
};

const props = defineProps<{ required: boolean }>();
const emit = defineEmits<{ change: [payload: { address: ShippingAddressPayload; valid: boolean }] }>();

const address = reactive<ShippingAddressPayload>({
    full_name: '', company: '', address_line_1: '', address_line_2: '', city: '',
    region: '', postal_code: '', country_code: 'US', phone: '', delivery_instructions: '',
});

const complete = computed(() => Boolean(
    address.full_name.trim()
    && address.address_line_1.trim()
    && address.city.trim()
    && address.region.trim()
    && address.postal_code.trim()
    && address.country_code.length === 2,
));
const valid = computed(() => !props.required || complete.value);

watch(address, () => emit('change', { address: { ...address }, valid: valid.value }), { deep: true, immediate: true });
</script>

<template>
    <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900">
        <div class="border-b border-stone-200 bg-stone-50 px-6 py-5 dark:border-stone-800 dark:bg-stone-900/70">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                    <MapPin class="h-5 w-5" />
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold">Shipping information</h3>
                        <span v-if="required" class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-300">Mandatory</span>
                        <span v-else class="rounded-full bg-stone-200 px-2.5 py-1 text-xs font-medium text-stone-600 dark:bg-stone-800 dark:text-stone-300">Optional</span>
                    </div>
                    <p class="mt-1 text-sm text-stone-500">Used only to fulfill this product. A different product can use a different address.</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium">Full name <span v-if="required" class="text-red-600">*</span><input v-model="address.full_name" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping name" /></label>
                <label class="text-sm font-medium">Company<input v-model="address.company" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping organization" /></label>
                <label class="text-sm font-medium sm:col-span-2">Address line 1 <span v-if="required" class="text-red-600">*</span><input v-model="address.address_line_1" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping address-line1" /></label>
                <label class="text-sm font-medium sm:col-span-2">Address line 2<input v-model="address.address_line_2" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping address-line2" /></label>
                <label class="text-sm font-medium">City <span v-if="required" class="text-red-600">*</span><input v-model="address.city" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping address-level2" /></label>
                <label class="text-sm font-medium">State / Province <span v-if="required" class="text-red-600">*</span><input v-model="address.region" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping address-level1" /></label>
                <label class="text-sm font-medium">Postal code <span v-if="required" class="text-red-600">*</span><input v-model="address.postal_code" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping postal-code" /></label>
                <label class="text-sm font-medium">Country <span v-if="required" class="text-red-600">*</span><select v-model="address.country_code" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping country"><option value="US">United States</option><option value="CA">Canada</option><option value="GB">United Kingdom</option><option value="AU">Australia</option><option value="NZ">New Zealand</option></select></label>
                <label class="text-sm font-medium">Phone<input v-model="address.phone" class="mt-1 h-11 w-full rounded-xl border bg-transparent px-3 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="shipping tel" /></label>
                <label class="text-sm font-medium sm:col-span-2">Delivery instructions<textarea v-model="address.delivery_instructions" rows="3" class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2 focus:border-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]/20" /></label>
            </div>

            <div :class="['mt-5 flex items-center gap-2 rounded-xl px-4 py-3 text-sm', valid ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300']">
                <CheckCircle2 v-if="valid" class="h-4 w-4" />
                <MapPin v-else class="h-4 w-4" />
                {{ valid ? 'Shipping information is ready.' : 'Complete all starred address fields to continue.' }}
            </div>
        </div>
    </section>
</template>
