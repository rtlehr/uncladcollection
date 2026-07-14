<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { MarketingCampaign } from '@/types/marketingCampaign';

const props = defineProps<{ campaign?: MarketingCampaign | null }>();
const form = useForm({
    name: props.campaign?.name ?? '', media_type: props.campaign?.media_type ?? 'image', media: null as File | null,
    poster: null as File | null, remove_poster: false, eyebrow: props.campaign?.eyebrow ?? '', headline: props.campaign?.headline ?? '',
    subheadline: props.campaign?.subheadline ?? '', primary_button_label: props.campaign?.primary_button_label ?? 'Browse Marketplace',
    primary_button_url: props.campaign?.primary_button_url ?? '/images', secondary_button_label: props.campaign?.secondary_button_label ?? 'Read Stories',
    secondary_button_url: props.campaign?.secondary_button_url ?? '/blog', overlay_opacity: props.campaign?.overlay_opacity ?? 35,
    media_position: props.campaign?.media_position ?? 'center', hero_height: props.campaign?.hero_height ?? 'large',
    text_alignment: props.campaign?.text_alignment ?? 'left', autoplay_first_visit: props.campaign?.autoplay_first_visit ?? true,
    autoplay_mobile: props.campaign?.autoplay_mobile ?? false, loop_video: props.campaign?.loop_video ?? true,
    show_search: props.campaign?.show_search ?? true, is_active: props.campaign?.is_active ?? true,
    sort_order: props.campaign?.sort_order ?? 0, starts_at: props.campaign?.starts_at?.slice(0,16) ?? '', ends_at: props.campaign?.ends_at?.slice(0,16) ?? '',
});
function file(e: Event, key: 'media'|'poster') { form[key] = (e.target as HTMLInputElement).files?.[0] ?? null; }
function submit() { const opts={forceFormData:true}; props.campaign ? form.post(`/admin/marketing-campaigns/${props.campaign.id}?_method=PUT`,opts) : form.post('/admin/marketing-campaigns',opts); }
</script>
<template><form class="space-y-6" @submit.prevent="submit">
<div class="grid gap-6 xl:grid-cols-2">
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Campaign media</h2><div class="mt-5 space-y-4">
<label class="block text-sm font-medium">Internal name<Input v-model="form.name" class="mt-2" /></label>
<label class="block text-sm font-medium">Media type<select v-model="form.media_type" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option value="image">Image</option><option value="video">Video</option></select></label>
<label class="block text-sm font-medium">{{ campaign ? 'Replace media' : 'Media file' }}<input class="mt-2 block w-full text-sm" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" @change="file($event,'media')" /></label>
<label class="block text-sm font-medium">Video poster image<input class="mt-2 block w-full text-sm" type="file" accept="image/*" @change="file($event,'poster')" /></label>
<label v-if="campaign?.poster_url" class="flex gap-2 text-sm"><input v-model="form.remove_poster" type="checkbox" /> Remove current poster</label>
</div></section>
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Message and actions</h2><div class="mt-5 space-y-4">
<label class="block text-sm font-medium">Eyebrow<Input v-model="form.eyebrow" class="mt-2" /></label><label class="block text-sm font-medium">Headline<Input v-model="form.headline" class="mt-2" /></label>
<label class="block text-sm font-medium">Subheadline<textarea v-model="form.subheadline" rows="4" class="mt-2 w-full rounded-md border bg-background p-3" /></label>
<div class="grid gap-3 sm:grid-cols-2"><Input v-model="form.primary_button_label" placeholder="Primary label"/><Input v-model="form.primary_button_url" placeholder="/images"/><Input v-model="form.secondary_button_label" placeholder="Secondary label"/><Input v-model="form.secondary_button_url" placeholder="/blog"/></div>
</div></section>
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Presentation</h2><div class="mt-5 grid gap-4 sm:grid-cols-2">
<label class="text-sm">Overlay opacity<Input v-model="form.overlay_opacity" type="number" min="0" max="90" class="mt-2"/></label>
<label class="text-sm">Media position<select v-model="form.media_position" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option v-for="v in ['center','top','bottom','left','right']" :value="v">{{v}}</option></select></label>
<label class="text-sm">Hero height<select v-model="form.hero_height" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option v-for="v in ['compact','medium','large','fullscreen']" :value="v">{{v}}</option></select></label>
<label class="text-sm">Text alignment<select v-model="form.text_alignment" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option v-for="v in ['left','center','right']" :value="v">{{v}}</option></select></label>
</div><div class="mt-5 grid gap-3 sm:grid-cols-2"><label v-for="[k,l] in [['autoplay_first_visit','Autoplay first visit'],['autoplay_mobile','Autoplay on mobile'],['loop_video','Loop video'],['show_search','Show search'],['is_active','Active']]" class="flex gap-2 text-sm"><input v-model="(form as any)[k]" type="checkbox"/>{{l}}</label></div></section>
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Schedule</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><label class="text-sm">Starts<Input v-model="form.starts_at" type="datetime-local" class="mt-2"/></label><label class="text-sm">Ends<Input v-model="form.ends_at" type="datetime-local" class="mt-2"/></label><label class="text-sm">Priority<Input v-model="form.sort_order" type="number" min="0" class="mt-2"/></label></div></section>
</div><div class="flex justify-end gap-3"><Button type="button" variant="outline" @click="history.back()">Cancel</Button><Button type="submit" :disabled="form.processing">{{form.processing?'Saving...':'Save Campaign'}}</Button></div></form></template>
