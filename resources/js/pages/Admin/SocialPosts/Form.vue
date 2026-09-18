<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const props = defineProps<{socialPost?:any; xConfigured:boolean}>();
const existingMedia = ref(!!props.socialPost?.media_path);
const form = useForm({
    text: props.socialPost?.text ?? '',
    scheduled_at: props.socialPost?.scheduled_at ? toLocalInput(props.socialPost.scheduled_at) : '',
    media: null as File|null,
    remove_media: false,
});

function toLocalInput(value:string) {
    const d = new Date(value);
    const pad=(n:number)=>String(n).padStart(2,'0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
function onFile(e:Event) {
    const target=e.target as HTMLInputElement;
    form.media=target.files?.[0] ?? null;
}
function removeExisting() {
    form.remove_media=true;
    existingMedia.value=false;
}
function scheduledAtForServer(value:string) {
    if (!value) return '';
    // datetime-local has no timezone information. Treat the selected value as
    // the administrator's local browser time, then send an explicit UTC ISO
    // timestamp to Laravel so it is stored and compared correctly in UTC.
    return new Date(value).toISOString();
}

function submit() {
    form.transform(data => ({
        ...data,
        scheduled_at: scheduledAtForServer(data.scheduled_at),
        ...(props.socialPost ? {_method:'put'} : {}),
    }));

    if (props.socialPost) {
        form.post(`/admin/social-posts/${props.socialPost.id}`, {forceFormData:true});
    } else {
        form.post('/admin/social-posts', {forceFormData:true});
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div v-if="!xConfigured" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            You can save drafts now, but publishing will fail until the X API credentials are added to <code>.env</code>.
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium">Post text</label>
            <textarea v-model="form.text" rows="8" class="w-full rounded-md border bg-background px-3 py-2" placeholder="What do you want to post on X?" />
            <div class="flex justify-between text-xs text-muted-foreground">
                <span>{{ form.errors.text }}</span><span>{{ form.text.length.toLocaleString() }} characters</span>
            </div>
            <p class="text-xs text-muted-foreground">No application-side character limit is imposed. X will apply the posting limits available to the connected account.</p>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium">Image or video <span class="text-muted-foreground font-normal">(optional)</span></label>
            <div v-if="existingMedia && socialPost?.media_url" class="space-y-2 rounded-lg border p-3">
                <img v-if="socialPost.media_type === 'image'" :src="socialPost.media_url" class="max-h-64 rounded object-contain" />
                <video v-else controls :src="socialPost.media_url" class="max-h-64 rounded" />
                <Button type="button" size="sm" variant="outline" @click="removeExisting">Remove existing media</Button>
            </div>
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime" @change="onFile" />
            <p class="text-xs text-muted-foreground">Images and MP4/MOV video are supported. X account/API limits still apply.</p>
            <p v-if="form.errors.media" class="text-xs text-destructive">{{ form.errors.media }}</p>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium">Schedule <span class="text-muted-foreground font-normal">(optional)</span></label>
            <input v-model="form.scheduled_at" type="datetime-local" class="rounded-md border bg-background px-3 py-2" />
            <p class="text-xs text-muted-foreground">Leave blank to save as a draft. Scheduled posts are published by Laravel's scheduler.</p>
            <p v-if="form.errors.scheduled_at" class="text-xs text-destructive">{{ form.errors.scheduled_at }}</p>
        </div>

        <div class="flex gap-2">
            <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving…' : (form.scheduled_at ? 'Save Scheduled Post' : 'Save Draft') }}</Button>
        </div>
    </form>
</template>
