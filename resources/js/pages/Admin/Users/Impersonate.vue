<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { LogIn, Search, UserRoundCheck } from '@lucide/vue';
import { ref } from 'vue';

import SearchToolbar from '@/components/Admin/SearchToolbar.vue';
import PageHeader from '@/components/Shared/PageHeader.vue';
import StatusBadge from '@/components/Shared/StatusBadge.vue';
import DataTable from '@/components/Tables/DataTable.vue';
import DataTableEmpty from '@/components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

type ImpersonationUser = {
    id: number;
    name: string;
    username: string | null;
    email: string;
    is_disabled: boolean;
    roles: string[];
    can_impersonate: boolean;
    unavailable_reason: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: ImpersonationUser[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search: string;
        direction: 'asc' | 'desc';
    };
    impersonationActive: boolean;
}>();

const search = ref(props.filters.search ?? '');

function load(parameters: Record<string, string | undefined>): void {
    router.get('/admin/users/impersonation', parameters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function runSearch(): void {
    load({
        search: search.value || undefined,
        direction: props.filters.direction,
    });
}

function resetSearch(): void {
    search.value = '';
    load({ direction: props.filters.direction });
}

function sortByName(): void {
    load({
        search: search.value || undefined,
        direction: props.filters.direction === 'asc' ? 'desc' : 'asc',
    });
}

function impersonate(user: ImpersonationUser): void {
    if (!user.can_impersonate || props.impersonationActive) return;

    router.post(`/admin/users/${user.id}/impersonate`, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Impersonate User" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Impersonate User"
            description="Search customer accounts and temporarily view the marketplace exactly as that customer sees it."
        />

        <div
            v-if="impersonationActive"
            class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
        >
            Stop the current impersonation session before starting another one.
        </div>

        <SearchToolbar
            v-model="search"
            placeholder="Search by name, username, or email..."
            @search="runSearch"
            @reset="resetSearch"
        />

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <p>
                <template v-if="users.total">
                    Showing {{ users.from }}–{{ users.to }} of {{ users.total }} users
                </template>
                <template v-else>No matching users</template>
            </p>

            <div class="inline-flex items-center gap-2">
                <Search class="h-4 w-4" aria-hidden="true" />
                Customer support view
            </div>
        </div>

        <DataTable min-width="850px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell
                        label="Name"
                        column="name"
                        sortable
                        current-sort="name"
                        :current-direction="filters.direction"
                        @sort="sortByName"
                    />
                    <DataTableHeaderCell label="Username" />
                    <DataTableHeaderCell label="Email" />
                    <DataTableHeaderCell label="Status" />
                    <DataTableHeaderCell label="Roles" />
                    <DataTableHeaderCell label="Action" align="right" />
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="user in users.data"
                    :key="user.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4 font-medium">{{ user.name }}</td>
                    <td class="p-4">{{ user.username || '—' }}</td>
                    <td class="p-4">{{ user.email }}</td>
                    <td class="p-4">
                        <StatusBadge :status="user.is_disabled ? 'disabled' : 'active'" />
                    </td>
                    <td class="p-4">{{ user.roles.join(', ') || 'Customer' }}</td>
                    <td class="p-4 text-right">
                        <div class="flex flex-col items-end gap-1">
                            <Button
                                size="sm"
                                :disabled="!user.can_impersonate || impersonationActive"
                                @click="impersonate(user)"
                            >
                                <LogIn class="mr-2 h-4 w-4" aria-hidden="true" />
                                Impersonate User
                            </Button>
                            <span
                                v-if="user.unavailable_reason"
                                class="max-w-xs text-xs text-muted-foreground"
                            >
                                {{ user.unavailable_reason }}
                            </span>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="users.data.length === 0"
                    :colspan="6"
                    message="No users match your search."
                />
            </tbody>
        </DataTable>

        <nav
            v-if="users.links.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
            aria-label="User pagination"
        >
            <Button
                v-for="link in users.links"
                :key="link.label"
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
            >
                <span v-html="link.label" />
            </Button>
        </nav>
    </div>
</template>
