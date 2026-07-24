<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminCollection,
    AdminCollectionFilters,
} from '@/types/collection';

const props = defineProps<{
    collections: AdminCollection[];
    filters: AdminCollectionFilters;
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const selectedCollection = ref<AdminCollection | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function reload() {
    router.get(
        '/admin/collections',
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
        '/admin/collections',
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
        '/admin/collections',
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

function requestDelete(collection: AdminCollection) {
    selectedCollection.value = collection;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    deleteDialogOpen.value = false;
    selectedCollection.value = null;
}

function confirmDelete() {
    if (!selectedCollection.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/admin/collections/${selectedCollection.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedCollection.value = null;
        },
    });
}
</script>

<template>
    <Head title="Collections" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Collections"
            description="Manage image collections."
        />

        <ActionToolbar align="end">
            <template #secondary>
                <Button as-child>
                    <Link href="/admin/collections/create">
                        Add Collection
                    </Link>
                </Button>
            </template>
        </ActionToolbar>

        <FilterToolbar :columns="2" compact>
            <SearchToolbar
                v-model="search"
                placeholder="Search name, slug, or description..."
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

                <option value="1">
                    Active
                </option>

                <option value="0">
                    Inactive
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

        <DataTable min-width="900px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell label="Cover" />

                    <DataTableHeaderCell
                        label="Name"
                        column="name"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Slug"
                        column="slug"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="Description" />

                    <DataTableHeaderCell
                        label="Sort"
                        column="sort_order"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Status"
                        column="is_active"
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
                    v-for="collection in collections"
                    :key="collection.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4">
                        <div class="h-14 w-24 overflow-hidden rounded-md border bg-muted">
                            <img
                                v-if="collection.cover_image_url"
                                :src="collection.cover_image_url"
                                :alt="`${collection.name} cover`"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    </td>

                    <td class="p-4 font-medium">
                        {{ collection.name }}
                    </td>

                    <td class="p-4 font-mono text-xs">
                        {{ collection.slug }}
                    </td>

                    <td class="max-w-md p-4 text-muted-foreground">
                        {{ collection.description || '—' }}
                    </td>

                    <td class="p-4">
                        {{ collection.sort_order }}
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="collection.is_active ? 'active' : 'inactive'"
                        />
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/collections/${collection.id}/edit`">
                                    Edit
                                </Link>
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                @click="requestDelete(collection)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="collections.length === 0"
                    :colspan="7"
                    message="No collections found."
                />
            </tbody>
        </DataTable>

        <ConfirmActionDialog
            v-model:open="deleteDialogOpen"
            title="Delete collection?"
            :description="
                selectedCollection
                    ? `Delete the collection '${selectedCollection.name}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Collection"
            destructive
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </div>
</template>
