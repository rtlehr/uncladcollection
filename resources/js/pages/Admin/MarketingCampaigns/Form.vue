<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImageIcon, Pencil } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import ImageEditorDialog from '@/components/media/ImageEditorDialog.vue';
import type {ImageEditData} from '@/components/media/ImageEditorDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { MARKETING_HERO_PRESET } from '@/config/imageEditorPresets';
import type { MarketingCampaign } from '@/types/marketingCampaign';

const props = defineProps<{ campaign?: MarketingCampaign | null }>();
const marketingPreset = MARKETING_HERO_PRESET;
const editorOpen = ref(false);
const selectedOriginal = ref<File | null>(null);
const originalSource = ref<File | string | null>(props.campaign?.media_original_url ?? props.campaign?.media_url ?? null);
const editedPreview = ref<string | null>(props.campaign?.media_url ?? null);
const editData = ref<ImageEditData | null>(props.campaign?.media_edit_data ?? null);

const form = useForm({
    name: props.campaign?.name ?? '', media_type: props.campaign?.media_type ?? 'image', media: null as File | null,
    media_original: null as File | null, media_edit_data: props.campaign?.media_edit_data ? JSON.stringify(props.campaign.media_edit_data) : '',
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
const editorFilename = computed(() => selectedOriginal.value?.name ?? 'marketing-hero.jpg');
function chooseMedia(e: Event): void {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;

    if (!file) {
return;
}

    if (file.type.startsWith('image/')) {
        selectedOriginal.value = file;
        originalSource.value = file;
        form.media_original = file;
        editorOpen.value = true;
    } else {
        form.media = file;
    }
}
function choosePoster(e: Event): void {
 form.poster = (e.target as HTMLInputElement).files?.[0] ?? null; 
}
function applyEdited(payload: { file: File; edit: ImageEditData; previewUrl: string }): void {
    if (editedPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(editedPreview.value);
    }

    editedPreview.value = payload.previewUrl;
    editData.value = payload.edit;

    // Assign a fresh File instance to the Inertia form so it is always detected
    // as multipart upload data in both development and managed hosting.
    form.media = new File(
        [payload.file],
        payload.file.name || 'marketing-hero.jpg',
        {
            type: payload.file.type || marketingPreset.outputType,
            lastModified: Date.now(),
        },
    );

    form.media_type = 'image';
    form.media_edit_data = JSON.stringify(payload.edit);
}

function submit(): void {
    const url = props.campaign
        ? `/admin/marketing-campaigns/${props.campaign.id}`
        : '/admin/marketing-campaigns';

    form
        .transform((data) => (
            props.campaign
                ? {
                    ...data,
                    _method: 'put',
                }
                : data
        ))
        .post(url, {
            forceFormData: true,
            preserveScroll: true,
        });
}
onBeforeUnmount(() => {
 if (editedPreview.value?.startsWith('blob:')) {
URL.revokeObjectURL(editedPreview.value);
} 
});
</script>

<template>
<form class="space-y-6" @submit.prevent="submit">
<ImageEditorDialog v-model:open="editorOpen" :source="originalSource" :filename="editorFilename" :preset="marketingPreset" :initial-edit="editData" @apply="applyEdited" />
<div class="grid gap-6 xl:grid-cols-2">
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Campaign media</h2><div class="mt-5 space-y-4">
<label class="block text-sm font-medium">Internal name<Input v-model="form.name" class="mt-2" /></label>
<label class="block text-sm font-medium">Media type<select v-model="form.media_type" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option value="image">Image</option><option value="video">Video</option></select></label>
<div v-if="form.media_type === 'image'" class="space-y-3">
<label class="block text-sm font-medium">{{ campaign ? 'Replace image' : 'Marketing image' }}<input class="mt-2 block w-full text-sm" type="file" accept="image/jpeg,image/png,image/webp" @change="chooseMedia" /></label>
<div v-if="editedPreview" class="overflow-hidden rounded-xl border bg-muted"><img :src="editedPreview" alt="Edited marketing preview" class="aspect-[12/5] w-full object-cover"><div class="flex items-center justify-between gap-3 p-3"><div class="flex items-center gap-2 text-sm"><ImageIcon class="h-4 w-4" /> {{ marketingPreset.width }} × {{ marketingPreset.height }}</div><Button type="button" size="sm" variant="outline" @click="editorOpen = true"><Pencil class="mr-2 h-4 w-4" /> Edit crop</Button></div></div>
<p class="text-xs text-muted-foreground">The original upload is retained and a separate optimized marketing image is saved.</p>
</div>
<label v-else class="block text-sm font-medium">{{ campaign ? 'Replace video' : 'Video file' }}<input class="mt-2 block w-full text-sm" type="file" accept="video/mp4,video/webm" @change="chooseMedia" /></label>
<label class="block text-sm font-medium">Video poster image<input class="mt-2 block w-full text-sm" type="file" accept="image/*" @change="choosePoster" /></label>
<label v-if="campaign?.poster_url" class="flex gap-2 text-sm"><input v-model="form.remove_poster" type="checkbox" /> Remove current poster</label>
<p v-if="form.errors.media || form.errors.media_original" class="text-sm text-destructive">{{ form.errors.media || form.errors.media_original }}</p>
</div></section>
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Message and actions</h2><div class="mt-5 space-y-4"><label class="block text-sm font-medium">Eyebrow<Input v-model="form.eyebrow" class="mt-2" /></label><label class="block text-sm font-medium">Headline<Input v-model="form.headline" class="mt-2" /></label><label class="block text-sm font-medium">Subheadline<textarea v-model="form.subheadline" rows="4" class="mt-2 w-full rounded-md border bg-background p-3" /></label><div class="grid gap-3 sm:grid-cols-2"><Input v-model="form.primary_button_label" placeholder="Primary label"/><Input v-model="form.primary_button_url" placeholder="/images"/><Input v-model="form.secondary_button_label" placeholder="Secondary label"/><Input v-model="form.secondary_button_url" placeholder="/blog"/></div></div></section>
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Presentation</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><label class="text-sm">Overlay opacity<Input v-model="form.overlay_opacity" type="number" min="0" max="90" class="mt-2"/></label><label class="text-sm">Media position<select v-model="form.media_position" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option v-for="v in ['center','top','bottom','left','right']" :key="v" :value="v">{{v}}</option></select></label><label class="text-sm">Hero height<select v-model="form.hero_height" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option v-for="v in ['compact','medium','large','fullscreen']" :key="v" :value="v">{{v}}</option></select></label><label class="text-sm">Text alignment<select v-model="form.text_alignment" class="mt-2 h-10 w-full rounded-md border bg-background px-3"><option v-for="v in ['left','center','right']" :key="v" :value="v">{{v}}</option></select></label></div><div class="mt-5 grid gap-3 sm:grid-cols-2"><label v-for="[k,l] in [['autoplay_first_visit','Autoplay first visit'],['autoplay_mobile','Autoplay on mobile'],['loop_video','Loop video'],['show_search','Show search'],['is_active','Active']]" :key="k" class="flex gap-2 text-sm"><input v-model="(form as any)[k]" type="checkbox"/>{{l}}</label></div></section>
<section class="rounded-xl border p-6"><h2 class="text-lg font-semibold">Schedule</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><label class="text-sm">Starts<Input v-model="form.starts_at" type="datetime-local" class="mt-2"/></label><label class="text-sm">Ends<Input v-model="form.ends_at" type="datetime-local" class="mt-2"/></label><label class="text-sm">Priority<Input v-model="form.sort_order" type="number" min="0" class="mt-2"/></label></div></section>
</div><div class="flex justify-end gap-3"><Button type="button" variant="outline" @click="history.back()">Cancel</Button><Button type="submit" :disabled="form.processing">{{form.processing?'Saving...':'Save Campaign'}}</Button></div>
</form>
</template>
