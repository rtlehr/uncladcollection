<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Order = {
    id: number;
    order_number: string;
    status: string;
    total_formatted: string;
    currency: string;
    payment_provider: string | null;
    paid_at: string | null;
    created_at: string | null;
    items_count: number;
    licenses_count: number;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
};

const props = defineProps<{
    orders: {
        data: Order[];
        links: any[];
        meta: any;
    };
    filters: {
        search: string;
        status: string;
    };
    statuses: string[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

watch([search, status], () => {
    router.get('/admin/orders', {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
});
</script>

<template>
    <Head title="Orders" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">
                Orders
            </h1>

            <p class="mt-1 text-muted-foreground">
                View customer image license purchases.
            </p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <Input
                v-model="search"
                placeholder="Search orders, users, or images..."
                class="md:max-w-sm"
            />

            <select
                v-model="status"
                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="">All Statuses</option>

                <option
                    v-for="orderStatus in statuses"
                    :key="orderStatus"
                    :value="orderStatus"
                >
                    {{ orderStatus }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">
                            Order
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            User
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Status
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Total
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Items
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Licenses
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Paid
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Created
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="order in orders.data"
                        :key="order.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ order.order_number }}
                        </td>

                        <td class="px-4 py-3">
                            <div v-if="order.user">
                                <div class="font-medium">
                                    {{ order.user.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ order.user.email }}
                                </div>
                            </div>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span class="rounded-full border px-2 py-1 text-xs">
                                {{ order.status }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{ order.total_formatted }}
                        </td>

                        <td class="px-4 py-3">
                            {{ order.items_count }}
                        </td>

                        <td class="px-4 py-3">
                            {{ order.licenses_count }}
                        </td>

                        <td class="px-4 py-3">
                            {{ order.paid_at ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ order.created_at ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    as-child
                                >
                                    <Link :href="`/admin/orders/${order.id}`">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="orders.data.length === 0">
                        <td
                            colspan="9"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No orders found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="orders.links?.length"
            class="flex flex-wrap gap-2"
        >
            <Link
                v-for="link in orders.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="rounded-md border px-3 py-2 text-sm"
                :class="{
                    'bg-primary text-primary-foreground': link.active,
                    'pointer-events-none opacity-50': !link.url,
                }"
            />
        </div>
    </div>
</template>