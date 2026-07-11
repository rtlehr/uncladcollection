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

const selectedImage = ref<AdminImageListItem | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

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

function requestDelete(image: AdminImageListItem) {
    selectedImage.value = image;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    selectedImage.value = null;
    deleteDialogOpen.value = false;
}

function confirmDelete() {
    if (!selectedImage.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/admin/images/${selectedImage.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            selectedImage.value = null;
            deleteDialogOpen.value = false;
        },
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
                placeholder="Search title, slug, photographer..."
                :show-reset="false"
                @search="reload"
            />

            <select
                v-model="collectionId"
                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
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

        <DataTable min-width="1050px">
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
                        <img
                            v-if="image.thumbnail_url"
                            :src="image.thumbnail_url"
                            :alt="image.title"
                            class="h-16 w-16 rounded border object-cover"
                        />

                        <div
                            v-else
                            class="flex h-16 w-16 items-center justify-center rounded border text-xs text-muted-foreground"
                        >
                            No Image
                        </div>
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
                        <div class="flex justify-end gap-2">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/admin/images/${image.id}`">
                                    View
                                </Link>
                            </Button>

                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/admin/images/${image.id}/edit`">
                                    Edit
                                </Link>
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                @click="requestDelete(image)"
                            >
                                Delete
                            </Button>
                        </div>
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
            v-model:open="deleteDialogOpen"
            title="Delete image?"
            :description="
                selectedImage
                    ? `Delete the image '${selectedImage.title}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Image"
            destructive
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </div>
</template>
