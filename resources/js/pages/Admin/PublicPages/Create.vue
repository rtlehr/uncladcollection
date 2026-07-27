<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'; import PageHeader from '@/Components/Shared/PageHeader.vue'; import FormActions from '@/Components/Forms/FormActions.vue'; import PublicPageForm from '@/components/admin/PublicPageForm.vue';
defineProps<{types:Record<string,string>; navigationLocations:Record<string,string>; statuses:string[]}>();
const form=useForm({title:'',slug:'',eyebrow:'',introduction:'',content:'',page_type:'standard',status:'draft',published_at:'',is_active:true,navigation_label:'',navigation_locations:[] as string[],sort_order:100,seo_title:'',seo_description:'',canonical_url:''});
function submit(){form.post('/admin/public-pages',{preserveScroll:true});}
</script>
<template><Head title="Create Public Page"/><AppLayout><div class="space-y-8 p-6"><PageHeader title="Create Public Page" description="Add a lightweight informational page to the public site."/><form class="space-y-8" @submit.prevent="submit"><PublicPageForm :form="form" :types="types" :navigation-locations="navigationLocations" :statuses="statuses"/><FormActions submit-label="Create Page" :processing="form.processing" @cancel="router.visit('/admin/public-pages')"/></form></div></AppLayout></template>
