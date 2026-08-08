<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import PurchasedAssetCard from '@/components/Purchases/PurchasedAssetCard.vue';
import EmptyState from '@/components/Shared/EmptyState.vue';
import Pagination from '@/components/Shared/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { PaginatedPurchases } from '@/types/purchase';

const props=defineProps<{licenses:PaginatedPurchases;filters:{search:string;sort:string;status:string};statusCounts:Record<string,number>}>();
const search=ref(props.filters.search);
const tabs=[['all','All'],['active','Active'],['expiring_soon','Expiring Soon'],['expired','Expired'],['revoked','Revoked'],['refunded','Refunded']];
function apply(status=props.filters.status){
router.get('/account/library',{search:search.value,sort:props.filters.sort,status},{preserveState:true,replace:true});
}
</script>
<template><Head title="My Library"/><AccountPageLayout><template #title>My Library</template><template #description>Manage every license, document, and download from one place.</template><div class="space-y-6">
<div class="flex flex-col gap-3 sm:flex-row"><Input v-model="search" placeholder="Search assets, order numbers, or license keys" @keyup.enter="apply()"/><Button @click="apply()">Search</Button></div>
<nav class="flex gap-2 overflow-x-auto pb-2" aria-label="License status filters"><Link v-for="tab in tabs" :key="tab[0]" :href="`/account/library?status=${tab[0]}`" class="shrink-0 rounded-full border px-4 py-2 text-sm" :class="filters.status===tab[0]?'bg-[var(--brand-primary)] text-white':''">{{tab[1]}} ({{statusCounts[tab[0]]??0}})</Link></nav>
<div v-if="licenses.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"><PurchasedAssetCard v-for="license in licenses.data" :key="license.id" :license="license"/></div>
<EmptyState v-else title="No licenses found" description="Try another status or search term."><template #actions><Button as-child variant="outline"><Link href="/images">Browse Marketplace</Link></Button></template></EmptyState>
<Pagination :links="licenses.links" item-label="licenses"/>
</div></AccountPageLayout></template>
