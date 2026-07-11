<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import RichTextEditor from '@/components/admin/RichTextEditorV2.vue';
import BlogImageUploadField from '@/Components/Admin/BlogImageUploadField.vue';
import OptionChecklist from '@/Components/Admin/OptionChecklist.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import type {
    AdminBlogOption,
    AdminBlogPostDetail,
} from '@/types/adminBlog';

const props = defineProps<{
    blogPost: AdminBlogPostDetail;
    categories: AdminBlogOption[];
    tags: AdminBlogOption[];
    statuses: string[];
}>();

const form = useForm({
    title: props.blogPost.title ?? '',
    slug: props.blogPost.slug ?? '',
    excerpt: props.blogPost.excerpt ?? '',
    content: props.blogPost.content ?? '',
    featured_image: null as File | null,
    header_image: null as File | null,
    icon_image: null as File | null,
    status: props.blogPost.status ?? 'draft',
    published_at: props.blogPost.published_at ? props.blogPost.published_at.slice(0, 16) : '',
    expires_at: props.blogPost.expires_at ? props.blogPost.expires_at.slice(0, 16) : '',
    seo_title: props.blogPost.seo_title ?? '',
    seo_description: props.blogPost.seo_description ?? '',
    is_featured: props.blogPost.is_featured ?? false,
    is_active: props.blogPost.is_active ?? true,
    comments_enabled: props.blogPost.comments_enabled ?? true,
    comments_visible: props.blogPost.comments_visible ?? true,
    comments_require_approval: props.blogPost.comments_require_approval ?? false,
    category_ids: props.blogPost.category_ids ?? [],
    tag_ids: props.blogPost.tag_ids ?? [],
});

const generatedSlug = computed(() =>
    form.title.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
);

function useGeneratedSlug() {
    form.slug = generatedSlug.value;
}

function toggleSelection(field: 'category_ids' | 'tag_ids', id: number, checked: boolean) {
    form[field] = checked
        ? [...form[field], id]
        : form[field].filter((selectedId) => selectedId !== id);
}

function setFile(event: Event, field: 'featured_image' | 'header_image' | 'icon_image') {
    form[field] = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' }))
        .post(`/admin/blog-posts/${props.blogPost.slug}`, {
            forceFormData: true,
            preserveScroll: true,
        });
}

function cancel() {
    router.visit('/admin/blog-posts');
}
</script>

<template>
    <Head title="Edit Blog Post" />

    <AppLayout>
        <div class="space-y-8 p-6">
            <PageHeader title="Edit Blog Post" description="Update this blog article." />

            <form class="space-y-8" @submit.prevent="submit">
                <FormSection
                    title="Post Content"
                    description="Write the article title, summary, and full content."
                >
                    <FormGrid :columns="2">
                        <FormField label="Title" for-id="title" required :error="form.errors.title">
                            <Input id="title" v-model="form.title" placeholder="Blog post title" />
                        </FormField>

                        <FormField label="Slug" for-id="slug" required :error="form.errors.slug">
                            <div class="flex gap-2">
                                <Input id="slug" v-model="form.slug" placeholder="blog-post-slug" />
                                <Button type="button" variant="outline" @click="useGeneratedSlug">
                                    Generate
                                </Button>
                            </div>
                        </FormField>
                    </FormGrid>

                    <div class="mt-6">
                        <FormField label="Excerpt" for-id="excerpt" :error="form.errors.excerpt">
                            <textarea
                                id="excerpt"
                                v-model="form.excerpt"
                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Short summary of the article"
                            />
                        </FormField>
                    </div>

                    <div class="mt-6">
                        <FormField label="Content" for-id="content" required :error="form.errors.content">
                            <RichTextEditor v-model="form.content" />
                        </FormField>
                    </div>
                </FormSection>

                <FormSection
                    title="Images"
                    description="Upload the featured, header, and compact icon images."
                >
                    <div class="grid gap-6 md:grid-cols-3">
                        <BlogImageUploadField
                            id="featured_image"
                            label="Featured Image"
                            description="Used on blog cards and related posts."
                            :current-url="blogPost.featured_image_url"
                            :error="form.errors.featured_image"
                            @change="setFile($event, 'featured_image')"
                        />

                        <BlogImageUploadField
                            id="header_image"
                            label="Header Image"
                            description="Large image shown at the top of the article."
                            :current-url="blogPost.header_image_url"
                            :error="form.errors.header_image"
                            @change="setFile($event, 'header_image')"
                        />

                        <BlogImageUploadField
                            id="icon_image"
                            label="Icon Image"
                            description="Small icon used for compact displays."
                            :current-url="blogPost.icon_image_url"
                            preview-class="h-24 w-24"
                            :error="form.errors.icon_image"
                            @change="setFile($event, 'icon_image')"
                        />
                    </div>
                </FormSection>

                <FormSection
                    title="Publishing Schedule"
                    description="Control status, visibility, scheduling, and comment behavior."
                >
                    <FormGrid :columns="2">
                        <FormField label="Status" for-id="status" required :error="form.errors.status">
                            <select
                                id="status"
                                v-model="form.status"
                                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option v-for="item in statuses" :key="item" :value="item">
                                    {{ item }}
                                </option>
                            </select>
                        </FormField>

                        <FormField
                            label="Release Date"
                            for-id="published_at"
                            description="The article appears publicly on or after this date when published."
                            :error="form.errors.published_at"
                        >
                            <Input id="published_at" v-model="form.published_at" type="datetime-local" />
                        </FormField>

                        <FormField
                            label="End Date"
                            for-id="expires_at"
                            description="Leave blank to keep the article published indefinitely."
                            :error="form.errors.expires_at"
                        >
                            <Input id="expires_at" v-model="form.expires_at" type="datetime-local" />
                        </FormField>
                    </FormGrid>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-md border p-4">
                            <Checkbox
                                :checked="form.is_featured"
                                @update:checked="form.is_featured = Boolean($event)"
                            />
                            <div>
                                <div class="font-medium">Featured Post</div>
                                <p class="text-xs text-muted-foreground">
                                    Highlight this article in featured placements.
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 rounded-md border p-4">
                            <Checkbox
                                :checked="form.is_active"
                                @update:checked="form.is_active = Boolean($event)"
                            />
                            <div>
                                <div class="font-medium">Active</div>
                                <p class="text-xs text-muted-foreground">
                                    Active posts can appear publicly when their schedule permits.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-6 rounded-md border p-4">
                        <h3 class="font-medium">Comment Settings</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Control visibility, posting, and moderation.
                        </p>

                        <div class="mt-4 space-y-3">
                            <label class="flex items-center gap-3 text-sm">
                                <Checkbox
                                    :checked="form.comments_enabled"
                                    @update:checked="form.comments_enabled = Boolean($event)"
                                />
                                Allow members to post comments
                            </label>

                            <label class="flex items-center gap-3 text-sm">
                                <Checkbox
                                    :checked="form.comments_visible"
                                    @update:checked="form.comments_visible = Boolean($event)"
                                />
                                Show comments on the public article page
                            </label>

                            <label class="flex items-center gap-3 text-sm">
                                <Checkbox
                                    :checked="form.comments_require_approval"
                                    @update:checked="form.comments_require_approval = Boolean($event)"
                                />
                                Require approval before comments appear
                            </label>
                        </div>
                    </div>
                </FormSection>

                <div class="grid gap-6 lg:grid-cols-2">
                    <FormSection
                        title="Categories"
                        description="Select blog categories."
                    >
                        <OptionChecklist
                            :options="categories"
                            :selected-ids="form.category_ids"
                            empty-message="No blog categories are available."
                            :disabled="form.processing"
                            @toggle="(id, checked) => toggleSelection('category_ids', id, checked)"
                        />
                    </FormSection>

                    <FormSection
                        title="Tags"
                        description="Select blog tags."
                    >
                        <OptionChecklist
                            :options="tags"
                            :selected-ids="form.tag_ids"
                            empty-message="No blog tags are available."
                            :disabled="form.processing"
                            @toggle="(id, checked) => toggleSelection('tag_ids', id, checked)"
                        />
                    </FormSection>
                </div>

                <FormSection
                    title="SEO"
                    description="Optional search-engine title and description."
                >
                    <FormField label="SEO Title" for-id="seo_title" :error="form.errors.seo_title">
                        <Input id="seo_title" v-model="form.seo_title" placeholder="Optional SEO title" />
                    </FormField>

                    <div class="mt-6">
                        <FormField
                            label="SEO Description"
                            for-id="seo_description"
                            :error="form.errors.seo_description"
                        >
                            <textarea
                                id="seo_description"
                                v-model="form.seo_description"
                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Optional SEO description"
                            />
                        </FormField>
                    </div>
                </FormSection>

                <FormActions
                    submit-label="Update Blog Post"
                    processing-label="Saving..."
                    :processing="form.processing"
                    @submit="submit"
                    @cancel="cancel"
                />
            </form>
        </div>
    </AppLayout>
</template>

