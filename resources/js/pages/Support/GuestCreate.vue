<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PageHeader from '@/components/Shared/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
const props = defineProps<{ mode: 'guest'|'member'; categories: Array<{id:number;name:string;description?:string}>; attachmentRules:{max_kb:number;extensions:string[]} }>();
defineOptions({ layout: PublicPageLayout });
const form = useForm({ guest_name:'', guest_email:'', category_id:'', subject:'', description:'', attachments:[] as File[] });
const submit=()=>form.post(props.mode==='guest'?'/support':'/support/tickets',{forceFormData:true});
</script>
<template>
<Head title="Submit a Support Request" />
<div class="mx-auto max-w-3xl space-y-6 p-6 sm:py-10">
<PageHeader title="Submit a support request" description="Tell us what happened and include any details that will help us investigate." />
<form class="space-y-6 rounded-xl border bg-card p-6" @submit.prevent="submit">
<div v-if="mode==='guest'" class="grid gap-5 sm:grid-cols-2"><div><Label for="guest_name">Name</Label><Input id="guest_name" v-model="form.guest_name" class="mt-2" required/><InputError :message="form.errors.guest_name"/></div><div><Label for="guest_email">Email</Label><Input id="guest_email" v-model="form.guest_email" type="email" class="mt-2" required/><InputError :message="form.errors.guest_email"/></div></div>
<div><Label for="category">Category</Label><select id="category" v-model="form.category_id" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option value="">Choose a category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><InputError :message="form.errors.category_id"/></div>
<div><Label for="subject">Subject</Label><Input id="subject" v-model="form.subject" class="mt-2" maxlength="180" required/><InputError :message="form.errors.subject"/></div>
<div><Label for="description">What can we help with?</Label><Textarea id="description" v-model="form.description" class="mt-2 min-h-44" required/><InputError :message="form.errors.description"/></div>
<div><Label for="attachments">Attachments</Label><Input id="attachments" type="file" multiple class="mt-2" @change="form.attachments=Array.from(($event.target as HTMLInputElement).files||[])"/><p class="mt-2 text-xs text-muted-foreground">Up to 5 files, {{ Math.round(attachmentRules.max_kb/1024) }} MB each. {{ attachmentRules.extensions.join(', ') }}</p><InputError :message="form.errors.attachments"/></div>
<div class="flex gap-3"><Button type="submit" :disabled="form.processing">{{ form.processing?'Submitting…':'Submit request' }}</Button><Button as-child variant="outline"><Link :href="mode==='guest'?'/support':'/support/tickets'">Cancel</Link></Button></div>
</form></div>
</template>
