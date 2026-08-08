<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdvertiserPortalHeader from '@/components/Advertiser/AdvertiserPortalHeader.vue';
defineProps<{ advertiser:any; membership:any; proposals:any[] }>();
const money=(value:number,currency='USD')=>new Intl.NumberFormat('en-US',{style:'currency',currency}).format((value||0)/100);
const statusClass=(status:string)=>({sent:'bg-blue-100 text-blue-800',accepted:'bg-emerald-100 text-emerald-800',declined:'bg-red-100 text-red-800',expired:'bg-amber-100 text-amber-800',converted:'bg-violet-100 text-violet-800'}[status]||'bg-muted text-muted-foreground');
</script>
<template>
<Head title="Sponsorship Proposals" />
<div class="space-y-6 p-6">
  <AdvertiserPortalHeader title="Sponsorship Proposals" description="Review and respond to sponsorship offers from Unclad Collection." :advertiser="advertiser" />
  <div class="overflow-hidden rounded-xl border bg-background">
    <table class="w-full text-sm">
      <thead class="bg-muted/50"><tr><th class="p-3 text-left">Proposal</th><th class="p-3 text-left">Dates</th><th class="p-3 text-left">Status</th><th class="p-3 text-right">Total</th></tr></thead>
      <tbody>
        <tr v-for="proposal in proposals" :key="proposal.id" class="border-t">
          <td class="p-3"><Link :href="`/advertiser/proposals/${proposal.id}`" class="font-medium hover:underline">{{proposal.title}}</Link><div class="text-muted-foreground">{{proposal.proposal_number}}</div></td>
          <td class="p-3">{{proposal.starts_on}} – {{proposal.ends_on}}</td>
          <td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-medium capitalize" :class="statusClass(proposal.status)">{{proposal.status}}</span></td>
          <td class="p-3 text-right font-medium">{{money(proposal.total_cents,proposal.currency)}}</td>
        </tr>
        <tr v-if="!proposals.length"><td colspan="4" class="p-8 text-center text-muted-foreground">No sponsorship proposals are available.</td></tr>
      </tbody>
    </table>
  </div>
</div>
</template>
