<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';

defineProps<{ order:any }>();
</script>
<template>
    <Head :title="`Order ${order.order_number}`" />
    <AccountPageLayout>
        <template #title>{{ order.order_number }}</template>
        <template #description>Order details, items, payment status, and fulfillment timeline.</template>
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <section class="rounded-2xl border p-5">
                    <h2 class="text-lg font-semibold">Purchased items</h2>
                    <div class="mt-4 divide-y">
                        <div v-for="item in order.items" :key="item.id" class="py-4 first:pt-0 last:pb-0">
                            <div class="flex justify-between gap-4"><div><h3 class="font-medium">{{ item.title }}</h3><p class="mt-1 text-sm text-stone-500">{{ item.license_name }}<span v-if="item.offering_name"> · {{ item.offering_name }}</span></p></div><p class="font-medium">{{ item.total_formatted }}</p></div>
                            <Link v-if="item.license_url" :href="item.license_url" class="mt-3 inline-flex text-sm font-medium underline">View license and downloads</Link>
                        </div>
                    </div>
                </section>
                <section class="rounded-2xl border p-5"><h2 class="text-lg font-semibold">Order timeline</h2><ol class="mt-5 space-y-5"><li v-for="event in order.timeline" :key="event.label + event.occurred_at" class="border-l-2 pl-4"><p class="font-medium">{{ event.label }}</p><p class="text-sm text-stone-500">{{ event.detail }}</p><p class="mt-1 text-xs text-stone-400">{{ event.occurred_at }}</p></li></ol></section>
            </div>
            <aside class="space-y-6"><section class="rounded-2xl border p-5"><h2 class="font-semibold">Summary</h2><dl class="mt-4 space-y-3 text-sm"><div class="flex justify-between"><dt>Status</dt><dd>{{ order.status_label }}</dd></div><div v-if="order.fulfillment_label" class="flex justify-between"><dt>Fulfillment</dt><dd>{{ order.fulfillment_label }}</dd></div><div class="flex justify-between"><dt>Subtotal</dt><dd>{{ order.subtotal_formatted }}</dd></div><div class="flex justify-between"><dt>Discount</dt><dd>{{ order.discount_formatted }}</dd></div><div class="flex justify-between"><dt>Tax</dt><dd>{{ order.tax_formatted }}</dd></div><div class="flex justify-between border-t pt-3 font-semibold"><dt>Total</dt><dd>{{ order.total_formatted }}</dd></div></dl></section><section v-if="order.tracking_number" class="rounded-2xl border p-5"><h2 class="font-semibold">Tracking</h2><p class="mt-3 text-sm">{{ order.shipping_carrier || 'Carrier' }}</p><p class="mt-1 break-all font-mono text-sm">{{ order.tracking_number }}</p></section></aside>
        </div>
    </AccountPageLayout>
</template>
