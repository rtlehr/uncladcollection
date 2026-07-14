<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminOrderFilters,
    PaginatedAdminOrders,
} from '@/types/orderList';

const props = defineProps<{
    orders: PaginatedAdminOrders;
    filters: AdminOrderFilters;
    statuses: string[];
    fulfillmentStatuses: { value: string; label: string }[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const fulfillmentStatus = ref((props.filters as any).fulfillment_status ?? '');

function reload() {
    router.get(
        '/admin/orders',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            fulfillment_status: fulfillmentStatus.value || undefined,
            sort: props.filters.sort,
            direction: props.filters.direction,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetFilters() {
    search.value = '';
    status.value = '';
    fulfillmentStatus.value = '';

    router.get(
        '/admin/orders',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column
        && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        '/admin/orders',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            fulfillment_status: fulfillmentStatus.value || undefined,
            sort: column,
            direction,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}
</script>

<template>
    <Head title="Orders" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Orders"
            description="View customer image license purchases."
        />

        <FilterToolbar :columns="3" compact>
            <SearchToolbar
                v-model="search"
                placeholder="Search orders, users, or images..."
                :show-reset="false"
                @search="reload"
            />

            <select
                v-model="status"
                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                @change="reload"
            >
                <option value="">
                    All Statuses
                </option>

                <option
                    v-for="orderStatus in statuses"
                    :key="orderStatus"
                    :value="orderStatus"
                >
                    {{ orderStatus }}
                </option>
            </select>


            <select v-model="fulfillmentStatus" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm" @change="reload"><option value="">All Fulfillment</option><option v-for="item in fulfillmentStatuses" :key="item.value" :value="item.value">{{ item.label }}</option></select>
            <template #actions>
                <Button
                    type="button"
                    @click="reload"
                >
                    Apply Filters
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    @click="resetFilters"
                >
                    Reset
                </Button>
            </template>
        </FilterToolbar>

        <DataTable min-width="1100px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell
                        label="Order"
                        column="order_number"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="User" />

                    <DataTableHeaderCell
                        label="Status"
                        column="status"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Total"
                        column="total_cents"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="Fulfillment" />
                    <DataTableHeaderCell label="Items" />
                    <DataTableHeaderCell label="Licenses" />

                    <DataTableHeaderCell
                        label="Paid"
                        column="paid_at"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Created"
                        column="created_at"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Actions"
                        align="right"
                    />
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="order in orders.data"
                    :key="order.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4 font-medium">
                        {{ order.order_number }}
                    </td>

                    <td class="p-4">
                        <div v-if="order.user">
                            <div class="font-medium">
                                {{ order.user.name }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ order.user.email }}
                            </div>
                        </div>

                        <span
                            v-else
                            class="text-muted-foreground"
                        >
                            —
                        </span>
                    </td>

                    <td class="p-4">
                        <StatusBadge :status="order.status" />
                    </td>

                    <td class="p-4 font-medium">
                        {{ order.total_formatted }}
                    </td>

                    <td class="p-4"><StatusBadge :status="order.fulfillment_status" /></td>

                    <td class="p-4">
                        {{ order.items_count }}
                    </td>

                    <td class="p-4">
                        {{ order.licenses_count }}
                    </td>

                    <td class="p-4">
                        {{ order.paid_at ?? '—' }}
                    </td>

                    <td class="p-4">
                        {{ order.created_at ?? '—' }}
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/orders/${order.id}`">
                                    View
                                </Link>
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="orders.data.length === 0"
                    :colspan="9"
                    message="No orders found."
                />
            </tbody>
        </DataTable>

        <Pagination
            :links="orders.links"
            :from="orders.from ?? null"
            :to="orders.to ?? null"
            :total="orders.total ?? null"
            item-label="orders"
            :show-summary="orders.total !== undefined"
        />
    </div>
</template>
