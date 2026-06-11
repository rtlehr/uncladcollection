<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Option = {
    id: number;
    name: string;
};

type ImageRecord = {
    id: number;
    collection_id: number | null;
    title: string;
    slug: string;
    description: string | null;
    original_url: string | null;
    high_res_url: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    photographer: string | null;
    sort_order: number;
    is_active: boolean;
    is_ai_generated: boolean;
    downloads_count: number;
    favorites_count: number;
    purchases_count: number;
    views_count: number;
    categories: number[];
    tags: number[];
};

const props = defineProps<{
    imageRecord: ImageRecord;
    collections: Option[];
    categories: Option[];
    tags: Option[];
}>();

const previewUrl = ref<string | null>(props.imageRecord.thumbnail_url);

const form = useForm({
    collection_id: props.imageRecord.collection_id ?? '',
    title: props.imageRecord.title,
    description: props.imageRecord.description ?? '',
    photographer: props.imageRecord.photographer ?? '',
    sort_order: props.imageRecord.sort_order,
    is_active: props.imageRecord.is_active,
    is_ai_generated: props.imageRecord.is_ai_generated,
    downloads_count: props.imageRecord.downloads_count,
    favorites_count: props.imageRecord.favorites_count,
    purchases_count: props.imageRecord.purchases_count,
    views_count: props.imageRecord.views_count,
    image: null as File | null,
    categories: [...props.imageRecord.categories],
    tags: [...props.imageRecord.tags],
});

function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    form.image = file;

    if (previewUrl.value && previewUrl.value !== props.imageRecord.thumbnail_url) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file
        ? URL.createObjectURL(file)
        : props.imageRecord.thumbnail_url;
}

function toggleCategory(categoryId: number, checked: boolean) {
    form.categories = checked
        ? [...form.categories, categoryId]
        : form.categories.filter((id) => id !== categoryId);
}

function toggleTag(tagId: number, checked: boolean) {
    form.tags = checked
        ? [...form.tags, tagId]
        : form.tags.filter((id) => id !== tagId);
}

function submit() {
    form.transform((data) => ({
        ...data,
        collection_id: data.collection_id === '' ? null : data.collection_id,
        _method: 'put',
    })).post(`/admin/images/${props.imageRecord.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Image" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Edit Image</h1>

            <p class="text-sm text-muted-foreground">
                Update image details, replace the uploaded file, and manage categories and tags.
            </p>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Image Details</h2>

                        <div class="space-y-2">
                            <Label for="title">Title</Label>

                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Enter image title"
                            />

                            <p v-if="form.errors.title" class="text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Slug</Label>

                            <div class="rounded-md border bg-muted px-3 py-2 font-mono text-sm text-muted-foreground">
                                {{ imageRecord.slug }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>

                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Optional image description"
                            />

                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="photographer">Photographer</Label>

                            <Input
                                id="photographer"
                                v-model="form.photographer"
                                placeholder="Optional photographer name"
                            />

                            <p v-if="form.errors.photographer" class="text-sm text-red-600">
                                {{ form.errors.photographer }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Image File</h2>

                        <div v-if="previewUrl" class="space-y-2">
                            <Label>Current Preview</Label>

                            <div class="rounded-lg border bg-muted p-4">
                                <img
                                    :src="previewUrl"
                                    :alt="form.title"
                                    class="max-h-80 w-full rounded object-contain"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="image">Replace Image</Label>

                            <Input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                            />

                            <p class="text-xs text-muted-foreground">
                                Leave this blank to keep the current image.
                            </p>

                            <p v-if="form.errors.image" class="text-sm text-red-600">
                                {{ form.errors.image }}
                            </p>
                        </div>

                        <div class="grid gap-3 text-xs text-muted-foreground md:grid-cols-2">
                            <div class="rounded-md border p-3">
                                <span class="font-medium">Original:</span>
                                {{ imageRecord.original_url ? 'Available' : 'Missing' }}
                            </div>

                            <div class="rounded-md border p-3">
                                <span class="font-medium">High Res:</span>
                                {{ imageRecord.high_res_url ? 'Available' : 'Missing' }}
                            </div>

                            <div class="rounded-md border p-3">
                                <span class="font-medium">Thumbnail:</span>
                                {{ imageRecord.thumbnail_url ? 'Available' : 'Missing' }}
                            </div>

                            <div class="rounded-md border p-3">
                                <span class="font-medium">Icon:</span>
                                {{ imageRecord.icon_url ? 'Available' : 'Missing' }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Categories</h2>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label
                                v-for="category in categories"
                                :key="category.id"
                                class="flex items-center gap-3 rounded-md border p-3"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300"
                                    :checked="form.categories.includes(category.id)"
                                    @change="toggleCategory(
                                        category.id,
                                        ($event.target as HTMLInputElement).checked
                                    )"
                                />

                                <span class="text-sm font-medium">
                                    {{ category.name }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Tags</h2>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label
                                v-for="tag in tags"
                                :key="tag.id"
                                class="flex items-center gap-3 rounded-md border p-3"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300"
                                    :checked="form.tags.includes(tag.id)"
                                    @change="toggleTag(
                                        tag.id,
                                        ($event.target as HTMLInputElement).checked
                                    )"
                                />

                                <span class="text-sm font-medium">
                                    {{ tag.name }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Publishing</h2>

                        <div class="space-y-2">
                            <Label for="collection_id">Collection</Label>

                            <select
                                id="collection_id"
                                v-model="form.collection_id"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">No Collection</option>

                                <option
                                    v-for="collection in collections"
                                    :key="collection.id"
                                    :value="collection.id"
                                >
                                    {{ collection.name }}
                                </option>
                            </select>

                            <p v-if="form.errors.collection_id" class="text-sm text-red-600">
                                {{ form.errors.collection_id }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="sort_order">Sort Order</Label>

                            <Input
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                            />

                            <p v-if="form.errors.sort_order" class="text-sm text-red-600">
                                {{ form.errors.sort_order }}
                            </p>
                        </div>

                        <label class="flex items-start gap-3 rounded-md border p-3">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-input"
                            />

                            <div>
                                <div class="text-sm font-medium">
                                    Active
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Active images can be displayed on the public site.
                                </p>
                            </div>
                        </label>

                        <p v-if="form.errors.is_active" class="text-sm text-red-600">
                            {{ form.errors.is_active }}
                        </p>

                        <label class="flex items-start gap-3 rounded-md border p-3">
                            <input
                                v-model="form.is_ai_generated"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-input"
                            />

                            <div>
                                <div class="text-sm font-medium">
                                    AI Generated
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Check this if the image was created or significantly assisted by AI.
                                </p>
                            </div>
                        </label>

                        <p v-if="form.errors.is_ai_generated" class="text-sm text-red-600">
                            {{ form.errors.is_ai_generated }}
                        </p>
                    </div>

                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold">Statistics</h2>

                        <div class="grid gap-4">
                            <div class="space-y-2">
                                <Label for="downloads_count">Downloads</Label>

                                <Input
                                    id="downloads_count"
                                    v-model="form.downloads_count"
                                    type="number"
                                    min="0"
                                />

                                <p v-if="form.errors.downloads_count" class="text-sm text-red-600">
                                    {{ form.errors.downloads_count }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="favorites_count">Favorites</Label>

                                <Input
                                    id="favorites_count"
                                    v-model="form.favorites_count"
                                    type="number"
                                    min="0"
                                />

                                <p v-if="form.errors.favorites_count" class="text-sm text-red-600">
                                    {{ form.errors.favorites_count }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="purchases_count">Purchases</Label>

                                <Input
                                    id="purchases_count"
                                    v-model="form.purchases_count"
                                    type="number"
                                    min="0"
                                />

                                <p v-if="form.errors.purchases_count" class="text-sm text-red-600">
                                    {{ form.errors.purchases_count }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="views_count">Views</Label>

                                <Input
                                    id="views_count"
                                    v-model="form.views_count"
                                    type="number"
                                    min="0"
                                />

                                <p v-if="form.errors.views_count" class="text-sm text-red-600">
                                    {{ form.errors.views_count }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Image' }}
                        </Button>

                        <Button type="button" variant="outline" as-child>
                            <Link href="/admin/images">
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>