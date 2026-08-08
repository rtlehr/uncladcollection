<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PageHeader from '@/components/Shared/PageHeader.vue';
import StatusBadge from '@/components/Support/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
const props=defineProps<{mode:'guest'|'member';ticket:any;guestToken?:string;attachmentRules:{max_kb:number;extensions:string[]}}>();
defineOptions({layout:PublicPageLayout});
const form=useForm({body:'',attachments:[] as File[]});
const reply=()=>form.post(props.mode==='guest'?`/support/guest/${props.ticket.uuid}/${props.guestToken}/reply`:`/support/tickets/${props.ticket.uuid}/reply`,{forceFormData:true,preserveScroll:true,onSuccess:()=>form.reset()});
</script>
<template><Head :title="ticket.ticket_number"/><div class="mx-auto max-w-4xl space-y-6 p-6 sm:py-10"><PageHeader :title="ticket.subject" :description="`${ticket.ticket_number} · ${ticket.category || 'General'}`"><template #actions><StatusBadge :status="ticket.status"/></template></PageHeader>
<div class="rounded-xl border bg-card p-5"><p class="whitespace-pre-wrap">{{ ticket.description }}</p><p class="mt-4 text-sm text-muted-foreground">Submitted {{ new Date(ticket.created_at).toLocaleString() }}</p></div>
<section aria-labelledby="conversation-heading" class="space-y-4"><h2 id="conversation-heading" class="text-xl font-semibold">Conversation</h2><article v-for="message in ticket.messages" :key="message.id" class="rounded-xl border bg-card p-5"><div class="mb-3 flex justify-between gap-4"><strong>{{ message.author_name }}</strong><time class="text-sm text-muted-foreground">{{ new Date(message.created_at).toLocaleString() }}</time></div><p class="whitespace-pre-wrap">{{ message.body }}</p><div v-if="message.attachments.length" class="mt-4 flex flex-wrap gap-2"><a v-for="file in message.attachments" :key="file.uuid" :href="file.url" class="rounded-md border px-3 py-2 text-sm underline">{{ file.name }}</a></div></article></section>
<form v-if="ticket.can_reply" class="space-y-4 rounded-xl border bg-card p-5" @submit.prevent="reply"><h2 class="text-lg font-semibold">Add a reply</h2><Textarea v-model="form.body" class="min-h-36" required/><InputError :message="form.errors.body"/><Input type="file" multiple @change="form.attachments=Array.from(($event.target as HTMLInputElement).files||[])"/><Button :disabled="form.processing">{{ form.processing?'Sending…':'Send reply' }}</Button></form>
<div v-if="mode==='member'" class="flex gap-3"><Button v-if="ticket.can_reopen" variant="outline" @click="router.post(`/support/tickets/${ticket.uuid}/reopen`)">Reopen ticket</Button><Button v-else-if="ticket.can_reply" variant="outline" @click="router.post(`/support/tickets/${ticket.uuid}/close`)">Close ticket</Button></div>
</div></template>
