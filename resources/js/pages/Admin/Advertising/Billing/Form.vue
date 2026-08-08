<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, computed, watch } from 'vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import WorkflowContextBanner from '@/components/Advertising/WorkflowContextBanner.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const p = defineProps<{ invoice: any | null; advertisers: any[]; campaigns: any[]; workflowContext?: any; initialInvoice?: any | null }>();
const seededItems = p.invoice?.items?.map((x: any) => ({ ...x })) ?? p.initialInvoice?.items ?? [{ description: 'Advertising campaign sponsorship', billing_model: 'flat', quantity: 1, unit_amount_cents: 0 }];
const form = reactive({
    advertiser_id: p.invoice?.advertiser_id ?? p.initialInvoice?.advertiser_id ?? '',
    advertising_campaign_id: p.invoice?.advertising_campaign_id ?? p.initialInvoice?.advertising_campaign_id ?? '',
    currency: p.invoice?.currency ?? 'USD', discount_cents: p.invoice?.discount_cents ?? 0, tax_cents: p.invoice?.tax_cents ?? 0,
    due_at: p.invoice?.due_at ?? '', notes: p.invoice?.notes ?? '', items: seededItems,
});
const availableCampaigns = computed(() => form.advertiser_id ? p.campaigns.filter((x: any) => Number(x.advertiser_id) === Number(form.advertiser_id)) : p.campaigns);
const total = computed(() => form.items.reduce((s: any, x: any) => s + (Number(x.quantity) || 0) * (Number(x.unit_amount_cents) || 0), 0) - Number(form.discount_cents || 0) + Number(form.tax_cents || 0));
const add = () => form.items.push({ description: '', billing_model: 'flat', quantity: 1, unit_amount_cents: 0 });
const remove = (i: number) => form.items.splice(i, 1);
const submit = () => p.invoice ? router.put(`/admin/advertising-invoices/${p.invoice.id}`, form) : router.post('/admin/advertising-invoices', form);

watch(() => form.advertiser_id, () => {
    if (form.advertising_campaign_id && !availableCampaigns.value.some((x: any) => Number(x.id) === Number(form.advertising_campaign_id))) form.advertising_campaign_id = '';
});
watch(() => form.advertising_campaign_id, (id) => {
    if (p.invoice || !id) return;
    const campaign = p.campaigns.find((x: any) => Number(x.id) === Number(id));
    if (!campaign) return;
    form.advertiser_id = campaign.advertiser_id;
    if (form.items.length === 1 && Number(form.items[0].unit_amount_cents || 0) === 0) {
        form.items[0] = { description: `${campaign.name} advertising campaign`, billing_model: campaign.pricing_model, quantity: 1, unit_amount_cents: Number(campaign.contract_value_cents || 0) };
    }
});
</script>

<template>
    <Head :title="invoice ? 'Edit Advertising Invoice' : 'Create Advertising Invoice'" />
    <div class="space-y-6 p-6">
        <PageHeader :title="invoice ? 'Edit Advertising Invoice' : 'Create Advertising Invoice'" description="Build the invoice from clear campaign or sponsorship line items." />
        <WorkflowContextBanner :context="workflowContext" label="Billing workflow" />
        <div class="grid gap-4 rounded-xl border p-5 md:grid-cols-2">
            <label class="space-y-1 text-sm">Advertiser<select v-model="form.advertiser_id" class="h-10 w-full rounded-md border bg-background px-3"><option value="">Select advertiser</option><option v-for="x in advertisers" :key="x.id" :value="x.id">{{ x.name }}</option></select></label>
            <label class="space-y-1 text-sm">Campaign<select v-model="form.advertising_campaign_id" class="h-10 w-full rounded-md border bg-background px-3"><option value="">No campaign</option><option v-for="x in availableCampaigns" :key="x.id" :value="x.id">{{ x.name }}</option></select></label>
            <label class="space-y-1 text-sm">Due date<Input v-model="form.due_at" type="date" /></label>
            <label class="space-y-1 text-sm">Currency<Input v-model="form.currency" maxlength="3" /></label>
        </div>
        <div class="space-y-4 rounded-xl border p-5">
            <div class="flex justify-between"><h2 class="font-semibold">Invoice items</h2><Button variant="outline" @click="add">Add item</Button></div>
            <div v-for="(x, i) in form.items" :key="i" class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_160px_100px_180px_auto]">
                <Input v-model="x.description" placeholder="Description" />
                <select v-model="x.billing_model" class="h-10 rounded-md border bg-background px-3"><option v-for="m in ['flat','cpm','cpc','sponsorship']" :key="m" :value="m">{{ m.toUpperCase() }}</option></select>
                <Input v-model="x.quantity" type="number" min="1" /><Input v-model="x.unit_amount_cents" type="number" min="0" placeholder="Unit cents" />
                <Button variant="destructive" @click="remove(i)" :disabled="form.items.length === 1">Remove</Button>
            </div>
        </div>
        <div class="grid gap-4 rounded-xl border p-5 md:grid-cols-3">
            <label class="space-y-1 text-sm">Discount (cents)<Input v-model="form.discount_cents" type="number" min="0" /></label>
            <label class="space-y-1 text-sm">Tax (cents)<Input v-model="form.tax_cents" type="number" min="0" /></label>
            <div><p class="text-sm text-muted-foreground">Estimated total</p><p class="text-2xl font-semibold">${{ (total / 100).toFixed(2) }}</p></div>
        </div>
        <div class="flex justify-end"><Button @click="submit">Save Invoice</Button></div>
    </div>
</template>
