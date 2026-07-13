<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import Form from './Form.vue';
import type { ConfigurationDisplayTypeOption, ConfigurationTemplate } from '@/types/configurationTemplate';
const props = defineProps<{ templateRecord: ConfigurationTemplate; displayTypes: ConfigurationDisplayTypeOption[] }>();
const form = useForm({ ...props.templateRecord, values: props.templateRecord.values ?? [] });
function submit(): void { form.put(`/admin/configuration-templates/${props.templateRecord.id}`); }
</script>
<template><Head :title="`Edit ${templateRecord.name}`" /><div class="space-y-6 p-6"><PageHeader :title="`Edit ${templateRecord.name}`" description="Changes affect future copies only. Existing asset configurations remain unchanged." /><form class="space-y-6" @submit.prevent="submit"><FormSection title="Template Details"><Form v-model="form" :display-types="displayTypes" /></FormSection><FormActions submit-label="Save Template" :processing="form.processing" @cancel="router.visit('/admin/configuration-templates')" /></form></div></template>
