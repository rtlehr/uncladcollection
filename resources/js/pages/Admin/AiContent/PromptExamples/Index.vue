<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'; import { ref } from 'vue';
import PageHeader from '@/Components/Shared/PageHeader.vue'; import ShowSection from '@/Components/Show/ShowSection.vue'; import { Button } from '@/components/ui/button'; import { Input } from '@/components/ui/input'; import { appConfirm } from '@/lib/appDialog';
const props=defineProps<{items:any,filters:any}>(); const search=ref(props.filters.search??''); const upload=useForm<{file:File|null}>({file:null});
function find(){
router.get('/admin/ai-content/prompt-library',{search:search.value||undefined},{preserveState:true,replace:true});
}
function importFile(){
upload.post('/admin/ai-content/prompt-library/import',{forceFormData:true});
}
function toggle(item:any){
router.put(`/admin/ai-content/prompt-library/${item.id}`,{...item,is_enabled:!item.is_enabled},{preserveScroll:true});
}
async function remove(item:any){
if(await appConfirm(`Delete ${item.title}?`, { title: 'Delete prompt example?', confirmLabel: 'Delete Example', destructive: true })){
router.delete(`/admin/ai-content/prompt-library/${item.id}`,{preserveScroll:true});
}
}
</script><template><Head title="AI Prompt Library"/><div class="space-y-6 p-6"><PageHeader title="AI Prompt Library" description="Curated examples used as structural inspiration. Explicit sexual content and duplicate prompts have been removed."/><ShowSection title="Import JSON"><form class="flex flex-wrap items-end gap-3" @submit.prevent="importFile"><label class="text-sm font-medium">JSON file<Input type="file" accept="application/json,.json" @change="upload.file=($event.target as HTMLInputElement).files?.[0]??null"/></label><Button :disabled="!upload.file||upload.processing">Import</Button></form></ShowSection><ShowSection title="Prompt examples" :description="`${items.total} prompt(s)`"><form class="mb-4 flex gap-2" @submit.prevent="find"><Input v-model="search" placeholder="Search title or content..."/><Button variant="outline">Search</Button></form><div class="divide-y rounded-xl border"><article v-for="item in items.data" :key="item.id" class="p-4"><div class="flex gap-4 justify-between"><div><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold">{{item.title}}</h3><span class="rounded-full bg-muted px-2 py-0.5 text-xs">{{item.category||'Uncategorized'}}</span><span class="rounded-full px-2 py-0.5 text-xs" :class="item.is_enabled?'bg-emerald-100 text-emerald-800':'bg-muted'">{{item.is_enabled?'Enabled':'Disabled'}}</span></div><p class="mt-2 text-sm leading-6 text-muted-foreground">{{item.content}}</p></div><div class="flex shrink-0 gap-2"><Button size="sm" variant="outline" @click="toggle(item)">{{item.is_enabled?'Disable':'Enable'}}</Button><Button size="sm" variant="destructive" @click="remove(item)">Delete</Button></div></div></article></div><div class="mt-4 flex gap-2"><Button v-for="link in items.links" :key="link.label" size="sm" variant="outline" :disabled="!link.url" @click="link.url&&router.visit(link.url)" ><span v-html="link.label"></span></Button></div></ShowSection></div></template>
