<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
const props=defineProps<{title:string;description?:string;advertiser:any}>();
const page=usePage();
const items=[['Overview','/advertiser'],['Campaigns','/advertiser/campaigns'],['Creatives','/advertiser/campaigns'],['Proposals','/advertiser/proposals'],['Invoices','/advertiser/invoices'],['Reports','/advertiser/reports'],['Account','/advertiser/account']];
function active(href:string){const u=page.url.split('?')[0];if(href==='/advertiser')return u==='/advertiser';if(href==='/advertiser/campaigns')return u.startsWith('/advertiser/campaigns');return u.startsWith(href)}
</script>
<template><div class="space-y-4"><PageHeader :title="title" :description="description || advertiser.name"><template #actions><slot name="actions"/></template></PageHeader><nav aria-label="Advertiser portal" class="overflow-x-auto rounded-xl border bg-background p-1"><div class="flex min-w-max gap-1"><Link v-for="item in items" :key="item[0]" :href="item[1]" :aria-current="active(item[1])?'page':undefined" class="rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :class="active(item[1])?'bg-primary text-primary-foreground':'text-muted-foreground hover:bg-muted hover:text-foreground'">{{item[0]}}</Link></div></nav></div></template>
