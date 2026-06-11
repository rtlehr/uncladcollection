<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type User = {
    id: number;
    name: string;
    username: string | null;
    email: string;
    is_disabled: boolean;
    roles: string[];
    direct_permissions_count: number;
    all_permissions_count: number;
    created_at: string;
};

const props = defineProps<{
    users: User[];
    filters: {
        search: string;
        sort: string;
        direction: string;
    };
}>();

const search = ref(props.filters.search ?? '');

function runSearch() {
    router.get('/admin/users', {
        search: search.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get('/admin/users', {
        search: search.value,
        sort: column,
        direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function sortIndicator(column: string) {
    if (props.filters.sort !== column) {
        return '↕';
    }

    return props.filters.direction === 'asc' ? '↑' : '↓';
}
</script>

<template>
    <Head title="Users" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Users</h1>
            <p class="text-sm text-muted-foreground">
                Manage user accounts, roles, and permissions.
            </p>
        </div>

        <div class="mb-4 flex gap-3">
            <Input
                v-model="search"
                placeholder="Search name, username, or email..."
                @keyup.enter="runSearch"
            />

            <Button type="button" @click="runSearch">
                Search
            </Button>

            <Button
                type="button"
                variant="outline"
                @click="search = ''; runSearch()"
            >
                Reset
            </Button>
        </div>

        <div class="rounded-lg border bg-card shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="cursor-pointer p-4" @click="sortBy('name')">
                            Name {{ sortIndicator('name') }}
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('username')">
                            Username {{ sortIndicator('username') }}
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('email')">
                            Email {{ sortIndicator('email') }}
                        </th>

                        <th class="p-4">Status</th>
                        <th class="p-4">Roles</th>
                        <th class="p-4">Permissions</th>

                        <th class="cursor-pointer p-4" @click="sortBy('created_at')">
                            Created {{ sortIndicator('created_at') }}
                        </th>

                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-b last:border-0"
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
                            <span
                                :class="user.is_disabled ? 'font-medium text-red-600' : 'font-medium text-green-600'"
                            >
                                {{ user.is_disabled ? 'Disabled' : 'Active' }}
                            </span>
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
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/users/${user.id}`">
                                        View
                                    </Link>
                                </Button>

                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/users/${user.id}/edit`">
                                        Edit
                                    </Link>
                                </Button>
                            </div>
                        </td>

                    </tr>

                    <tr v-if="users.length === 0">
                        <td colspan="8" class="p-6 text-center text-muted-foreground">
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>