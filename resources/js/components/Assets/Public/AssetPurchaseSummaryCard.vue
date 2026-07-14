<script setup lang="ts">
import { ArrowDown, CheckCircle2, CreditCard, ShieldCheck } from '@lucide/vue';
import AssetFulfillmentBadge from './AssetFulfillmentBadge.vue';
import type { PublicAssetOffering } from '@/types/publicAsset';

const props = defineProps<{
    offerings: PublicAssetOffering[];
    formats: string[];
    fulfillmentType: 'digital' | 'physical' | 'hybrid';
}>();

function money(cents: number, currency: string): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(cents / 100);
}
function scrollToPurchase(): void {
    document.getElementById('purchase')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-lg dark:border-stone-800 dark:bg-stone-900">
        <div class="border-b border-stone-200 bg-gradient-to-br from-stone-50 to-white p-6 dark:border-stone-800 dark:from-stone-900 dark:to-stone-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Purchase options</p>
                <AssetFulfillmentBadge :type="fulfillmentType" />
            </div>
            <h2 class="mt-3 text-2xl font-semibold">Choose the package that fits</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">Compare licenses, configure product details, review fulfillment information, and see the exact total before checkout.</p>
        </div>

        <div class="p-6">
            <div v-if="offerings.length" class="rounded-2xl bg-stone-100 p-4 dark:bg-stone-800/70">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Starting at</p>
                        <p class="mt-1 text-3xl font-bold">{{ money(Math.min(...offerings.map((offering) => offering.price_cents)), offerings[0].currency) }}</p>
                    </div>
                    <p class="text-right text-sm text-stone-500">{{ offerings.length }} package{{ offerings.length === 1 ? '' : 's' }}<br>{{ formats.length }} format{{ formats.length === 1 ? '' : 's' }}</p>
                </div>
            </div>

            <button type="button" class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-primary)] focus-visible:ring-offset-2" @click="scrollToPurchase">
                Configure your purchase
                <ArrowDown class="h-4 w-4" />
            </button>

            <div class="mt-5 grid gap-3 text-sm text-stone-600 dark:text-stone-300">
                <div class="flex items-center gap-3"><ShieldCheck class="h-4 w-4 text-emerald-600" />Secure Stripe checkout</div>
                <div class="flex items-center gap-3"><CreditCard class="h-4 w-4 text-emerald-600" />Pricing confirmed before payment</div>
                <div class="flex items-center gap-3"><CheckCircle2 class="h-4 w-4 text-emerald-600" />Selections saved with your order</div>
            </div>
        </div>
    </section>
</template>
