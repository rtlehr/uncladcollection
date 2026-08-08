<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import WorkflowContextBanner from '@/components/Advertising/WorkflowContextBanner.vue';
import { Button } from '@/components/ui/button';

const p = defineProps<{ proposal: any | null; selectedPackage: any | null; packages: any[]; leads: any[]; advertisers: any[]; placements: any[]; workflowContext?: any; selectedAdvertiserId?: number | null; selectedLeadId?: number | null }>();
const initial = p.proposal?.items ?? p.selectedPackage?.placements?.map((x: any) => ({ description: `${p.selectedPackage.name} — ${x.name}`, ad_placement_id: x.id, billing_model: 'sponsorship', quantity: 1, unit_amount_cents: p.selectedPackage.package_price_cents })) ?? [{ description: 'Sponsorship package', ad_placement_id: null, billing_model: 'sponsorship', quantity: 1, unit_amount_cents: 0 }];
const f = useForm({
    sponsorship_lead_id: p.proposal?.sponsorship_lead_id ?? p.selectedLeadId ?? null,
    advertiser_id: p.proposal?.advertiser_id ?? p.selectedAdvertiserId ?? null,
    sponsorship_package_id: p.proposal?.sponsorship_package_id ?? p.selectedPackage?.id ?? null,
    title: p.proposal?.title ?? p.selectedPackage?.name ?? '',
    starts_on: p.proposal?.starts_on ?? '', ends_on: p.proposal?.ends_on ?? '', expires_on: p.proposal?.expires_on ?? '', currency: p.proposal?.currency ?? 'USD',
    discount_cents: p.proposal?.discount_cents ?? 0, tax_cents: p.proposal?.tax_cents ?? 0, terms: p.proposal?.terms ?? '', notes: p.proposal?.notes ?? '', items: initial,
});
const submit = () => p.proposal ? f.put(`/admin/sponsorship-proposals/${p.proposal.id}`) : f.post('/admin/sponsorship-proposals');
</script>

<template>
    <Head title="Sponsorship Proposal" />
    <div class="space-y-6 p-6">
        <PageHeader :title="proposal ? 'Edit Proposal' : 'Create Proposal'" description="Define the commercial offer, dates, placements, and accepted value." />
        <WorkflowContextBanner :context="workflowContext" label="Proposal workflow" />
        <form @submit.prevent="submit" class="space-y-5 rounded-xl border p-6">
            <div class="grid gap-4 md:grid-cols-2">
                <label>Title<input v-model="f.title" class="mt-1 w-full rounded-md border p-2" /></label>
                <label>Advertiser<select v-model="f.advertiser_id" class="mt-1 w-full rounded-md border p-2"><option v-for="a in advertisers" :value="a.id" :key="a.id">{{ a.name }}</option></select></label>
                <label>Sales opportunity<select v-model="f.sponsorship_lead_id" class="mt-1 w-full rounded-md border p-2"><option :value="null">No linked opportunity</option><option v-for="lead in leads" :value="lead.id" :key="lead.id">{{ lead.company_name }} — {{ lead.stage }}</option></select></label>
                <div></div>
                <label>Starts<input v-model="f.starts_on" type="date" class="mt-1 w-full rounded-md border p-2" /></label>
                <label>Ends<input v-model="f.ends_on" type="date" class="mt-1 w-full rounded-md border p-2" /></label>
            </div>
            <section>
                <h2 class="font-semibold">Proposal Items</h2>
                <div v-for="(item, i) in f.items" :key="i" class="mt-3 grid gap-3 rounded-lg border p-3 md:grid-cols-5">
                    <input v-model="item.description" class="rounded-md border p-2 md:col-span-2" />
                    <select v-model="item.ad_placement_id" class="rounded-md border p-2"><option :value="null">No placement</option><option v-for="pl in placements" :value="pl.id" :key="pl.id">{{ pl.name }}</option></select>
                    <input v-model.number="item.quantity" type="number" min="1" class="rounded-md border p-2" />
                    <input v-model.number="item.unit_amount_cents" type="number" min="0" class="rounded-md border p-2" />
                </div>
                <Button type="button" variant="outline" class="mt-3" @click="f.items.push({ description: '', ad_placement_id: null, billing_model: 'sponsorship', quantity: 1, unit_amount_cents: 0 })">Add Item</Button>
            </section>
            <Button>Save Proposal</Button>
        </form>
    </div>
</template>
