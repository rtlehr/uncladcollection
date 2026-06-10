<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category_type: string;
    sort_order: number;
    is_active: boolean;
    created_at: string;
};

const props = defineProps<{
    categories: Category[];
    filters: {
        search: string;
        type: string;
        sort: string;
        direction: string;
    };
    categoryTypes: Record<string, string>;
}>();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');

function reload() {
    router.get('/admin/categories', {
        search: search.value,
        type: type.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    type.value = '';

    router.get('/admin/categories', {}, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get('/admin/categories', {
        search: search.value,
        type: type.value,
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

function deleteCategory(category: Category) {
    if (!confirm(`Delete category "${category.name}"?`)) {
        return;
    }

    router.delete(`/admin/categories/${category.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Categories" />

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Categories</h1>
                <p class="text-sm text-muted-foreground">
                    Manage image and blog categories.
                </p>
            </div>

            <Button as-child>
                <Link href="/admin/categories/create">
                    Add Category
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
                v-model="type"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                @change="reload"
            >
                <option value="">All Types</option>
                <option
                    v-for="(label, value) in categoryTypes"
                    :key="value"
                    :value="value"
                >
                    {{ label }}
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

                        <th class="cursor-pointer p-4" @click="sortBy('category_type')">
                            Type {{ sortIndicator('category_type') }}
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('slug')">
                            Slug {{ sortIndicator('slug') }}
                        </th>

                        <th class="p-4">Description</th>

                        <th class="cursor-pointer p-4" @click="sortBy('sort_order')">
                            Sort {{ sortIndicator('sort_order') }}
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('is_active')">
                            Status {{ sortIndicator('is_active') }}
                        </th>

                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="category in categories"
                        :key="category.id"
                        class="border-b last:border-0"
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

                        <td class="p-4 text-muted-foreground">
                            {{ category.description || '—' }}
                        </td>

                        <td class="p-4">
                            {{ category.sort_order }}
                        </td>

                        <td class="p-4">
                            <span
                                :class="category.is_active ? 'font-medium text-green-600' : 'font-medium text-red-600'"
                            >
                                {{ category.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/categories/${category.id}/edit`">
                                        Edit
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="deleteCategory(category)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="categories.length === 0">
                        <td colspan="7" class="p-6 text-center text-muted-foreground">
                            No categories found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>