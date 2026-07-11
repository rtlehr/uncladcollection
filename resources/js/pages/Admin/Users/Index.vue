<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminUserFilters,
    AdminUserListItem,
} from '@/types/user';

const props = defineProps<{
    users: AdminUserListItem[];
    filters: AdminUserFilters;
}>();

const search = ref(props.filters.search ?? '');

function runSearch() {
    router.get(
        '/admin/users',
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

function resetSearch() {
    search.value = '';

    router.get(
        '/admin/users',
        {
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

function sortBy(column: string) {
    const direction =
        props.filters.sort === column
        && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        '/admin/users',
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
    <Head title="Users" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Users"
            description="Manage user accounts, roles, and permissions."
        />

        <SearchToolbar
            v-model="search"
            placeholder="Search name, username, or email..."
            @search="runSearch"
            @reset="resetSearch"
        />

        <DataTable min-width="1050px">
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
                        label="Username"
                        column="username"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell
                        label="Email"
                        column="email"
                        sortable
                        :current-sort="filters.sort"
                        :current-direction="filters.direction"
                        @sort="sortBy"
                    />

                    <DataTableHeaderCell label="Status" />
                    <DataTableHeaderCell label="Roles" />
                    <DataTableHeaderCell label="Permissions" />

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
                    v-for="user in users"
                    :key="user.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4 font-medium">
                        {{ user.name }}
                    </td>

                    <td class="p-4">
                        {{ user.username || '—' }}
                    </td>

                    <td class="p-4">
                        {{ user.email }}
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="user.is_disabled ? 'disabled' : 'active'"
                        />
                    </td>

                    <td class="p-4">
                        {{ user.roles.join(', ') || 'None' }}
                    </td>

                    <td class="p-4">
                        {{ user.all_permissions_count }}
                    </td>

                    <td class="p-4">
                        {{ user.created_at }}
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/users/${user.id}`">
                                    View
                                </Link>
                            </Button>

                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/users/${user.id}/edit`">
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="users.length === 0"
                    :colspan="8"
                    message="No users found."
                />
            </tbody>
        </DataTable>
    </div>
</template>
