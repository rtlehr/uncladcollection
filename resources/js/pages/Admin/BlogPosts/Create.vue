<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';



import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import RichTextEditor from '@/components/admin/RichTextEditorV2.vue';

interface Category {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

const props = defineProps<{
    categories: Category[];
    tags: Tag[];
    statuses: string[];
}>();

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    content: '',

    featured_image: null as File | null,
    header_image: null as File | null,
    icon_image: null as File | null,

    status: 'draft',
    published_at: '',
    expires_at: '',

    seo_title: '',
    seo_description: '',

    is_featured: false,
    is_active: true,

    category_ids: [] as number[],
    tag_ids: [] as number[],
});

const generatedSlug = computed(() => {
    return form.title
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
});

function useGeneratedSlug() {
    form.slug = generatedSlug.value;
}

function toggleCategory(categoryId: number) {
    form.category_ids = form.category_ids.includes(categoryId)
        ? form.category_ids.filter((id) => id !== categoryId)
        : [...form.category_ids, categoryId];
}

function toggleTag(tagId: number) {
    form.tag_ids = form.tag_ids.includes(tagId)
        ? form.tag_ids.filter((id) => id !== tagId)
        : [...form.tag_ids, tagId];
}

function setFile(
    event: Event,
    field: 'featured_image' | 'header_image' | 'icon_image'
) {
    const input = event.target as HTMLInputElement;
    form[field] = input.files?.[0] ?? null;
}

function submit() {
    form.post('/admin/blog-posts', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Create Blog Post" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Create Blog Post
                    </h1>

                    <p class="text-muted-foreground">
                        Add a new article to the blog.
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <Link href="/admin/blog-posts">
                        Back to Blog Posts
                    </Link>
                </Button>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Post Content</CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="title">Title</Label>

                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Blog post title"
                            />

                            <p v-if="form.errors.title" class="text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="slug">Slug</Label>

                            <div class="flex gap-2">
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    placeholder="blog-post-slug"
                                />

                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="useGeneratedSlug"
                                >
                                    Generate
                                </Button>
                            </div>

                            <p v-if="form.errors.slug" class="text-sm text-red-600">
                                {{ form.errors.slug }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="excerpt">Excerpt</Label>

                            <textarea
                                id="excerpt"
                                v-model="form.excerpt"
                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Short summary of the article"
                            />

                            <p v-if="form.errors.excerpt" class="text-sm text-red-600">
                                {{ form.errors.excerpt }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="content">Content</Label>

                            <RichTextEditor v-model="form.content" />

                            <p v-if="form.errors.content" class="text-sm text-red-600">
                                {{ form.errors.content }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Images</CardTitle>
                    </CardHeader>

                    <CardContent class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="featured_image">Featured Image</Label>

                            <Input
                                id="featured_image"
                                type="file"
                                accept="image/*"
                                @change="setFile($event, 'featured_image')"
                            />

                            <p class="text-xs text-muted-foreground">
                                Used on blog cards and related posts.
                            </p>

                            <p v-if="form.errors.featured_image" class="text-sm text-red-600">
                                {{ form.errors.featured_image }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="header_image">Header Image</Label>

                            <Input
                                id="header_image"
                                type="file"
                                accept="image/*"
                                @change="setFile($event, 'header_image')"
                            />

                            <p class="text-xs text-muted-foreground">
                                Large image shown at the top of the article.
                            </p>

                            <p v-if="form.errors.header_image" class="text-sm text-red-600">
                                {{ form.errors.header_image }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="icon_image">Icon Image</Label>

                            <Input
                                id="icon_image"
                                type="file"
                                accept="image/*"
                                @change="setFile($event, 'icon_image')"
                            />

                            <p class="text-xs text-muted-foreground">
                                Small icon used for compact displays.
                            </p>

                            <p v-if="form.errors.icon_image" class="text-sm text-red-600">
                                {{ form.errors.icon_image }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Publishing Schedule</CardTitle>
                    </CardHeader>

                    <CardContent class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="status">Status</Label>

                            <select
                                id="status"
                                v-model="form.status"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            >
                                <option
                                    v-for="status in statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ status }}
                                </option>
                            </select>

                            <p v-if="form.errors.status" class="text-sm text-red-600">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="published_at">Release Date</Label>

                            <Input
                                id="published_at"
                                v-model="form.published_at"
                                type="datetime-local"
                            />

                            <p class="text-xs text-muted-foreground">
                                The article will appear publicly on or after this date when status is published.
                            </p>

                            <p v-if="form.errors.published_at" class="text-sm text-red-600">
                                {{ form.errors.published_at }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="expires_at">End Date</Label>

                            <Input
                                id="expires_at"
                                v-model="form.expires_at"
                                type="datetime-local"
                            />

                            <p class="text-xs text-muted-foreground">
                                Optional. Leave blank to keep this article published forever.
                            </p>

                            <p v-if="form.errors.expires_at" class="text-sm text-red-600">
                                {{ form.errors.expires_at }}
                            </p>
                        </div>

                        <div class="space-y-3 rounded-md border p-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.is_featured" type="checkbox" />
                                Featured Post
                            </label>

                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.is_active" type="checkbox" />
                                Active
                            </label>

                            <p class="text-xs text-muted-foreground">
                                A post must be active, published, and within its release window to appear publicly.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Categories & Tags</CardTitle>
                    </CardHeader>

                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-3">
                            <Label>Blog Categories</Label>

                            <div class="space-y-2">
                                <label
                                    v-for="category in props.categories"
                                    :key="category.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.category_ids.includes(category.id)"
                                        @change="toggleCategory(category.id)"
                                    />

                                    {{ category.name }}
                                </label>
                            </div>

                            <p v-if="form.errors.category_ids" class="text-sm text-red-600">
                                {{ form.errors.category_ids }}
                            </p>
                        </div>

                        <div class="space-y-3">
                            <Label>Blog Tags</Label>

                            <div class="space-y-2">
                                <label
                                    v-for="tag in props.tags"
                                    :key="tag.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.tag_ids.includes(tag.id)"
                                        @change="toggleTag(tag.id)"
                                    />

                                    {{ tag.name }}
                                </label>
                            </div>

                            <p v-if="form.errors.tag_ids" class="text-sm text-red-600">
                                {{ form.errors.tag_ids }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>SEO</CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="seo_title">SEO Title</Label>

                            <Input
                                id="seo_title"
                                v-model="form.seo_title"
                                placeholder="Optional SEO title"
                            />

                            <p v-if="form.errors.seo_title" class="text-sm text-red-600">
                                {{ form.errors.seo_title }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="seo_description">SEO Description</Label>

                            <textarea
                                id="seo_description"
                                v-model="form.seo_description"
                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Optional SEO description"
                            />

                            <p v-if="form.errors.seo_description" class="text-sm text-red-600">
                                {{ form.errors.seo_description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" as-child>
                        <Link href="/admin/blog-posts">
                            Cancel
                        </Link>
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        Create Blog Post
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>