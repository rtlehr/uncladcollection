<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import type { ConfigurationTemplate } from '@/types/configurationTemplate';
const props = defineProps<{ templates: ConfigurationTemplate[]; filters: { search: string } }>();
const search = ref(props.filters.search ?? '');
const selected = ref<ConfigurationTemplate | null>(null);
const deleteOpen = computed({ get: () => selected.value !== null, set: (open: boolean) => {
 if (!open) {
selected.value = null;
} 
} });
function reload(): void {
 router.get('/admin/configuration-templates', { search: search.value || undefined }, { preserveState: true, replace: true }); 
}
function remove(): void {
 if (!selected.value) {
return;
}

 router.delete(`/admin/configuration-templates/${selected.value.id}`, { onFinish: () => selected.value = null }); 
}
</script>
<template><Head title="Configuration Library" /><div class="space-y-6 p-6"><PageHeader title="Configuration Library" description="Create reusable product-configuration templates for sizes, colors, print formats, resolutions, and other common choices." /><div class="flex flex-wrap gap-3 rounded-xl border p-4"><input v-model="search" class="h-10 min-w-64 flex-1 rounded-md border bg-background px-3 text-sm" placeholder="Search templates…" @keyup.enter="reload" /><Button type="button" variant="outline" @click="reload">Search</Button><Button as-child><Link href="/admin/configuration-templates/create">Add Template</Link></Button></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"><article v-for="template in templates" :key="template.id" class="rounded-2xl border bg-background p-5"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ template.display_type_label }}</p><h2 class="mt-1 text-lg font-semibold">{{ template.name }}</h2><p class="mt-1 text-sm text-muted-foreground">{{ template.description || 'Reusable configuration template' }}</p></div><span class="rounded-full bg-muted px-2.5 py-1 text-xs">{{ template.values_count }} values</span></div><div class="mt-5 flex justify-end gap-2"><Button type="button" size="sm" variant="destructive" @click="selected = template">Remove</Button><Button as-child size="sm" variant="outline"><Link :href="`/admin/configuration-templates/${template.id}/edit`">Edit</Link></Button></div></article><div v-if="!templates.length" class="col-span-full rounded-2xl border border-dashed p-10 text-center text-muted-foreground">No configuration templates found.</div></div><ConfirmActionDialog v-model:open="deleteOpen" title="Remove configuration template?" description="Existing assets keep their copied configuration groups. This only removes the reusable library template." confirm-label="Remove Template" destructive @confirm="remove" @cancel="selected = null" /></div></template>
