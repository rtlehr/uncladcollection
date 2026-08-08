<script setup lang="ts">
import {Head,Link,router} from '@inertiajs/vue3'; import PageHeader from '@/Components/Shared/PageHeader.vue'; import {Button} from '@/components/ui/button';
const p=defineProps<{invoice:any}>(); const money=(v:number)=>new Intl.NumberFormat('en-US',{style:'currency',currency:p.invoice.currency}).format(v/100);
const post=(path:string,data:any={})=>router.post(path,data); const payment=()=>{
const v=prompt('Payment amount in dollars', (p.invoice.balance_cents/100).toFixed(2));

if(v){
post(`/admin/advertising-invoices/${p.invoice.id}/payments`,{amount_cents:Math.round(Number(v)*100),provider:'manual'});
}
}; const refund=()=>{
const v=prompt('Refund amount in dollars');

if(v){
post(`/admin/advertising-invoices/${p.invoice.id}/refunds`,{amount_cents:Math.round(Number(v)*100)});
}
};
</script>
<template><Head :title="invoice.invoice_number"/><div class="space-y-6 p-6"><div class="flex items-start justify-between gap-4"><PageHeader :title="invoice.invoice_number" :description="`${invoice.advertiser.name}${invoice.campaign?' · '+invoice.campaign.name:''}`"/><div class="flex flex-wrap gap-2"><Link v-if="!['paid','void','refunded'].includes(invoice.status)" :href="`/admin/advertising-invoices/${invoice.id}/edit`"><Button variant="outline">Edit</Button></Link><Button v-if="invoice.status==='draft'" @click="post(`/admin/advertising-invoices/${invoice.id}/issue`)">Issue</Button><Button v-if="invoice.balance_cents>0&&!['draft','void'].includes(invoice.status)" @click="payment">Record Payment</Button><Button v-if="invoice.balance_cents>0&&!['draft','void'].includes(invoice.status)" variant="outline" @click="post(`/admin/advertising-invoices/${invoice.id}/checkout`)">Stripe Checkout</Button><Button v-if="invoice.paid_cents>invoice.refunded_cents" variant="outline" @click="refund">Record Refund</Button></div></div><div class="grid gap-4 md:grid-cols-4"><div v-for="x in [{l:'Status',v:invoice.status.replace('_',' ')},{l:'Total',v:money(invoice.total_cents)},{l:'Paid',v:money(invoice.paid_cents)},{l:'Balance',v:money(invoice.balance_cents)}]" :key="x.l" class="rounded-xl border p-5"><p class="text-sm text-muted-foreground">{{x.l}}</p><p class="text-xl font-semibold capitalize">{{x.v}}</p></div></div><div class="rounded-xl border"><div class="border-b p-4 font-semibold">Invoice items</div><div v-for="x in invoice.items" :key="x.id" class="grid grid-cols-[1fr_auto] gap-4 border-b p-4 last:border-0"><div><p class="font-medium">{{x.description}}</p><p class="text-sm text-muted-foreground">{{x.billing_model.toUpperCase()}} · {{x.quantity}} × {{money(x.unit_amount_cents)}}</p></div><p class="font-medium">{{money(x.line_total_cents)}}</p></div></div><div class="rounded-xl border"><div class="border-b p-4 font-semibold">Payment and refund history</div><div v-for="x in invoice.payments" :key="x.id" class="grid gap-2 border-b p-4 last:border-0 md:grid-cols-5"><span class="capitalize">{{x.type}}</span><span class="capitalize">{{x.status}}</span><span>{{x.provider}}</span><span>{{money(x.amount_cents)}}</span><span>{{x.processed_at||x.created_at}}</span></div><p v-if="!invoice.payments.length" class="p-6 text-muted-foreground">No payments have been recorded.</p></div></div></template>
