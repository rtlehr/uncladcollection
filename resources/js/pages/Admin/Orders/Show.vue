<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type OrderItem = {
    id: number;
    status: string;
    quantity: number;
    unit_price_formatted: string;
    total_price_formatted: string;
    image_title: string;
    license_name: string;
    image: {
        id: number;
        title: string;
        slug: string;
    } | null;
};

type License = {
    id: number;
    license_key: string;
    status: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    downloads_count: number;
    image: {
        id: number;
        title: string;
        slug: string;
    } | null;
};

type Order = {
    id: number;
    order_number: string;
    status: string;
    subtotal_formatted: string;
    total_formatted: string;
    subtotal_cents: number;
    discount_cents: number;
    tax_cents: number;
    total_cents: number;
    currency: string;
    payment_provider: string | null;
    payment_reference: string | null;
    stripe_checkout_session_id: string | null;
    stripe_payment_intent_id: string | null;
    paid_at: string | null;
    refunded_at: string | null;
    canceled_at: string | null;
    created_at: string | null;

    user: {
        id: number;
        name: string;
        email: string;
    } | null;

    items: OrderItem[];
    licenses: License[];
};

defineProps<{
    order: Order;
}>();

function formatCents(value: number): string {
    return `$${(Number(value ?? 0) / 100).toFixed(2)}`;
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="space-y-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <Link
                    href="/admin/orders"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    ← Back to Orders
                </Link>

                <h1 class="mt-2 text-3xl font-semibold">
                    Order {{ order.order_number }}
                </h1>

                <p class="mt-1 text-muted-foreground">
                    View order details, purchased images, and generated licenses.
                </p>
            </div>

            <span class="rounded-full border px-3 py-1 text-sm">
                {{ order.status }}
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Customer
                </h2>

                <div v-if="order.user" class="space-y-2">
                    <div class="font-medium">
                        {{ order.user.name }}
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ order.user.email }}
                    </div>
                </div>

                <div v-else class="text-muted-foreground">
                    No user attached.
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Payment
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Provider</span>
                        <span>{{ order.payment_provider || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Paid</span>
                        <span>{{ order.paid_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Created</span>
                        <span>{{ order.created_at || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Refunded</span>
                        <span>{{ order.refunded_at || '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Totals
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ order.subtotal_formatted || formatCents(order.subtotal_cents) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Discount</span>
                        <span>{{ formatCents(order.discount_cents) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Tax</span>
                        <span>{{ formatCents(order.tax_cents) }}</span>
                    </div>

                    <div class="flex justify-between gap-4 border-t pt-3 font-semibold">
                        <span>Total</span>
                        <span>{{ order.total_formatted || formatCents(order.total_cents) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card shadow-sm">
            <div class="border-b p-6">
                <h2 class="text-lg font-semibold">
                    Order Items
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Image</th>
                            <th class="px-4 py-3 text-left font-medium">License</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Qty</th>
                            <th class="px-4 py-3 text-left font-medium">Unit</th>
                            <th class="px-4 py-3 text-left font-medium">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="item in order.items"
                            :key="item.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ item.image_title }}
                                </div>

                                <Link
                                    v-if="item.image"
                                    :href="`/images/${item.image.slug}`"
                                    class="text-xs text-primary hover:underline"
                                >
                                    View Public Image
                                </Link>
                            </td>

                            <td class="px-4 py-3">
                                {{ item.license_name }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="rounded-full border px-2 py-1 text-xs">
                                    {{ item.status }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                {{ item.quantity }}
                            </td>

                            <td class="px-4 py-3">
                                {{ item.unit_price_formatted }}
                            </td>

                            <td class="px-4 py-3">
                                {{ item.total_price_formatted }}
                            </td>
                        </tr>

                        <tr v-if="order.items.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No order items found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border bg-card shadow-sm">
            <div class="border-b p-6">
                <h2 class="text-lg font-semibold">
                    Licenses
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">License Key</th>
                            <th class="px-4 py-3 text-left font-medium">Image</th>
                            <th class="px-4 py-3 text-left font-medium">License</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Downloads</th>
                            <th class="px-4 py-3 text-left font-medium">Starts</th>
                            <th class="px-4 py-3 text-left font-medium">Expires</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="license in order.licenses"
                            :key="license.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div class="max-w-[220px] break-all text-xs">
                                    {{ license.license_key }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ license.image?.title || '—' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                {{ license.license_name }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="rounded-full border px-2 py-1 text-xs">
                                    {{ license.status }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                {{ license.downloads_used }}
                                <span v-if="license.download_limit !== null">
                                    / {{ license.download_limit }}
                                </span>
                                <span v-else>
                                    / Unlimited
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                {{ license.starts_at || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ license.expires_at || 'Never' }}
                            </td>
                        </tr>

                        <tr v-if="order.licenses.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No licenses found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="order.stripe_checkout_session_id || order.stripe_payment_intent_id || order.payment_reference"
            class="rounded-lg border bg-card p-6 shadow-sm"
        >
            <h2 class="mb-4 text-lg font-semibold">
                Payment References
            </h2>

            <div class="space-y-3 text-sm">
                <div v-if="order.payment_reference">
                    <div class="text-muted-foreground">Payment Reference</div>
                    <div class="break-all">{{ order.payment_reference }}</div>
                </div>

                <div v-if="order.stripe_checkout_session_id">
                    <div class="text-muted-foreground">Stripe Checkout Session</div>
                    <div class="break-all">{{ order.stripe_checkout_session_id }}</div>
                </div>

                <div v-if="order.stripe_payment_intent_id">
                    <div class="text-muted-foreground">Stripe Payment Intent</div>
                    <div class="break-all">{{ order.stripe_payment_intent_id }}</div>
                </div>
            </div>
        </div>
    </div>
</template>