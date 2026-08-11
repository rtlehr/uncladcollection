<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import RichTextEditor from '@/components/admin/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Btn={label:string;url:string;style:'primary'|'secondary'|'outline'};
type Field={name:string;label:string;type:'text'|'email'|'textarea'|'select'|'checkbox';required:boolean;placeholder:string;options_text:string};
const props=defineProps<{messageBox?:any|null}>();
const imagePreview=ref<string|null>(props.messageBox?.image_url??null);
const form=useForm({
 name:props.messageBox?.name??'',title:props.messageBox?.title??'',body_html:props.messageBox?.body_html??'',image:null as File|null,remove_image:false,
 presentation:props.messageBox?.presentation??'modal',trigger_type:props.messageBox?.trigger_type??'auto',trigger_key:props.messageBox?.trigger_key??'',
 page_patterns_text:(props.messageBox?.page_patterns??['/']).join('\n'),audience:props.messageBox?.audience??'all',show_once:props.messageBox?.show_once??false,
 is_dismissible:props.messageBox?.is_dismissible??true,is_active:props.messageBox?.is_active??true,priority:props.messageBox?.priority??100,
 starts_at:props.messageBox?.starts_at?.slice(0,16)??'',ends_at:props.messageBox?.ends_at?.slice(0,16)??'',
 buttons:(props.messageBox?.buttons??[]) as Btn[],form_fields:(props.messageBox?.form_fields??[]).map((f:any)=>({...f,options_text:(f.options??[]).join('\n')})) as Field[],
 form_submit_label:props.messageBox?.form_submit_label??'Submit',form_success_message:props.messageBox?.form_success_message??'Thank you. Your response has been received.'
});
const banner=computed(()=>form.presentation!=='modal');
function pickImage(e:Event){const file=(e.target as HTMLInputElement).files?.[0]??null;form.image=file;if(file) imagePreview.value=URL.createObjectURL(file);}
function addButton(){if(form.buttons.length<3)form.buttons.push({label:'Learn More',url:'/',style:'primary'});}
function addField(){if(form.form_fields.length<10)form.form_fields.push({name:`field_${form.form_fields.length+1}`,label:'Field label',type:'text',required:false,placeholder:'',options_text:''});}
function submit(){
 const url=props.messageBox?`/admin/message-boxes/${props.messageBox.id}`:'/admin/message-boxes';
 form.transform(d=>props.messageBox?{...d,_method:'put'}:d).post(url,{forceFormData:true,preserveScroll:true});
}
</script>
<template>
<form class="space-y-6" @submit.prevent="submit">
 <div class="grid gap-6 xl:grid-cols-2">
  <section class="rounded-xl border bg-card p-6"><h2 class="text-lg font-semibold">Message</h2><div class="mt-5 space-y-4">
   <label class="block text-sm font-medium">Internal name<Input v-model="form.name" class="mt-2" required/></label>
   <label class="block text-sm font-medium">Public title<Input v-model="form.title" class="mt-2"/></label>
   <div><div class="mb-2 text-sm font-medium">Message content</div><RichTextEditor v-model="form.body_html"/></div>
   <label class="block text-sm font-medium">Optional image<input type="file" accept="image/*" class="mt-2 block w-full text-sm" @change="pickImage"/></label>
   <img v-if="imagePreview" :src="imagePreview" alt="Message preview" class="max-h-48 rounded-lg border object-cover"/>
   <label v-if="messageBox?.image_url" class="flex items-center gap-2 text-sm"><input v-model="form.remove_image" type="checkbox"/> Remove current image</label>
  </div></section>
  <section class="rounded-xl border bg-card p-6"><h2 class="text-lg font-semibold">Display & targeting</h2><div class="mt-5 grid gap-4 sm:grid-cols-2">
   <label class="text-sm font-medium">Style<select v-model="form.presentation" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option value="modal">Modal window</option><option value="bottom_banner">Rise from bottom — 200px</option><option value="top_banner">Lower from top — 200px</option></select></label>
   <label class="text-sm font-medium">Trigger<select v-model="form.trigger_type" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option value="auto">Automatically on page load</option><option value="action">User action / trigger key</option></select></label>
   <label v-if="form.trigger_type==='action'" class="text-sm font-medium sm:col-span-2">Trigger key<Input v-model="form.trigger_key" class="mt-2" placeholder="welcome.signup-info"/><span class="mt-1 block text-xs text-muted-foreground">Use on an element as data-message-box-trigger="welcome.signup-info".</span></label>
   <label class="text-sm font-medium sm:col-span-2">Pages<textarea v-model="form.page_patterns_text" rows="4" class="mt-2 w-full rounded-md border bg-background p-3" placeholder="/&#10;/blog/*"/><span class="mt-1 block text-xs text-muted-foreground">One per line. Use * for every page. Wildcards are supported.</span></label>
   <label class="text-sm font-medium">Audience<select v-model="form.audience" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option value="all">Everyone</option><option value="guests">Guests only</option><option value="authenticated">Signed-in users only</option></select></label>
   <label class="text-sm font-medium">Priority<Input v-model="form.priority" type="number" min="0" class="mt-2"/></label>
   <label class="text-sm font-medium">Starts<Input v-model="form.starts_at" type="datetime-local" class="mt-2"/></label><label class="text-sm font-medium">Ends<Input v-model="form.ends_at" type="datetime-local" class="mt-2"/></label>
  </div><div class="mt-5 grid gap-3 sm:grid-cols-3"><label class="flex gap-2 text-sm"><input v-model="form.is_active" type="checkbox"/> Active</label><label class="flex gap-2 text-sm"><input v-model="form.show_once" type="checkbox"/> Show only once per user/device</label><label class="flex gap-2 text-sm"><input v-model="form.is_dismissible" type="checkbox"/> User can close it</label></div>
  <div v-if="banner" class="mt-5 rounded-md bg-muted p-3 text-sm">Top and bottom messages are fixed at 200px tall and span the full browser width.</div>
  </section>
 </div>
 <section class="rounded-xl border bg-card p-6"><div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold">Action buttons</h2><p class="text-sm text-muted-foreground">Add up to three optional buttons.</p></div><Button type="button" variant="outline" @click="addButton">Add Button</Button></div>
  <div class="mt-4 space-y-3"><div v-for="(b,i) in form.buttons" :key="i" class="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_1fr_160px_auto]"><Input v-model="b.label" placeholder="Button label"/><Input v-model="b.url" placeholder="/images or https://..."/><select v-model="b.style" class="h-10 rounded-md border bg-background px-3"><option value="primary">Primary</option><option value="secondary">Secondary</option><option value="outline">Outline</option></select><Button type="button" variant="ghost" @click="form.buttons.splice(i,1)">Remove</Button></div></div>
 </section>
 <section class="rounded-xl border bg-card p-6"><div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold">Optional form</h2><p class="text-sm text-muted-foreground">Collect simple responses directly in the message.</p></div><Button type="button" variant="outline" @click="addField">Add Field</Button></div>
  <div class="mt-4 space-y-3"><div v-for="(f,i) in form.form_fields" :key="i" class="grid gap-3 rounded-lg border p-3 lg:grid-cols-6"><Input v-model="f.name" placeholder="field_name"/><Input v-model="f.label" placeholder="Label"/><select v-model="f.type" class="h-10 rounded-md border bg-background px-3"><option value="text">Text</option><option value="email">Email</option><option value="textarea">Textarea</option><option value="select">Select</option><option value="checkbox">Checkbox</option></select><Input v-model="f.placeholder" placeholder="Placeholder"/><label class="flex items-center gap-2 text-sm"><input v-model="f.required" type="checkbox"/> Required</label><Button type="button" variant="ghost" @click="form.form_fields.splice(i,1)">Remove</Button><textarea v-if="f.type==='select'" v-model="f.options_text" rows="3" class="rounded-md border bg-background p-2 text-sm lg:col-span-6" placeholder="One option per line"/></div></div>
  <div v-if="form.form_fields.length" class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-sm font-medium">Submit button<Input v-model="form.form_submit_label" class="mt-2"/></label><label class="text-sm font-medium">Success message<Input v-model="form.form_success_message" class="mt-2"/></label></div>
 </section>
 <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive"><div v-for="(error,key) in form.errors" :key="key">{{error}}</div></div>
 <div class="flex justify-end"><Button :disabled="form.processing">{{messageBox?'Save Changes':'Create Message'}}</Button></div>
</form>
</template>
