<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminDownloadFilters,
    PaginatedAdminDownloads,
} from '@/types/downloadList';

const props = defineProps<{
    downloads: PaginatedAdminDownloads;
    filters: AdminDownloadFilters;
}>();

const search = ref(props.filters.search ?? '');

function reload() {
    router.get(
        '/admin/downloads',
        {
            search: search.value || undefined,
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

    router.get(
        '/admin/downloads',
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
        '/admin/downloads',
        {
            search: search.value || undefined,
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
    <Head title="Downloads" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Downloads"
            description="View customer image download history."
        />

        <FilterToolbar :columns="1" compact>
            <SearchToolbar
                v-model="search"
                placeholder="Search downloads, users, images, licenses, or orders..."
                :show-reset="false"
                @search="reload"
            />

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
                        label="Downloaded"
                        column="downloaded_at"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="User" />
                    <DataTableHeaderCell label="Image" />
                    <DataTableHeaderCell label="License" />
                    <DataTableHeaderCell label="Order" />

                    <DataTableHeaderCell
                        label="Type"
                        column="download_type"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="IP"
                        column="ip_address"
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
                    v-for="download in downloads.data"
                    :key="download.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4">
                        {{ download.downloaded_at || '—' }}
                    </td>

                    <td class="p-4">
                        <div v-if="download.user">
                            <div class="font-medium">
                                {{ download.user.name }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ download.user.email }}
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
                        <div v-if="download.image">
                            <div class="font-medium">
                                {{ download.image.title }}
                            </div>

                            <Link
                                :href="`/images/${download.image.slug}`"
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
                        <div v-if="download.license">
                            <Link
                                :href="`/admin/licenses/${download.license.id}`"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ download.license.license_name }}
                            </Link>

                            <div class="max-w-[220px] break-all font-mono text-xs text-muted-foreground">
                                {{ download.license.license_key }}
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
                        <Link
                            v-if="download.order"
                            :href="`/admin/orders/${download.order.id}`"
                            class="text-primary hover:underline"
                        >
                            {{ download.order.order_number }}
                        </Link>

                        <span
                            v-else
                            class="text-muted-foreground"
                        >
                            —
                        </span>
                    </td>

                    <td class="p-4 capitalize">
                        {{ download.download_type }}
                    </td>

                    <td class="p-4 font-mono text-xs">
                        {{ download.ip_address || '—' }}
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/downloads/${download.id}`">
                                    View
                                </Link>
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="downloads.data.length === 0"
                    :colspan="8"
                    message="No downloads found."
                />
            </tbody>
        </DataTable>

        <Pagination
            :links="downloads.links"
            :from="downloads.from ?? null"
            :to="downloads.to ?? null"
            :total="downloads.total ?? null"
            item-label="downloads"
            :show-summary="downloads.total !== undefined"
        />
    </div>
</template>
