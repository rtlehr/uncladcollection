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
    AdminLicenseFilters,
    PaginatedAdminLicenses,
} from '@/types/licenseList';

const props = defineProps<{
    licenses: PaginatedAdminLicenses;
    filters: AdminLicenseFilters;
    statuses: string[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function reload() {
    router.get(
        '/admin/licenses',
        {
            search: search.value || undefined,
            status: status.value || undefined,
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

    router.get(
        '/admin/licenses',
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
        '/admin/licenses',
        {
            search: search.value || undefined,
            status: status.value || undefined,
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

function downloadLabel(
    downloadsUsed: number,
    downloadLimit: number | null,
): string {
    if (downloadLimit === null) {
        return `${downloadsUsed} / Unlimited`;
    }

    return `${downloadsUsed} / ${downloadLimit}`;
}
</script>

<template>
    <Head title="Licenses" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Licenses"
            description="View customer image licenses and download usage."
        />

        <FilterToolbar :columns="2" compact>
            <SearchToolbar
                v-model="search"
                placeholder="Search license, user, image, or order..."
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
                    v-for="licenseStatus in statuses"
                    :key="licenseStatus"
                    :value="licenseStatus"
                >
                    {{ licenseStatus }}
                </option>
            </select>

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

        <DataTable min-width="1200px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell
                        label="License"
                        column="license_key"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="User" />
                    <DataTableHeaderCell label="Image" />
                    <DataTableHeaderCell label="Order" />

                    <DataTableHeaderCell
                        label="Status"
                        column="status"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Downloads"
                        column="downloads_used"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Expires"
                        column="expires_at"
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
                    v-for="license in licenses.data"
                    :key="license.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4">
                        <div class="font-medium">
                            {{ license.license_name }}
                        </div>

                        <div class="max-w-[240px] break-all font-mono text-xs text-muted-foreground">
                            {{ license.license_key }}
                        </div>
                    </td>

                    <td class="p-4">
                        <div v-if="license.user">
                            <div class="font-medium">
                                {{ license.user.name }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ license.user.email }}
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
                        <div v-if="license.image">
                            <div class="font-medium">
                                {{ license.image.title }}
                            </div>

                            <Link
                                :href="`/images/${license.image.slug}`"
                                class="text-xs text-primary hover:underline"
                            >
                                View Image
                            </Link>
                        </div>

                        <span
                            v-else
                            class="text-muted-foreground"
                        >
                            —
                        </span>
                    </td>

                    <td class="p-4">
                        <Link
                            v-if="license.order"
                            :href="`/admin/orders/${license.order.id}`"
                            class="text-primary hover:underline"
                        >
                            {{ license.order.order_number }}
                        </Link>

                        <span
                            v-else
                            class="text-muted-foreground"
                        >
                            —
                        </span>
                    </td>

                    <td class="p-4">
                        <StatusBadge :status="license.status" />
                    </td>

                    <td class="p-4">
                        {{
                            downloadLabel(
                                license.downloads_used,
                                license.download_limit,
                            )
                        }}
                    </td>

                    <td class="p-4">
                        {{ license.expires_at || 'Never' }}
                    </td>

                    <td class="p-4">
                        {{ license.created_at || '—' }}
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/licenses/${license.id}`">
                                    View
                                </Link>
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="licenses.data.length === 0"
                    :colspan="9"
                    message="No licenses found."
                />
            </tbody>
        </DataTable>

        <Pagination
            :links="licenses.links"
            :from="licenses.from ?? null"
            :to="licenses.to ?? null"
            :total="licenses.total ?? null"
            item-label="licenses"
            :show-summary="licenses.total !== undefined"
        />
    </div>
</template>
