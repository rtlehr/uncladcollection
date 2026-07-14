<script setup lang="ts">
import { BadgeCheck, CreditCard, Download, PackageCheck, RefreshCcw } from '@lucide/vue';

const props = defineProps<{ fulfillmentType: 'digital' | 'physical' | 'hybrid' }>();
</script>

<template>
    <section class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-stone-800 dark:bg-stone-900/50">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">Purchase with confidence</p>
        <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="flex gap-3"><CreditCard class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]" /><div><h3 class="font-semibold">Secure payment</h3><p class="mt-1 text-sm text-stone-500">Checkout is handled securely through Stripe.</p></div></div>
            <div class="flex gap-3"><component :is="fulfillmentType === 'digital' ? Download : PackageCheck" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]" /><div><h3 class="font-semibold">{{ fulfillmentType === 'digital' ? 'Instant access' : 'Fulfillment recorded' }}</h3><p class="mt-1 text-sm text-stone-500">{{ fulfillmentType === 'digital' ? 'Eligible files become available after payment.' : 'Shipping details and product selections stay with your order.' }}</p></div></div>
            <div class="flex gap-3"><BadgeCheck class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]" /><div><h3 class="font-semibold">Exact purchase saved</h3><p class="mt-1 text-sm text-stone-500">Your package, options, quantity, price, and terms are recorded.</p></div></div>
            <div class="flex gap-3"><RefreshCcw class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]" /><div><h3 class="font-semibold">My Library</h3><p class="mt-1 text-sm text-stone-500">Review purchases, invoices, and available downloads from your account.</p></div></div>
        </div>
    </section>
</template>
