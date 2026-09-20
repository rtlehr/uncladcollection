<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '@/components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { appConfirm } from '@/lib/appDialog';

const props = defineProps<{posts:any; xConfigured:boolean}>();

async function removePost(id:number) {
    if (await appConfirm('Delete this social post?', {title:'Delete social post?', confirmLabel:'Delete', destructive:true})) {
        router.delete(`/admin/social-posts/${id}`);
    }
}
function publishNow(id:number) {
    router.post(`/admin/social-posts/${id}/publish-now`);
}
function verifyConnection() {
    router.post('/admin/social-posts/verify');
}
function statusClass(status:string) {
    return {
        draft:'text-muted-foreground', scheduled:'text-blue-600', publishing:'text-amber-600',
        published:'text-emerald-600', failed:'text-destructive'
    }[status] || 'text-muted-foreground';
}
</script>

<template>
    <Head title="X Posts" />
    <div class="space-y-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <PageHeader title="X Posts" description="Create, schedule, publish, and review posts sent to X." />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" @click="verifyConnection">Test X Connection</Button>
                <Button variant="outline" as-child><Link href="/admin/social-posts/ai">Generate with AI</Link></Button>
                <Button as-child><Link href="/admin/social-posts/create">Create Post</Link></Button>
            </div>
        </div>

        <div v-if="!xConfigured" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            X is not configured yet. Add the four <code>X_API_*</code> credentials to the server <code>.env</code>, then use “Test X Connection.”
        </div>

        <div class="overflow-hidden rounded-xl border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr><th class="p-3">Post</th><th class="p-3">Status</th><th class="p-3">Schedule / Published</th><th class="p-3">Attempts</th><th class="p-3"></th></tr>
                </thead>
                <tbody>
                    <tr v-for="post in posts.data" :key="post.id" class="border-t align-top">
                        <td class="p-3 max-w-xl">
                            <div class="whitespace-pre-wrap line-clamp-3">{{ post.text }}</div>
                            <div v-if="post.media_type" class="mt-1 text-xs text-muted-foreground">{{ post.media_type }} attached</div>
                            <div v-if="post.error_message" class="mt-2 text-xs text-destructive">{{ post.error_message }}</div>
                        </td>
                        <td class="p-3"><span class="capitalize font-medium" :class="statusClass(post.status)">{{ post.status }}</span></td>
                        <td class="p-3 text-xs">
                            <div v-if="post.scheduled_at">Scheduled: {{ new Date(post.scheduled_at).toLocaleString() }}</div>
                            <div v-if="post.published_at">Published: {{ new Date(post.published_at).toLocaleString() }}</div>
                            <a v-if="post.x_post_url" :href="post.x_post_url" target="_blank" rel="noopener" class="text-primary underline">View on X</a>
                        </td>
                        <td class="p-3">{{ post.attempts }}</td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <Button v-if="post.status !== 'published' && post.status !== 'publishing'" size="sm" variant="outline" @click="publishNow(post.id)">Post Now</Button>
                                <Button v-if="['draft','scheduled','failed'].includes(post.status)" size="sm" variant="outline" as-child><Link :href="`/admin/social-posts/${post.id}/edit`">Edit</Link></Button>
                                <Button v-if="post.status !== 'publishing'" size="sm" variant="destructive" @click="removePost(post.id)">Delete</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!posts.data.length"><td colspan="5" class="p-10 text-center text-muted-foreground">No X posts yet.</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="posts.links?.length > 3" class="flex flex-wrap gap-1">
            <Button v-for="link in posts.links" :key="link.label" size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" @click="link.url && router.get(link.url)" v-html="link.label" />
        </div>
    </div>
</template>
