<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';

type Order = { id:number; order_number:string; status_label:string; fulfillment_label:string|null; total_formatted:string; items_count:number; created_at:string; show_url:string };
defineProps<{ orders: { data: Order[]; links: Array<{url:string|null;label:string;active:boolean}> } }>();
</script>

<template>
    <Head title="My Orders" />
    <AccountPageLayout>
        <template #title>My Orders</template>
        <template #description>Review purchases, payment status, fulfillment progress, and tracking details.</template>
        <div class="space-y-4">
            <article v-for="order in orders.data" :key="order.id" class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-stone-500">{{ order.created_at }}</p>
                        <h2 class="mt-1 text-lg font-semibold">{{ order.order_number }}</h2>
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">{{ order.items_count }} item(s) · {{ order.status_label }}<span v-if="order.fulfillment_label"> · {{ order.fulfillment_label }}</span></p>
                    </div>
                    <div class="sm:text-right">
                        <p class="font-semibold">{{ order.total_formatted }}</p>
                        <Link :href="order.show_url" class="mt-3 inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-medium">View order</Link>
                    </div>
                </div>
            </article>
            <p v-if="orders.data.length === 0" class="rounded-2xl border border-dashed p-10 text-center text-stone-500">You do not have any orders yet.</p>
            <nav v-if="orders.links.length > 3" class="flex flex-wrap gap-2" aria-label="Order pagination">
                <Link v-for="link in orders.links" :key="link.label" :href="link.url || ''" :class="['rounded-lg border px-3 py-2 text-sm', { 'pointer-events-none opacity-50': !link.url, 'bg-stone-900 text-white': link.active }]" v-html="link.label" />
            </nav>
        </div>
    </AccountPageLayout>
</template>
