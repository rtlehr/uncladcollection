<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Option = {
    id: number;
    name: string;
};

const props = defineProps<{
    collections: Option[];
    categories: Option[];
    tags: Option[];
}>();

const previewUrl = ref<string | null>(null);

const form = useForm({
    collection_id: '',
    title: '',
    description: '',
    photographer: '',
    sort_order: 0,
    is_active: true,
    image: null as File | null,
    categories: [] as number[],
    tags: [] as number[],
});

function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    form.image = file;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file ? URL.createObjectURL(file) : null;
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
    })).post('/admin/images', {
        forceFormData: true,
        preserveScroll: true,
    });
}

</script>

<template>
    <Head title="Create Image" />

    <div class="max-w-5xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Create Image</h1>
            <p class="text-sm text-muted-foreground">
                Upload a new image and assign it to collections, categories, and tags.
            </p>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Image Details</h2>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Title</label>
                            <Input v-model="form.title" placeholder="Enter image title" />
                            <p v-if="form.errors.title" class="text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Description</label>
                            <textarea
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
                            <label class="text-sm font-medium">Photographer</label>
                            <Input
                                v-model="form.photographer"
                                placeholder="Optional photographer name"
                            />
                            <p v-if="form.errors.photographer" class="text-sm text-red-600">
                                {{ form.errors.photographer }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Upload</h2>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Image File</label>

                            <Input
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                            />

                            <p class="text-xs text-muted-foreground">
                                The physical file will be stored on the server. The database stores only file paths.
                            </p>

                            <p v-if="form.errors.image" class="text-sm text-red-600">
                                {{ form.errors.image }}
                            </p>
                        </div>

                        <div v-if="previewUrl" class="space-y-2">
                            <label class="text-sm font-medium">Preview</label>

                            <img
                                :src="previewUrl"
                                alt="Image preview"
                                class="max-h-80 rounded-lg border object-contain"
                            />
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
                            <label class="text-sm font-medium">Collection</label>

                            <select
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
                            <label class="text-sm font-medium">Sort Order</label>
                            <Input v-model="form.sort_order" type="number" min="0" />
                            <p v-if="form.errors.sort_order" class="text-sm text-red-600">
                                {{ form.errors.sort_order }}
                            </p>
                        </div>

                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-input"
                            />

                            Active
                        </label>

                        <p v-if="form.errors.is_active" class="text-sm text-red-600">
                            {{ form.errors.is_active }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Uploading...' : 'Create Image' }}
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