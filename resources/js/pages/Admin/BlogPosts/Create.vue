<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminSectionNavigator from '@/components/admin/AdminSectionNavigator.vue';

import RichTextEditor from '@/components/admin/RichTextEditorV2.vue';
import BlogAiAssistantPanel from '@/components/admin/BlogAiAssistantPanel.vue';
import BlogEditedImageField from '@/components/admin/BlogEditedImageField.vue';
import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';
import {
    BLOG_HEADER_PRESET,
    BLOG_ICON_PRESET,
} from '@/config/imageEditorPresets';
import OptionChecklist from '@/Components/Admin/OptionChecklist.vue';
import CreatableTagInput from '@/components/admin/tags/CreatableTagInput.vue';
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
    categories: AdminBlogOption[];
    tags: AdminBlogOption[];
    statuses: string[];
}>();

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    header_image: null as File | null,
    header_image_original: null as File | null,
    header_image_edit_data: null as string | null,
    icon_image: null as File | null,
    icon_image_original: null as File | null,
    icon_image_edit_data: null as string | null,
    status: 'draft',
    published_at: '',
    expires_at: '',
    seo_title: '',
    seo_description: '',
    is_featured: false,
    is_active: true,
    comments_enabled: true,
    comments_visible: true,
    comments_require_approval: false,
    category_ids: [] as number[],
    tag_names: [] as string[],
    ai_analysis_json: null as string | null,
    ai_analysis_settings_json: null as string | null,
});

const generatedSlug = computed(() =>
    form.title.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
);

function useGeneratedSlug() {
    form.slug = generatedSlug.value;
}

function toggleSelection(field: 'category_ids', id: number, checked: boolean) {
    form[field] = checked
        ? [...form[field], id]
        : form[field].filter((selectedId) => selectedId !== id);
}

function applyHeaderImage(payload: {
    file: File;
    original: File | null;
    edit: ImageEditData;
}): void {
    form.header_image = payload.file;
    form.header_image_original = payload.original;
    form.header_image_edit_data = JSON.stringify(payload.edit);
}

function applyIconImage(payload: {
    file: File;
    original: File | null;
    edit: ImageEditData;
}): void {
    form.icon_image = payload.file;
    form.icon_image_original = payload.original;
    form.icon_image_edit_data = JSON.stringify(payload.edit);
}


function storeAiAnalysis(result: Record<string, any>, settings: Record<string, any>): void {
    form.ai_analysis_json = JSON.stringify(result);
    form.ai_analysis_settings_json = JSON.stringify(settings);
}

function applyGeneratedTags(tags: string[]): void {
    const merged = [...form.tag_names];

    for (const tag of tags) {
        const value = tag.trim();
        if (!value) continue;
        if (!merged.some((item) => item.toLocaleLowerCase() === value.toLocaleLowerCase())) {
            merged.push(value);
        }
    }

    form.tag_names = merged;
}

const adminSections = [
    { id: 'blog-content', title: 'Content', description: 'Title, excerpt, and article body.', errorKeys: ['title', 'slug', 'excerpt', 'content'] },
    { id: 'blog-ai', title: 'AI Assistant', description: 'Review content, SEO, and image concepts.', errorKeys: [] },
    { id: 'blog-images', title: 'Images', description: 'Header and icon presentation.', errorKeys: ['header_image', 'icon_image'] },
    { id: 'blog-publishing', title: 'Publishing', description: 'Schedule, visibility, and comments.', errorKeys: ['status', 'published_at', 'expires_at', 'is_active'] },
    { id: 'blog-taxonomy', title: 'Categories & Keywords', description: 'Organize and connect the article.', errorKeys: ['category_ids', 'tag_names'] },
    { id: 'blog-seo', title: 'SEO', description: 'Search title and description.', errorKeys: ['seo_title', 'seo_description'] },
];

function submit() {
    form.post('/admin/blog-posts', {
        forceFormData: true,
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/blog-posts');
}
</script>

<template>
    <Head title="Create Blog Post" />

    <AppLayout>
        <div class="space-y-8 p-6">
            <PageHeader title="Create Blog Post" description="Add a new article to the blog." />

            <AdminSectionNavigator :sections="adminSections" :errors="form.errors" label="Blog sections" storage-key="admin.blog-posts.create.workspace" v-slot="{ activeSection }">
                <form class="space-y-8" @submit.prevent="submit">
                <FormSection
                    v-show="activeSection === 'blog-content'"
                    id="blog-content"
                    class="scroll-mt-24"
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
                    v-show="activeSection === 'blog-ai'"
                    id="blog-ai"
                    class="scroll-mt-24"
                    title="Blog AI Assistant"
                    description="Review the current draft and generate practical publishing recommendations."
                >
                    <BlogAiAssistantPanel
                        :title="form.title"
                        :excerpt="form.excerpt"
                        :content="form.content"
                        :blog-post-id="null"
                        :initial-result="null"
                        :initial-settings="null"
                        :initial-analyzed-at="null"
                        @apply-excerpt="form.excerpt = $event"
                        @apply-seo-title="form.seo_title = $event"
                        @apply-seo-description="form.seo_description = $event"
                        @analysis-updated="storeAiAnalysis"
                        @apply-tags="applyGeneratedTags"
                    />
                </FormSection>

                <FormSection
                    v-show="activeSection === 'blog-images'"
                    id="blog-images"
                    class="scroll-mt-24"
                    title="Images"
                    description="Create the article header and compact icon crops. Article images are inserted from the editor toolbar."
                >
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(260px,1fr)]">
                        <BlogEditedImageField
                            id="header_image"
                            label="Header Image"
                            description="Wide image shown at the top of the article."
                            :preset="BLOG_HEADER_PRESET"
                            :current-url="null"
                            :current-original-url="null"
                            :initial-edit="null"
                            :error="form.errors.header_image"
                            @apply="applyHeaderImage"
                        />

                        <BlogEditedImageField
                            id="icon_image"
                            label="Icon Image"
                            description="Square image used for compact blog displays."
                            :preset="BLOG_ICON_PRESET"
                            :current-url="null"
                            :current-original-url="null"
                            :initial-edit="null"
                            preview-class="aspect-square w-full"
                            :error="form.errors.icon_image"
                            @apply="applyIconImage"
                        />
                    </div>
                </FormSection>

                <FormSection
                    v-show="activeSection === 'blog-publishing'"
                    id="blog-publishing"
                    class="scroll-mt-24"
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

                <div v-show="activeSection === 'blog-taxonomy'" id="blog-taxonomy" class="scroll-mt-24 grid gap-6 lg:grid-cols-2">
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
                        title="Keywords"
                        description="Search existing Blog keywords or create new ones."
                    >
                        <FormField label="Keywords" for-id="blog-keywords" :error="form.errors.tag_names">
                            <CreatableTagInput
                                :key="`blog-keywords-${form.tag_names.join('|')}`"
                                v-model="form.tag_names"
                                :options="tags"
                                :disabled="form.processing"
                            />
                        </FormField>
                    </FormSection>
                </div>

                <FormSection
                    v-show="activeSection === 'blog-seo'"
                    id="blog-seo"
                    class="scroll-mt-24"
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
                    submit-label="Create Blog Post"
                    processing-label="Creating..."
                    :processing="form.processing"
                    @submit="submit"
                    @cancel="cancel"
                />
                </form>
            </AdminSectionNavigator>
        </div>
    </AppLayout>
</template>

