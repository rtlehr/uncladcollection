<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import AssetHero from '@/components/Assets/AssetHero.vue';
import PurchaseSummary from '@/components/Purchases/PurchaseSummary.vue';
import DetailRow from '@/components/Shared/DetailRow.vue';
import DetailSection from '@/components/Shared/DetailSection.vue';
import { Button } from '@/components/ui/button';
import type { PurchaseDetailRecord, PurchasedIncludedFile } from '@/types/purchase';
const props=defineProps<{licenseRecord:PurchaseDetailRecord}>();
function formatBytes(bytes:number|null){
if(!bytes){
return 'Size unavailable';
}

const u=['B','KB','MB','GB'];const i=Math.min(Math.floor(Math.log(bytes)/Math.log(1024)),3);

return `${(bytes/Math.pow(1024,i)).toFixed(i?1:0)} ${u[i]}`;
}
function createDesign(){
router.post(`/account/licenses/${props.licenseRecord.id}/designs`);
}
function fileSubtitle(file:PurchasedIncludedFile){
return [file.extension,file.role?.replaceAll('_',' '),formatBytes(file.size_bytes)].filter(Boolean).join(' · ');
}
</script>
<template><Head :title="licenseRecord.product.title"/><AccountPageLayout><template #title>License Details</template><template #description>Review rights, documents, files, and download history.</template><div class="space-y-8">
<AssetHero :title="licenseRecord.product.title" :collection-name="licenseRecord.product.collection?.name" back-href="/account/library" back-label="Back to My Library"><template #actions><Button v-if="licenseRecord.kind==='asset'&&licenseRecord.status.can_download" @click="createDesign">Customize Image</Button><Button v-if="licenseRecord.download_url&&licenseRecord.can_download" as-child><a :href="licenseRecord.download_url">Download</a></Button><Button v-else disabled variant="secondary">Download Unavailable</Button><Button variant="outline" as-child><Link :href="licenseRecord.product.public_url">View Asset</Link></Button></template></AssetHero>
<div class="rounded-xl border p-5" :class="licenseRecord.status.tone==='danger'?'border-red-300 bg-red-50 dark:bg-red-950/20':licenseRecord.status.tone==='warning'?'border-amber-300 bg-amber-50 dark:bg-amber-950/20':'border-emerald-300 bg-emerald-50 dark:bg-emerald-950/20'"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="font-semibold">{{licenseRecord.status.label}}</p><p class="mt-1 text-sm">{{licenseRecord.status.message}}</p></div><Button variant="outline" as-child><a :href="licenseRecord.support_url">Contact Support</a></Button></div></div>
<div class="grid gap-6 lg:grid-cols-3"><div class="space-y-6 lg:col-span-2"><div class="rounded-lg border bg-card p-6"><img v-if="licenseRecord.product.preview_url" :src="licenseRecord.product.preview_url" :alt="licenseRecord.product.title" class="max-h-[650px] w-full rounded object-contain"/><div v-else class="flex h-80 items-center justify-center text-muted-foreground">No preview available.</div></div>
<DetailSection v-if="licenseRecord.kind==='asset'" title="Included Files"><div v-if="licenseRecord.included_files.length" class="divide-y rounded-lg border"><div v-for="file in licenseRecord.included_files" :key="`${file.id}-${file.name}`" class="flex items-center justify-between gap-4 p-4"><div class="min-w-0"><p class="truncate font-medium">{{file.name}}</p><p class="mt-1 capitalize text-xs text-muted-foreground">{{fileSubtitle(file)}}</p></div><Button v-if="file.download_url&&file.is_available&&licenseRecord.can_download" as-child size="sm" variant="outline"><a :href="file.download_url">Download</a></Button><span v-else class="text-xs text-muted-foreground">Unavailable</span></div></div><p v-else class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">No file manifest was stored with this license.</p></DetailSection>
<DetailSection title="Purchased License Terms"><div class="whitespace-pre-line text-sm leading-7">{{licenseRecord.license_terms||'No written terms were stored with this historical license.'}}</div><div v-if="licenseRecord.terms_changed" class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm dark:bg-blue-950/20"><strong>Current template has changed.</strong> Your purchase-time terms above remain the historical terms for this license.</div></DetailSection>
<DetailSection title="Download History"><div v-if="licenseRecord.download_history.length" class="divide-y rounded-lg border"><div v-for="item in licenseRecord.download_history" :key="item.id" class="grid gap-1 p-4 sm:grid-cols-[1fr_auto]"><div><p class="font-medium">{{item.filename||item.type||'Download'}}</p><p class="text-xs text-muted-foreground">{{item.type}} · {{item.status}}</p></div><time class="text-sm text-muted-foreground">{{item.downloaded_at}}</time></div></div><p v-else class="text-sm text-muted-foreground">No downloads have been recorded for this license.</p></DetailSection>
<DetailSection v-if="licenseRecord.status_history.length" title="License History"><div class="space-y-3"><div v-for="item in licenseRecord.status_history" :key="item.id" class="rounded-lg border p-4"><p class="font-medium capitalize">{{item.from_status||'Created'}} → {{item.to_status}}</p><p v-if="item.message" class="mt-1 text-sm text-muted-foreground">{{item.message}}</p><p class="mt-1 text-xs text-muted-foreground">{{item.changed_at}}</p></div></div></DetailSection></div>
<aside class="space-y-6"><PurchaseSummary :license="licenseRecord"/><DetailSection title="Documents"><div class="grid gap-3"><Button as-child variant="outline"><a :href="licenseRecord.certificate_url">Download License Certificate</a></Button><Button as-child variant="outline"><a :href="licenseRecord.proof_of_purchase_url">Download Proof of Purchase</a></Button></div></DetailSection><DetailSection title="Asset Details"><div class="space-y-4"><DetailRow label="Type" :value="licenseRecord.product.asset_type_label"/><DetailRow label="Creator" :value="licenseRecord.product.creator"/><DetailRow label="Added" :value="licenseRecord.product.created_at"/></div></DetailSection></aside></div>
</div></AccountPageLayout></template>
