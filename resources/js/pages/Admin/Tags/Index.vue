<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminTag,
    AdminTagFilters,
    TagTypes,
} from '@/types/tag';

const props = defineProps<{
    tags: AdminTag[];
    filters: AdminTagFilters;
    tagTypes: TagTypes;
}>();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');

const selectedTag = ref<AdminTag | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function reload() {
    router.get(
        '/admin/tags',
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
        '/admin/tags',
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
        '/admin/tags',
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

function requestDelete(tag: AdminTag) {
    selectedTag.value = tag;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    deleteDialogOpen.value = false;
    selectedTag.value = null;
}

function confirmDelete() {
    if (!selectedTag.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/admin/tags/${selectedTag.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedTag.value = null;
        },
    });
}
</script>

<template>
    <Head title="Tags" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Tags"
            description="Manage image and blog tags."
        />

        <ActionToolbar align="end">
            <template #secondary>
                <Button as-child>
                    <Link href="/admin/tags/create">
                        Add Tag
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
                    v-for="(label, value) in tagTypes"
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

        <DataTable min-width="850px">
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
                        column="tag_type"
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
                        label="Actions"
                        align="right"
                    />
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="tag in tags"
                    :key="tag.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4 font-medium">
                        {{ tag.name }}
                    </td>

                    <td class="p-4 capitalize">
                        {{ tag.tag_type }}
                    </td>

                    <td class="p-4 font-mono text-xs">
                        {{ tag.slug }}
                    </td>

                    <td class="max-w-md p-4 text-muted-foreground">
                        {{ tag.description || '—' }}
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/tags/${tag.id}/edit`">
                                    Edit
                                </Link>
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                @click="requestDelete(tag)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="tags.length === 0"
                    :colspan="5"
                    message="No tags found."
                />
            </tbody>
        </DataTable>

        <ConfirmActionDialog
            v-model:open="deleteDialogOpen"
            title="Delete tag?"
            :description="
                selectedTag
                    ? `Delete the tag '${selectedTag.name}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Tag"
            destructive
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </div>
</template>
