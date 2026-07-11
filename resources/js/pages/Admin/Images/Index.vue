<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import AdminRowActions from '@/Components/Admin/AdminRowActions.vue';
import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import AssetThumbnail from '@/Components/Shared/AssetThumbnail.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';

import type {
    AdminImageCollection,
    AdminImageListFilters,
    AdminImageListItem,
} from '@/types/adminImageList';

const props = defineProps<{
    images: AdminImageListItem[];
    collections: AdminImageCollection[];
    filters: AdminImageListFilters;
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const collectionId = ref(props.filters.collection_id ?? '');

const deletion = useDeleteConfirmation<AdminImageListItem>();

function reload() {
    router.get(
        '/admin/images',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            collection_id: collectionId.value || undefined,
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
    collectionId.value = '';

    router.get(
        '/admin/images',
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
        '/admin/images',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            collection_id: collectionId.value || undefined,
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

function confirmDelete() {
    deletion.runDelete((image, finish) => {
        router.delete(`/admin/images/${image.id}`, {
            preserveScroll: true,
            onFinish: finish,
        });
    });
}
</script>

<template>
    <Head title="Images" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Images"
            description="Manage image uploads and collections."
        />

        <ActionToolbar align="end">
            <template #secondary>
                <Button as-child>
                    <Link href="/admin/images/create">
                        Add Image
                    </Link>
                </Button>
            </template>
        </ActionToolbar>

        <FilterToolbar :columns="3" compact>
            <SearchToolbar
                v-model="search"
                input-label="Search images"
                placeholder="Search title, slug, photographer..."
                :show-reset="false"
                @search="reload"
            />

            <label class="grid gap-1.5 text-sm font-medium">
                <span class="sr-only">Collection</span>

                <select
                    v-model="collectionId"
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    aria-label="Filter by collection"
                    @change="reload"
                >
                    <option value="">
                        All Collections
                    </option>

                    <option
                        v-for="collection in collections"
                        :key="collection.id"
                        :value="collection.id"
                    >
                        {{ collection.name }}
                    </option>
                </select>
            </label>

            <label class="grid gap-1.5 text-sm font-medium">
                <span class="sr-only">Status</span>

                <select
                    v-model="status"
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    aria-label="Filter by status"
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
            </label>

            <template #actions>
                <Button type="button" @click="reload">
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

        <DataTable
            min-width="1050px"
            caption="Administrative image list"
        >
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell label="Preview" />

                    <DataTableHeaderCell
                        label="Title"
                        column="title"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="Collection" />

                    <DataTableHeaderCell
                        label="Photographer"
                        column="photographer"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

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
                    v-for="image in images"
                    :key="image.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4">
                        <AssetThumbnail
                            :src="image.thumbnail_url"
                            :alt="image.title"
                            fallback="No image"
                        />
                    </td>

                    <td class="p-4">
                        <div class="font-medium">
                            {{ image.title }}
                        </div>

                        <div class="font-mono text-xs text-muted-foreground">
                            {{ image.slug }}
                        </div>
                    </td>

                    <td class="p-4">
                        {{ image.collection?.name ?? '—' }}
                    </td>

                    <td class="p-4">
                        {{ image.photographer ?? '—' }}
                    </td>

                    <td class="p-4">
                        {{ image.sort_order }}
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="image.is_active ? 'active' : 'inactive'"
                        />
                    </td>

                    <td class="p-4">
                        <AdminRowActions
                            compact
                            :view-href="`/admin/images/${image.id}`"
                            :edit-href="`/admin/images/${image.id}/edit`"
                            @delete="deletion.requestDelete(image)"
                        />
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="images.length === 0"
                    :colspan="7"
                    message="No images found."
                />
            </tbody>
        </DataTable>

        <ConfirmActionDialog
            v-model:open="deletion.open.value"
            title="Delete image?"
            :description="
                deletion.selected.value
                    ? `Delete the image '${deletion.selected.value.title}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Image"
            destructive
            :loading="deletion.processing.value"
            @confirm="confirmDelete"
            @cancel="deletion.cancelDelete"
        />
    </div>
</template>
