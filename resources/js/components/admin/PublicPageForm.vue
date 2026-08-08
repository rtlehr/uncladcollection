<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

import PublicPageHeaderEditor from '@/components/admin/PublicPageHeaderEditor.vue';
import RichTextEditor from '@/components/admin/RichTextEditorV2.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    form: any;
    types: Record<string, string>;
    navigationLocations: Record<string, string>;
    statuses: string[];
    initialImageUrl?: string | null;
    initialOriginalUrl?: string | null;
    initialEditData?: any;
}>();

const generatedSlug = computed(() =>
    props.form.title
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, ''),
);


function toggleLocation(value: string, checked: boolean): void {
    props.form.navigation_locations = checked
        ? [...props.form.navigation_locations, value]
        : props.form.navigation_locations.filter((location: string) => location !== value);
}

function imageApplied(payload: any): void {
    props.form.header_image_original = payload.original;
    props.form.header_image_rendered = payload.rendered;
    props.form.header_image_edit = payload.edit;
    props.form.remove_header_image = false;
}

function imageRemoved(): void {
    props.form.header_image_original = null;
    props.form.header_image_rendered = null;
    props.form.header_image_edit = null;
    props.form.remove_header_image = true;
}

function addFaq(): void {
    props.form.faq_items.push({
        question: '',
        answer: '',
        is_active: true,
        sort_order: (props.form.faq_items.length + 1) * 10,
    });
}
</script>

<template>
    <div class="space-y-8">
        <FormSection
            title="Page Content"
            description="Create the title, introduction, and public page content."
        >
            <FormGrid :columns="2">
                <FormField label="Title" for-id="title" required :error="form.errors.title">
                    <Input id="title" v-model="form.title" />
                </FormField>

                <FormField label="Slug" for-id="slug" required :error="form.errors.slug">
                    <div class="flex gap-2">
                        <Input id="slug" v-model="form.slug" />
                        <Button type="button" variant="outline" @click="form.slug = generatedSlug">
                            Generate
                        </Button>
                    </div>
                </FormField>
            </FormGrid>

            <div class="mt-6">
                <FormField label="Eyebrow" for-id="eyebrow">
                    <Input id="eyebrow" v-model="form.eyebrow" />
                </FormField>
            </div>

            <div class="mt-6">
                <FormField label="Introduction" for-id="introduction">
                    <textarea
                        id="introduction"
                        v-model="form.introduction"
                        class="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    />
                </FormField>
            </div>

            <div class="mt-6">
                <FormField label="Content" for-id="content">
                    <RichTextEditor v-model="form.content" />
                </FormField>
            </div>
        </FormSection>

        <FormSection
            title="Header Image"
            description="Optional edited image for the public inside-page hero."
        >
            <PublicPageHeaderEditor
                :initial-image-url="initialImageUrl"
                :initial-original-url="initialOriginalUrl"
                :initial-edit-data="initialEditData"
                :error="form.errors.header_image_rendered"
                @apply="imageApplied"
                @remove="imageRemoved"
            />

            <div class="mt-5">
                <FormField label="Alternative Text" for-id="header_image_alt">
                    <Input id="header_image_alt" v-model="form.header_image_alt" />
                </FormField>
            </div>
        </FormSection>

        <FormSection
            title="Publishing"
            description="Choose page behavior, publication state, and visibility."
        >
            <FormGrid :columns="2">
                <FormField label="Page Type" for-id="page_type">
                    <select
                        id="page_type"
                        v-model="form.page_type"
                        class="h-10 w-full rounded-md border bg-background px-3"
                    >
                        <option v-for="(label, value) in types" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Status" for-id="status">
                    <select
                        id="status"
                        v-model="form.status"
                        class="h-10 w-full rounded-md border bg-background px-3"
                    >
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Published At" for-id="published_at">
                    <Input id="published_at" v-model="form.published_at" type="datetime-local" />
                </FormField>

                <FormField label="Sort Order" for-id="sort_order">
                    <Input id="sort_order" v-model.number="form.sort_order" type="number" min="0" />
                </FormField>
            </FormGrid>

            <label class="mt-6 flex items-center gap-3">
                <Checkbox
                    :model-value="form.is_active"
                    @update:model-value="form.is_active = Boolean($event)"
                />
                Active
            </label>
        </FormSection>

        <FormSection
            v-if="form.page_type === 'faq' || form.page_type === 'support'"
            title="FAQ Items"
            description="Add questions in the order they should appear."
        >
            <div class="space-y-4">
                <div
                    v-for="(faq, index) in form.faq_items"
                    :key="index"
                    class="rounded-xl border p-4"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-1 space-y-3">
                            <Input v-model="faq.question" placeholder="Question" />
                            <textarea
                                v-model="faq.answer"
                                rows="4"
                                class="w-full rounded-md border bg-background p-3 text-sm"
                                placeholder="Answer"
                            />

                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <Checkbox
                                        :model-value="faq.is_active"
                                        @update:model-value="faq.is_active = Boolean($event)"
                                    />
                                    Active
                                </label>

                                <Input v-model.number="faq.sort_order" type="number" class="max-w-28" />
                            </div>
                        </div>

                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            @click="form.faq_items.splice(index, 1)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <Button type="button" variant="outline" @click="addFaq">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Question
                </Button>
            </div>
        </FormSection>

        <FormSection
            v-if="form.page_type === 'legal'"
            title="Legal Document Details"
            description="Optional version and effective-date information."
        >
            <FormGrid :columns="3">
                <FormField label="Version" for-id="legal_version">
                    <Input id="legal_version" v-model="form.legal_version" />
                </FormField>

                <FormField label="Effective Date" for-id="effective_date">
                    <Input id="effective_date" v-model="form.effective_date" type="date" />
                </FormField>

                <FormField label="Revised Date" for-id="revised_date">
                    <Input id="revised_date" v-model="form.revised_date" type="date" />
                </FormField>
            </FormGrid>
        </FormSection>

        <FormSection
            title="Navigation"
            description="Optionally publish this page in predefined public navigation locations. Support Center links always open /support."
        >
            <FormField label="Navigation Label" for-id="navigation_label">
                <Input id="navigation_label" v-model="form.navigation_label" />
            </FormField>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <label
                    v-for="(label, value) in navigationLocations"
                    :key="value"
                    class="flex items-center gap-3 rounded-lg border p-4"
                >
                    <Checkbox
                        :model-value="form.navigation_locations.includes(value)"
                        @update:model-value="toggleLocation(value, Boolean($event))"
                    />
                    <span>{{ label }}</span>
                </label>
            </div>
        </FormSection>

        <FormSection
            title="Search & Sharing"
            description="Optional metadata for search engines and social sharing."
        >
            <FormGrid :columns="2">
                <FormField label="SEO Title" for-id="seo_title">
                    <Input id="seo_title" v-model="form.seo_title" />
                </FormField>

                <FormField label="Canonical URL" for-id="canonical_url">
                    <Input id="canonical_url" v-model="form.canonical_url" />
                </FormField>
            </FormGrid>

            <div class="mt-6">
                <FormField label="SEO Description" for-id="seo_description">
                    <textarea
                        id="seo_description"
                        v-model="form.seo_description"
                        class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    />
                </FormField>
            </div>
        </FormSection>
    </div>
</template>
