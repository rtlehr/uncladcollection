<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { AlertCircle, Brain, CheckCircle2, LoaderCircle, RefreshCw, Search, Sparkles } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ShowSection from '@/Components/Show/ShowSection.vue';

const props = defineProps<{ filters: any; terms: any }>();
const filters = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '', opportunity: props.filters.opportunity ?? false });
const editing = ref<Record<number, { canonical_term: string; synonyms_text: string; status: string; is_content_opportunity: boolean }>>({});
const analyzingId = ref<number | null>(null);
const analysisNotice = ref<{ type: 'success' | 'error'; message: string } | null>(null);
const money = (c:number) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(c/100);
const apply = () => router.get('/admin/discovery/search-intelligence', filters, { preserveState:true, replace:true });
const rebuild = () => router.post('/admin/discovery/search-intelligence/rebuild');
const analyze = (id:number) => {
  analyzingId.value = id;
  analysisNotice.value = null;

  router.post(`/admin/discovery/search-intelligence/${id}/analyze`, {}, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      delete editing.value[id];
      analysisNotice.value = {
        type: 'success',
        message: 'Qwen analysis completed. Review the suggested canonical term, synonyms, confidence, and explanation below.',
      };
    },
    onError: (errors) => {
      analysisNotice.value = {
        type: 'error',
        message: String(errors.ai ?? 'Qwen analysis failed. Check the Ollama connection and application log for details.'),
      };
    },
    onFinish: () => {
      analyzingId.value = null;
    },
  });
};
const stateFor = (row:any) => editing.value[row.id] ??= {
  canonical_term: row.mapping?.approved_canonical_term ?? row.mapping?.suggested_canonical_term ?? row.term,
  synonyms_text: (row.mapping?.approved_synonyms?.length ? row.mapping.approved_synonyms : row.mapping?.suggested_synonyms ?? []).join(', '),
  status: row.mapping?.status ?? 'pending', is_content_opportunity: row.is_content_opportunity,
};
const save = (row:any, status?:string) => {
  const state=stateFor(row); if(status) state.status=status;
  router.patch(`/admin/discovery/search-intelligence/${row.id}`, {
    status: state.status, canonical_term: state.canonical_term,
    synonyms: state.synonyms_text.split(',').map((v:string)=>v.trim()).filter(Boolean),
    is_content_opportunity: state.is_content_opportunity,
  }, { preserveScroll:true });
};
</script>
<template>
<Head title="Search Term Intelligence" />
<div class="space-y-8 p-6">
  <div
    v-if="analysisNotice"
    class="flex items-start gap-3 rounded-xl border p-4 text-sm"
    :class="analysisNotice.type === 'success' ? 'border-green-500/30 bg-green-500/10' : 'border-destructive/30 bg-destructive/10 text-destructive'"
    role="status"
    aria-live="polite"
  >
    <CheckCircle2 v-if="analysisNotice.type === 'success'" class="mt-0.5 h-5 w-5 shrink-0" />
    <AlertCircle v-else class="mt-0.5 h-5 w-5 shrink-0" />
    <span>{{ analysisNotice.message }}</span>
  </div>
  <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div><div class="flex items-center gap-2 text-sm font-medium text-primary"><Brain class="h-4 w-4"/> Marketplace intelligence</div><h1 class="mt-2 text-3xl font-bold">Search Term Intelligence</h1><p class="mt-2 max-w-3xl text-muted-foreground">Preserve what visitors typed, group spelling variants, review Qwen suggestions, and apply only approved aliases to live search.</p></div>
    <Button @click="rebuild"><RefreshCw class="mr-2 h-4 w-4"/>Rebuild search terms</Button>
  </div>
  <form class="grid gap-4 rounded-xl border bg-card p-5 md:grid-cols-4 md:items-end" @submit.prevent="apply">
    <div class="grid gap-2 md:col-span-2"><Label>Find a term</Label><Input v-model="filters.search" placeholder="campground, beach, couple..."/></div>
    <div class="grid gap-2"><Label>Status</Label><select v-model="filters.status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">All</option><option value="unanalyzed">Unanalyzed</option><option value="pending">Pending review</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div>
    <Button type="submit"><Search class="mr-2 h-4 w-4"/>Apply</Button>
  </form>
  <ShowSection title="Search terms" description="AI suggestions never affect live search until an administrator approves them.">
    <div class="space-y-4">
      <article v-for="row in terms.data" :key="row.id" class="rounded-xl border p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:justify-between">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-semibold">{{row.term}}</h2><span class="rounded-full bg-muted px-2 py-1 text-xs">{{row.mapping?.status ?? 'unanalyzed'}}</span><span v-if="row.mapping?.confidence != null" class="text-xs text-muted-foreground">{{Math.round(row.mapping.confidence*100)}}% confidence</span></div>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted-foreground"><span>{{row.search_count}} searches</span><span>{{row.zero_result_count}} zero-result</span><span>{{row.average_results}} avg. results</span><span>{{row.click_count}} views</span><span>{{row.order_count}} orders</span><span>{{money(row.revenue_cents)}}</span></div>
            <div v-if="row.variants.length" class="mt-3 text-sm"><span class="font-medium">Observed variants: </span><span class="text-muted-foreground">{{row.variants.map((v:any)=>`${v.term} (${v.count})`).join(', ')}}</span></div>
            <p v-if="row.mapping?.explanation" class="mt-3 rounded-lg bg-muted/50 p-3 text-sm">{{row.mapping.explanation}}</p>
          </div>
          <Button variant="outline" :disabled="analyzingId !== null" @click="analyze(row.id)">
            <LoaderCircle v-if="analyzingId === row.id" class="mr-2 h-4 w-4 animate-spin" />
            <Sparkles v-else class="mr-2 h-4 w-4"/>
            {{ analyzingId === row.id ? 'Analyzing with Qwen…' : (row.mapping ? 'Analyze again' : 'Analyze with Qwen') }}
          </Button>
        </div>
        <div v-if="row.mapping" class="mt-5 grid gap-4 lg:grid-cols-2">
          <div class="grid gap-2"><Label>Canonical search term</Label><Input v-model="stateFor(row).canonical_term"/></div>
          <div class="grid gap-2"><Label>Approved synonyms, comma separated</Label><Input v-model="stateFor(row).synonyms_text"/></div>
          <label class="flex items-center gap-2 text-sm"><input v-model="stateFor(row).is_content_opportunity" type="checkbox"/>Mark as a content opportunity</label>
          <div class="flex flex-wrap justify-end gap-2"><Button variant="outline" @click="save(row,'rejected')">Reject</Button><Button variant="secondary" @click="save(row,'pending')">Keep pending</Button><Button @click="save(row,'approved')">Approve and apply</Button></div>
        </div>
      </article>
      <div v-if="!terms.data.length" class="py-12 text-center text-muted-foreground">No search terms match these filters. Run the rebuild after search activity has been recorded.</div>
    </div>
  </ShowSection>
  <div v-if="terms.links?.length" class="flex flex-wrap gap-2"><Button v-for="link in terms.links" :key="link.label" variant="outline" size="sm" :disabled="!link.url" @click="link.url && router.get(link.url)"><span v-html="link.label"/></Button></div>
</div>
</template>
