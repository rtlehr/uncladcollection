<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import WorkflowContextBanner from '@/components/Advertising/WorkflowContextBanner.vue';
import { Button } from '@/components/ui/button';

const p = defineProps<{ lead: any | null; advertisers: any[]; users: any[]; workflowContext?: any; initialLead?: any | null }>();
const seed = p.lead ?? p.initialLead ?? {};
const f = useForm({
    advertiser_id: seed.advertiser_id ?? null,
    assigned_to: seed.assigned_to ?? null,
    company_name: seed.company_name ?? '',
    contact_name: seed.contact_name ?? '',
    contact_email: seed.contact_email ?? '',
    contact_phone: seed.contact_phone ?? '',
    source: seed.source ?? '',
    stage: seed.stage ?? 'new',
    estimated_value_cents: seed.estimated_value_cents ?? 0,
    probability: seed.probability ?? 10,
    target_close_date: seed.target_close_date ?? '',
    next_follow_up_at: seed.next_follow_up_at ?? '',
    notes: seed.notes ?? '',
    lost_reason: seed.lost_reason ?? '',
});
const submit = () => p.lead ? f.put(`/admin/sponsorship-leads/${p.lead.id}`) : f.post('/admin/sponsorship-leads');
</script>

<template>
    <Head title="Sponsorship Lead" />
    <div class="space-y-6 p-6">
        <PageHeader :title="lead ? 'Edit Lead' : 'Add Sponsorship Lead'" description="Capture prospect details and forecast the opportunity." />
        <WorkflowContextBanner :context="workflowContext" label="Sales opportunity" />
        <form @submit.prevent="submit" class="grid gap-4 rounded-xl border p-6 md:grid-cols-2">
            <label>Company<input v-model="f.company_name" class="mt-1 w-full rounded-md border p-2" /></label>
            <label>Contact<input v-model="f.contact_name" class="mt-1 w-full rounded-md border p-2" /></label>
            <label>Email<input v-model="f.contact_email" type="email" class="mt-1 w-full rounded-md border p-2" /></label>
            <label>Phone<input v-model="f.contact_phone" class="mt-1 w-full rounded-md border p-2" /></label>
            <label>Stage<select v-model="f.stage" class="mt-1 w-full rounded-md border p-2"><option v-for="s in ['new','contacted','qualified','proposal','negotiation','won','lost']" :key="s">{{ s }}</option></select></label>
            <label>Estimated value cents<input v-model.number="f.estimated_value_cents" type="number" class="mt-1 w-full rounded-md border p-2" /></label>
            <label>Probability<input v-model.number="f.probability" type="number" min="0" max="100" class="mt-1 w-full rounded-md border p-2" /></label>
            <label>Assigned owner<select v-model="f.assigned_to" class="mt-1 w-full rounded-md border p-2"><option :value="null">Unassigned</option><option v-for="u in users" :value="u.id" :key="u.id">{{ u.name }}</option></select></label>
            <label>Advertiser<select v-model="f.advertiser_id" class="mt-1 w-full rounded-md border p-2"><option :value="null">Not linked</option><option v-for="a in advertisers" :value="a.id" :key="a.id">{{ a.name }}</option></select></label>
            <label class="md:col-span-2">Notes<textarea v-model="f.notes" class="mt-1 w-full rounded-md border p-2" /></label>
            <div class="md:col-span-2"><Button>Save Lead</Button></div>
        </form>
    </div>
</template>
