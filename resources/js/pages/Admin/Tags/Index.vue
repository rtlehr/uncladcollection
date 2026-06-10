<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Tag = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    tag_type: string;
    created_at: string;
};

const props = defineProps<{
    tags: Tag[];
    filters: {
        search: string;
        type: string;
        sort: string;
        direction: string;
    };
    tagTypes: Record<string, string>;
}>();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');

function reload() {
    router.get('/admin/tags', {
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

    router.get('/admin/tags', {}, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get('/admin/tags', {
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

function deleteTag(tag: Tag) {
    if (!confirm(`Delete tag "${tag.name}"?`)) {
        return;
    }

    router.delete(`/admin/tags/${tag.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Tags" />

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Tags</h1>
                <p class="text-sm text-muted-foreground">
                    Manage image and blog tags.
                </p>
            </div>

            <Button as-child>
                <Link href="/admin/tags/create">
                    Add Tag
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
                    v-for="(label, value) in tagTypes"
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

                        <th class="cursor-pointer p-4" @click="sortBy('tag_type')">
                            Type {{ sortIndicator('tag_type') }}
                        </th>

                        <th class="cursor-pointer p-4" @click="sortBy('slug')">
                            Slug {{ sortIndicator('slug') }}
                        </th>

                        <th class="p-4">Description</th>

                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="tag in tags"
                        :key="tag.id"
                        class="border-b last:border-0"
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

                        <td class="p-4 text-muted-foreground">
                            {{ tag.description || '—' }}
                        </td>

                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/tags/${tag.id}/edit`">
                                        Edit
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="deleteTag(tag)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="tags.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">
                            No tags found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>