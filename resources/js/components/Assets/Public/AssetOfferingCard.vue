<script setup lang="ts">
import { Check, Download, Package } from '@lucide/vue';
import AssetFormatBadge from './AssetFormatBadge.vue';
import type { PublicAssetOffering } from '@/types/publicAsset';

const props = defineProps<{ offering: PublicAssetOffering; purchaseUrl: string | null; featured?: boolean }>();
function money(cents: number, currency: string): string { return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(cents / 100); }
function bytes(value: number): string { if (!value) return '0 B'; const units = ['B','KB','MB','GB']; const i = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1); return `${(value / 1024 ** i).toFixed(i ? 1 : 0)} ${units[i]}`; }
</script>
<template>
    <article :class="['relative flex h-full flex-col rounded-3xl border bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-stone-900', featured ? 'border-[var(--brand-accent)] ring-1 ring-[var(--brand-accent)]/20' : 'border-stone-200 dark:border-stone-800']">
        <span v-if="featured" class="absolute -top-3 left-6 rounded-full bg-[var(--brand-accent)] px-3 py-1 text-xs font-semibold text-white">Most popular</span>
        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">{{ offering.license_type.name }}</p><h3 class="mt-2 text-xl font-semibold">{{ offering.name }}</h3></div><p class="text-2xl font-bold">{{ money(offering.price_cents, offering.currency) }}</p></div>
        <p v-if="offering.description || offering.license_type.description" class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">{{ offering.description || offering.license_type.description }}</p>
        <div class="mt-5"><p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Perfect for</p><ul class="mt-3 space-y-2 text-sm"><li class="flex gap-2"><Check class="mt-0.5 h-4 w-4 text-emerald-600" />{{ offering.license_type.description || 'Projects covered by this license' }}</li></ul></div>
        <div class="mt-5 flex flex-wrap gap-2"><AssetFormatBadge v-for="format in [...new Set(offering.files.map(file => file.extension))]" :key="format" :format="format" /></div>
        <ul class="mt-5 flex-1 space-y-3 text-sm"><li v-for="file in offering.files" :key="file.id" class="flex gap-3"><Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /><span><strong class="font-medium">{{ file.role_label }}</strong><span class="block text-stone-500">{{ file.original_filename }}</span></span></li></ul>
        <div class="mt-5 grid grid-cols-2 gap-3 rounded-2xl bg-stone-100 p-4 text-sm dark:bg-stone-800/70"><div><Package class="mb-1 h-4 w-4" /><strong>{{ offering.files.length }}</strong><span class="block text-xs text-stone-500">files</span></div><div><Download class="mb-1 h-4 w-4" /><strong>{{ bytes(offering.total_size_bytes) }}</strong><span class="block text-xs text-stone-500">package size</span></div></div>
        <a v-if="purchaseUrl" :href="purchaseUrl" class="mt-5 inline-flex h-11 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90">Choose this license</a>
        <button v-else type="button" disabled class="mt-5 h-11 rounded-full bg-stone-200 px-5 text-sm font-semibold text-stone-500 dark:bg-stone-800">Purchasing coming next</button>
        <p class="mt-3 text-center text-xs text-stone-500">{{ offering.download_limit === null ? 'Unlimited downloads' : `${offering.download_limit} downloads` }} · {{ offering.expires_after_days === null ? 'No expiration' : `${offering.expires_after_days} days` }}</p>
    </article>
</template>
