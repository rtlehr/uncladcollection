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
    AdminCategory,
    AdminCategoryFilters,
    CategoryTypes,
} from '@/types/category';

const props = defineProps<{
    categories: AdminCategory[];
    filters: AdminCategoryFilters;
    categoryTypes: CategoryTypes;
}>();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');

const selectedCategory = ref<AdminCategory | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function reload() {
    router.get(
        '/admin/categories',
        {
            search: search.value || undefined,
            type: type.value || undefined,
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
    type.value = '';

    router.get(
        '/admin/categories',
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
        '/admin/categories',
        {
            search: search.value || undefined,
            type: type.value || undefined,
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

function requestDelete(category: AdminCategory) {
    selectedCategory.value = category;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    deleteDialogOpen.value = false;
    selectedCategory.value = null;
}

function confirmDelete() {
    if (!selectedCategory.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/admin/categories/${selectedCategory.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedCategory.value = null;
        },
    });
}
</script>

<template>
    <Head title="Categories" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Categories"
            description="Manage image and blog categories."
        />

        <ActionToolbar align="end">
            <template #secondary>
                <Button as-child>
                    <Link href="/admin/categories/create">
                        Add Category
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
                v-model="type"
                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                @change="reload"
            >
                <option value="">
                    All Types
                </option>

                <option
                    v-for="(label, value) in categoryTypes"
                    :key="value"
                    :value="value"
                >
                    {{ label }}
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

        <DataTable min-width="1000px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell
                        label="Name"
                        column="name"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Type"
                        column="category_type"
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
                    v-for="category in categories"
                    :key="category.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4 font-medium">
                        {{ category.name }}
                    </td>

                    <td class="p-4 capitalize">
                        {{ category.category_type }}
                    </td>

                    <td class="p-4 font-mono text-xs">
                        {{ category.slug }}
                    </td>

                    <td class="max-w-md p-4 text-muted-foreground">
                        {{ category.description || '—' }}
                    </td>

                    <td class="p-4">
                        {{ category.sort_order }}
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="category.is_active ? 'active' : 'inactive'"
                        />
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/categories/${category.id}/edit`">
                                    Edit
                                </Link>
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                @click="requestDelete(category)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="categories.length === 0"
                    :colspan="7"
                    message="No categories found."
                />
            </tbody>
        </DataTable>

        <ConfirmActionDialog
            v-model:open="deleteDialogOpen"
            title="Delete category?"
            :description="
                selectedCategory
                    ? `Delete the category '${selectedCategory.name}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Category"
            destructive
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </div>
</template>
