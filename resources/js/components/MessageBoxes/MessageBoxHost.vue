<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { X } from '@lucide/vue';

interface ButtonConfig { label: string; url?: string | null; style?: 'primary'|'secondary'|'outline' }
interface FieldConfig { name:string; label:string; type:'text'|'email'|'textarea'|'select'|'checkbox'; required?:boolean; placeholder?:string|null; options?:string[] }
interface ManagedMessage {
    id:number; uuid:string; name:string; title?:string|null; body_html?:string|null; image_url?:string|null;
    presentation:'modal'|'bottom_banner'|'top_banner'; is_dismissible:boolean; buttons:ButtonConfig[]; form_fields:FieldConfig[];
    form_submit_label:string; form_success_message:string;
}

const queue = ref<ManagedMessage[]>([]);
const current = ref<ManagedMessage|null>(null);
const formData = reactive<Record<string, any>>({});
const formErrors = ref<Record<string,string[]>>({});
const submitting = ref(false);
const successMessage = ref('');
let removeNavigateListener: (()=>void)|null = null;

function visitorToken(): string {
    const key='uc_message_visitor';
    let value=localStorage.getItem(key);
    if (!value) { value=crypto.randomUUID(); localStorage.setItem(key,value); }
    return value;
}
function csrfToken(): string { return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''; }
function headers(): Record<string,string> { return {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrfToken(),'X-Message-Visitor':visitorToken()}; }

async function load(triggerType:'auto'|'action'='auto', triggerKey?:string): Promise<void> {
    const params=new URLSearchParams({path:window.location.pathname+window.location.search,trigger_type:triggerType});
    if(triggerKey) params.set('trigger_key',triggerKey);
    const response=await fetch(`/message-boxes/eligible?${params.toString()}`,{headers:{'Accept':'application/json','X-Message-Visitor':visitorToken()},credentials:'same-origin'});
    if(!response.ok) return;
    const data=await response.json();
    enqueue(data.messages ?? []);
}
function enqueue(messages:ManagedMessage[]):void {
    const ids=new Set([current.value?.id,...queue.value.map(x=>x.id)]);
    queue.value.push(...messages.filter(x=>!ids.has(x.id)));
    showNext();
}
function showNext():void {
    if(current.value || !queue.value.length) return;
    current.value=queue.value.shift() ?? null;
    successMessage.value=''; formErrors.value={};
    Object.keys(formData).forEach(k=>delete formData[k]);
    current.value?.form_fields.forEach(f=>{formData[f.name]=f.type==='checkbox'?false:''});
    if(current.value) mark('seen');
}
async function mark(action:'seen'|'dismiss'):Promise<void> {
    if(!current.value) return;
    fetch(`/message-boxes/${current.value.id}/${action}`,{method:'POST',headers:headers(),credentials:'same-origin',body:'{}'}).catch(()=>{});
}
async function close(dismiss=true):Promise<void> {
    if(dismiss) await mark('dismiss');
    current.value=null; showNext();
}
function action(button:ButtonConfig):void {
    if(button.url) {
        if(/^https?:\/\//i.test(button.url)) window.location.href=button.url;
        else router.visit(button.url);
    }
    close(false);
}
async function submit():Promise<void> {
    if(!current.value) return;
    submitting.value=true; formErrors.value={};
    const response=await fetch(`/message-boxes/${current.value.id}/submit`,{method:'POST',headers:headers(),credentials:'same-origin',body:JSON.stringify(formData)});
    submitting.value=false;
    const data=await response.json().catch(()=>({}));
    if(response.status===422){formErrors.value=data.errors??{};return;}
    if(!response.ok) return;
    successMessage.value=data.message??current.value.form_success_message;
}
function triggerFromClick(event:Event):void {
    const target=(event.target as HTMLElement)?.closest<HTMLElement>('[data-message-box-trigger]');
    const key=target?.dataset.messageBoxTrigger;
    if(key){event.preventDefault();load('action',key);}
}
function customTrigger(event:Event):void { const key=(event as CustomEvent<string>).detail; if(key) load('action',key); }
function onKey(event:KeyboardEvent):void { if(event.key==='Escape' && current.value?.is_dismissible) close(); }

onMounted(()=>{
    load();
    document.addEventListener('click',triggerFromClick);
    window.addEventListener('message-box:trigger',customTrigger as EventListener);
    window.addEventListener('keydown',onKey);
    removeNavigateListener=router.on('navigate',()=>setTimeout(()=>load(),0));
});
onBeforeUnmount(()=>{
    document.removeEventListener('click',triggerFromClick);
    window.removeEventListener('message-box:trigger',customTrigger as EventListener);
    window.removeEventListener('keydown',onKey);
    removeNavigateListener?.();
});
</script>

<template>
<Teleport to="body">
    <Transition name="message-fade">
        <div v-if="current" class="fixed inset-0 z-[150]" :class="current.presentation==='modal'?'flex items-center justify-center bg-black/55 p-4':'pointer-events-none'">
            <section
                :class="[
                    'pointer-events-auto relative overflow-auto border bg-background shadow-2xl',
                    current.presentation==='modal'?'max-h-[90vh] w-full max-w-2xl rounded-2xl':
                    current.presentation==='top_banner'?'absolute inset-x-0 top-0 h-[200px] rounded-b-xl':'absolute inset-x-0 bottom-0 h-[200px] rounded-t-xl'
                ]"
                role="dialog" aria-modal="true" :aria-label="current.title || current.name"
            >
                <button v-if="current.is_dismissible" type="button" class="absolute right-3 top-3 z-10 rounded-full bg-background/90 p-2 shadow hover:bg-muted" aria-label="Close message" @click="close()"><X class="h-4 w-4"/></button>
                <div :class="current.presentation==='modal'?'':'mx-auto flex h-full max-w-[1440px] items-stretch'">
                    <img v-if="current.image_url" :src="current.image_url" alt="" :class="current.presentation==='modal'?'max-h-72 w-full object-cover':'hidden w-1/3 object-cover md:block'"/>
                    <div :class="['flex-1',current.presentation==='modal'?'p-6 sm:p-8':'flex items-center p-5 sm:px-8']">
                        <div :class="current.presentation==='modal'?'w-full':'grid w-full gap-4 md:grid-cols-[1fr_auto] md:items-center'">
                            <div>
                                <h2 v-if="current.title" class="pr-10 text-xl font-semibold sm:text-2xl">{{ current.title }}</h2>
                                <div v-if="current.body_html" class="prose prose-sm mt-3 max-w-none dark:prose-invert" v-html="current.body_html"/>
                                <p v-if="successMessage" class="mt-3 rounded-md bg-muted p-3 text-sm font-medium">{{ successMessage }}</p>
                            </div>
                            <div>
                                <form v-if="current.form_fields.length && !successMessage" class="mt-5 grid gap-3" :class="current.presentation==='modal'?'':'md:mt-0 md:min-w-80'" @submit.prevent="submit">
                                    <label v-for="field in current.form_fields" :key="field.name" class="text-sm">
                                        <span v-if="field.type!=='checkbox'" class="mb-1 block font-medium">{{ field.label }}<span v-if="field.required"> *</span></span>
                                        <textarea v-if="field.type==='textarea'" v-model="formData[field.name]" :required="field.required" :placeholder="field.placeholder??''" rows="3" class="w-full rounded-md border bg-background p-2"/>
                                        <select v-else-if="field.type==='select'" v-model="formData[field.name]" :required="field.required" class="h-10 w-full rounded-md border bg-background px-2"><option value="">Select…</option><option v-for="o in field.options" :key="o" :value="o">{{o}}</option></select>
                                        <span v-else-if="field.type==='checkbox'" class="flex items-center gap-2"><input v-model="formData[field.name]" type="checkbox" :required="field.required"/>{{field.label}}</span>
                                        <input v-else v-model="formData[field.name]" :type="field.type" :required="field.required" :placeholder="field.placeholder??''" class="h-10 w-full rounded-md border bg-background px-3"/>
                                        <span v-if="formErrors[field.name]?.length" class="mt-1 block text-xs text-destructive">{{formErrors[field.name][0]}}</span>
                                    </label>
                                    <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50" :disabled="submitting">{{ submitting?'Submitting…':current.form_submit_label }}</button>
                                </form>
                                <div v-if="current.buttons.length" class="mt-5 flex flex-wrap gap-2" :class="current.presentation==='modal'?'':'md:mt-0 md:justify-end'">
                                    <button v-for="(button,index) in current.buttons" :key="index" type="button" :class="['rounded-md px-4 py-2 text-sm font-medium',button.style==='outline'?'border bg-background':button.style==='secondary'?'bg-secondary text-secondary-foreground':'bg-primary text-primary-foreground']" @click="action(button)">{{button.label}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </Transition>
</Teleport>
</template>

<style scoped>
.message-fade-enter-active,.message-fade-leave-active{transition:opacity .2s ease}.message-fade-enter-from,.message-fade-leave-to{opacity:0}
</style>
