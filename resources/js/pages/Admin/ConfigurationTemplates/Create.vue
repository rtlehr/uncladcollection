<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import Form from './Form.vue';
import type { ConfigurationDisplayTypeOption } from '@/types/configurationTemplate';
defineProps<{ displayTypes: ConfigurationDisplayTypeOption[] }>();
const form = useForm({ name:'', code:'', description:'', display_type:'select', is_required_default:false, allows_multiple_default:false, placeholder:'', help_text:'', minimum_value:null, maximum_value:null, step_value:null, sort_order:0, is_active:true, values:[] as any[] });
function submit(): void { form.post('/admin/configuration-templates'); }
</script>
<template><Head title="Create Configuration Template" /><div class="space-y-6 p-6"><PageHeader title="Create Configuration Template" description="Create a reusable starting point that can be copied into any asset." /><form class="space-y-6" @submit.prevent="submit"><FormSection title="Template Details"><Form v-model="form" :display-types="displayTypes" /></FormSection><FormActions submit-label="Create Template" :processing="form.processing" @cancel="router.visit('/admin/configuration-templates')" /></form></div></template>
