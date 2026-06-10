<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    category_type: 'image',
    sort_order: 0,
    is_active: true,
});

function generateSlug() {
    form.slug = form.name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function submit() {
    form.post('/admin/categories', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Create Category" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Create Category</h1>
            <p class="text-sm text-muted-foreground">
                Create a new image or blog category.
            </p>
        </div>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    placeholder="Category name"
                    @blur="generateSlug"
                />
                <p v-if="form.errors.name" class="text-sm text-red-600">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    placeholder="category-slug"
                />
                <p class="text-xs text-muted-foreground">
                    Used in URLs. Example: image-lifestyle or blog-travel.
                </p>
                <p v-if="form.errors.slug" class="text-sm text-red-600">
                    {{ form.errors.slug }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="category_type">Type</Label>
                <select
                    id="category_type"
                    v-model="form.category_type"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                    <option value="image">Image</option>
                    <option value="blog">Blog</option>
                </select>

                <p v-if="form.errors.category_type" class="text-sm text-red-600">
                    {{ form.errors.category_type }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>

                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    placeholder="Optional category description"
                />

                <p v-if="form.errors.description" class="text-sm text-red-600">
                    {{ form.errors.description }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="sort_order">Sort Order</Label>

                <Input
                    id="sort_order"
                    v-model="form.sort_order"
                    type="number"
                    min="0"
                />

                <p class="text-xs text-muted-foreground">
                    Lower numbers appear first.
                </p>

                <p v-if="form.errors.sort_order" class="text-sm text-red-600">
                    {{ form.errors.sort_order }}
                </p>
            </div>

            <div class="rounded-lg border bg-card p-4">
                <label class="flex items-start gap-3">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="mt-1 h-4 w-4 rounded border-gray-300"
                    />

                    <div>
                        <div class="font-medium">
                            Active
                        </div>

                        <div class="text-sm text-muted-foreground">
                            Active categories will be available throughout the site.
                        </div>
                    </div>
                </label>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Creating...' : 'Create Category' }}
                </Button>

                <Button variant="outline" as-child>
                    <Link href="/admin/categories">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>