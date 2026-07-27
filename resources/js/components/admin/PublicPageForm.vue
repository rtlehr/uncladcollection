<script setup lang="ts">
import { computed } from 'vue';
import RichTextEditor from '@/components/admin/RichTextEditorV2.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

const props=defineProps<{ form:any; types:Record<string,string>; navigationLocations:Record<string,string>; statuses:string[] }>();
const generatedSlug=computed(()=>props.form.title.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,''));
function toggleLocation(value:string, checked:boolean){ props.form.navigation_locations=checked?[...props.form.navigation_locations,value]:props.form.navigation_locations.filter((x:string)=>x!==value); }
</script>
<template>
<div class="space-y-8">
<FormSection title="Page Content" description="Create the title, introduction, and public page content.">
<FormGrid :columns="2"><FormField label="Title" for-id="title" required :error="form.errors.title"><Input id="title" v-model="form.title" /></FormField><FormField label="Slug" for-id="slug" required :error="form.errors.slug"><div class="flex gap-2"><Input id="slug" v-model="form.slug" /><Button type="button" variant="outline" @click="form.slug=generatedSlug">Generate</Button></div></FormField></FormGrid>
<div class="mt-6"><FormField label="Eyebrow" for-id="eyebrow" :error="form.errors.eyebrow"><Input id="eyebrow" v-model="form.eyebrow" placeholder="Optional small heading" /></FormField></div>
<div class="mt-6"><FormField label="Introduction" for-id="introduction" :error="form.errors.introduction"><textarea id="introduction" v-model="form.introduction" class="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm" /></FormField></div>
<div class="mt-6"><FormField label="Content" for-id="content" :error="form.errors.content"><RichTextEditor v-model="form.content" /></FormField></div>
</FormSection>
<FormSection title="Publishing" description="Choose page behavior, publication state, and visibility."><FormGrid :columns="2"><FormField label="Page Type" for-id="page_type" required :error="form.errors.page_type"><select id="page_type" v-model="form.page_type" class="h-10 w-full rounded-md border bg-background px-3"><option v-for="(label,value) in types" :key="value" :value="value">{{label}}</option></select></FormField><FormField label="Status" for-id="status" required :error="form.errors.status"><select id="status" v-model="form.status" class="h-10 w-full rounded-md border bg-background px-3"><option v-for="status in statuses" :key="status" :value="status">{{status}}</option></select></FormField><FormField label="Published At" for-id="published_at" :error="form.errors.published_at"><Input id="published_at" v-model="form.published_at" type="datetime-local" /></FormField><FormField label="Sort Order" for-id="sort_order" required :error="form.errors.sort_order"><Input id="sort_order" v-model.number="form.sort_order" type="number" min="0" /></FormField></FormGrid><label class="mt-6 flex items-center gap-3"><Checkbox :checked="form.is_active" @update:checked="form.is_active=$event" /> Active</label></FormSection>
<FormSection title="Navigation" description="Optionally publish this page in predefined public navigation locations."><FormField label="Navigation Label" for-id="navigation_label" :error="form.errors.navigation_label"><Input id="navigation_label" v-model="form.navigation_label" placeholder="Defaults to page title" /></FormField><div class="mt-5 grid gap-3 sm:grid-cols-2"><label v-for="(label,value) in navigationLocations" :key="value" class="flex items-center gap-3 rounded-lg border p-4"><Checkbox :checked="form.navigation_locations.includes(value)" @update:checked="toggleLocation(value, Boolean($event))" /><span>{{label}}</span></label></div></FormSection>
<FormSection title="Search & Sharing" description="Optional metadata for search engines and social sharing."><FormGrid :columns="2"><FormField label="SEO Title" for-id="seo_title" :error="form.errors.seo_title"><Input id="seo_title" v-model="form.seo_title" /></FormField><FormField label="Canonical URL" for-id="canonical_url" :error="form.errors.canonical_url"><Input id="canonical_url" v-model="form.canonical_url" /></FormField></FormGrid><div class="mt-6"><FormField label="SEO Description" for-id="seo_description" :error="form.errors.seo_description"><textarea id="seo_description" v-model="form.seo_description" class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" /></FormField></div></FormSection>
</div>
</template>
