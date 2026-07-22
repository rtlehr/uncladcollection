<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AdvertiserPortalHeader from '@/components/Advertiser/AdvertiserPortalHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const props=defineProps<{advertiser:any;membership:any;proposal:any;canRespond:boolean}>();
const money=(v:number)=>new Intl.NumberFormat('en-US',{style:'currency',currency:props.proposal.currency||'USD'}).format((v||0)/100);
const form=reactive({signer_name:'',signer_title:'',signer_email:'',signer_company:props.advertiser.name,terms_acknowledged:false});
const declineReason=ref('');
const accepting=ref(false); const declining=ref(false);
function accept(){router.post(`/advertiser/proposals/${props.proposal.id}/accept`,form,{preserveScroll:true,onStart:()=>accepting.value=true,onFinish:()=>accepting.value=false});}
function decline(){router.post(`/advertiser/proposals/${props.proposal.id}/decline`,{reason:declineReason.value},{preserveScroll:true,onStart:()=>declining.value=true,onFinish:()=>declining.value=false});}
</script>
<template>
<Head :title="proposal.title" />
<div class="space-y-6 p-6">
  <AdvertiserPortalHeader :title="proposal.title" :description="`${proposal.proposal_number} · ${proposal.status}`" :advertiser="advertiser" />
  <div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
      <section class="rounded-xl border bg-background p-5"><h2 class="font-semibold">Proposal details</h2><div class="mt-4 grid gap-3 sm:grid-cols-2"><div><p class="text-sm text-muted-foreground">Campaign dates</p><p>{{proposal.starts_on}} – {{proposal.ends_on}}</p></div><div><p class="text-sm text-muted-foreground">Respond by</p><p>{{proposal.expires_on||'No expiration date'}}</p></div><div v-if="proposal.package"><p class="text-sm text-muted-foreground">Package</p><p>{{proposal.package.name}}</p></div></div></section>
      <section class="rounded-xl border bg-background p-5"><h2 class="font-semibold">Included services</h2><div class="mt-3 divide-y"><div v-for="item in proposal.items" :key="item.id" class="flex justify-between gap-4 py-3"><div><p class="font-medium">{{item.description}}</p><p class="text-sm text-muted-foreground">{{item.placement?.name||'Custom sponsorship item'}} · {{item.quantity}} × {{money(item.unit_amount_cents)}}</p></div><strong>{{money(item.line_total_cents)}}</strong></div></div></section>
      <section v-if="proposal.terms" class="rounded-xl border bg-background p-5"><h2 class="font-semibold">Terms</h2><div class="mt-3 whitespace-pre-wrap text-sm text-muted-foreground">{{proposal.terms}}</div></section>
      <section v-if="proposal.acceptance" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5"><h2 class="font-semibold text-emerald-900">Accepted electronically</h2><p class="mt-2 text-sm text-emerald-800">Signed by {{proposal.acceptance.signer_name}}<span v-if="proposal.acceptance.signer_title">, {{proposal.acceptance.signer_title}}</span> on {{proposal.acceptance.accepted_at}}.</p></section>
      <section v-if="canRespond" class="rounded-xl border bg-background p-5"><h2 class="font-semibold">Accept proposal</h2><div class="mt-4 grid gap-4 sm:grid-cols-2"><div><Label>Signer name</Label><Input v-model="form.signer_name" /></div><div><Label>Title</Label><Input v-model="form.signer_title" /></div><div><Label>Email</Label><Input v-model="form.signer_email" type="email" /></div><div><Label>Company</Label><Input v-model="form.signer_company" /></div></div><label class="mt-4 flex items-start gap-2 text-sm"><input v-model="form.terms_acknowledged" type="checkbox" class="mt-1"/><span>I have reviewed this proposal and agree to its terms on behalf of the advertiser.</span></label><Button class="mt-4" :disabled="accepting" @click="accept">Accept Proposal</Button></section>
      <section v-if="canRespond" class="rounded-xl border bg-background p-5"><h2 class="font-semibold">Decline proposal</h2><textarea v-model="declineReason" class="mt-3 min-h-24 w-full rounded-md border bg-background p-3 text-sm" placeholder="Please tell us why this proposal is not a fit."/><Button class="mt-3" variant="outline" :disabled="declining||!declineReason" @click="decline">Decline Proposal</Button></section>
    </div>
    <aside class="h-fit rounded-xl border bg-background p-5"><div class="flex justify-between"><span>Subtotal</span><strong>{{money(proposal.subtotal_cents)}}</strong></div><div class="mt-2 flex justify-between"><span>Discount</span><strong>-{{money(proposal.discount_cents)}}</strong></div><div class="mt-2 flex justify-between"><span>Tax</span><strong>{{money(proposal.tax_cents)}}</strong></div><div class="mt-4 flex justify-between border-t pt-4 text-lg"><span>Total</span><strong>{{money(proposal.total_cents)}}</strong></div><div class="mt-5 rounded-lg bg-muted p-3 text-sm"><p class="font-medium capitalize">Status: {{proposal.status}}</p><p v-if="proposal.converted_at" class="mt-1 text-muted-foreground">Converted into an active campaign and invoice.</p></div></aside>
  </div>
</div>
</template>
