<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Collection = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    created_at: string;
};

const props = defineProps<{
    collections: Collection[];
    filters: {
        search: string;
        status: string;
        sort: string;
        direction: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function reload() {
    router.get('/admin/collections', {
        search: search.value,
        status: status.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    status.value = '';

    router.get('/admin/collections', {}, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get('/admin/collections', {
        search: search.value,
        status: status.value,
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

function deleteCollection(collection: Collection) {
    if (!confirm(`Delete collection "${collection.name}"?`)) {
        return;
    }

    router.delete(`/admin/collections/${collection.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Collections" />

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Collections</h1>
                <p class="text-sm text-muted-foreground">
                    Manage image collections.
                </p>
            </div>

            <Button as-child>
                <Link href="/admin/collections/create">
                    Add Collection
                </Link>
            </Button>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <Input
                v-model="search"
                class="max-w-sm"
                placeholder="Search name, slug, or description..."
                @keyup.enter="reload"
            />

            <select
                v-model="status"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
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

            <Button type="button" @click="reload">
                Search
            </Button>

            <Button type="button" variant="outline" @click="resetFilters">
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

                        <th class="cursor-pointer p-4" @click="sortBy('slug')">
                            Slug {{ sortIndicator('slug') }}
                        </th>

                        <th class="p-4">
                            Description
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('sort_order')">
                            Sort {{ sortIndicator('sort_order') }}
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('is_active')">
                            Status {{ sortIndicator('is_active') }}
                        </th>

                        <th class="p-4 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="collection in collections"
                        :key="collection.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-4 font-medium">
                            {{ collection.name }}
                        </td>

                        <td class="p-4 font-mono text-xs">
                            {{ collection.slug }}
                        </td>

                        <td class="p-4 text-muted-foreground">
                            {{ collection.description || '—' }}
                        </td>

                        <td class="p-4">
                            {{ collection.sort_order }}
                        </td>

                        <td class="p-4">
                            <span
                                :class="collection.is_active
                                    ? 'font-medium text-green-600'
                                    : 'font-medium text-red-600'"
                            >
                                {{ collection.is_active ? 'Active' : 'Inactive' }}
                            </span>
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
                                    @click="deleteCollection(collection)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="collections.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">
                            No collections found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>