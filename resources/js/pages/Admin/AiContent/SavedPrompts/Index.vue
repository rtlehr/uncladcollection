<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowUpDown, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { appConfirm } from '@/lib/appDialog';

const props = defineProps<{ items: any; filters: { search?: string; sort?: string; direction?: string } }>();
const search = ref(props.filters.search ?? '');

function runSearch() {
    router.get('/admin/ai-content/image-prompts', { search: search.value || undefined, sort: props.filters.sort, direction: props.filters.direction }, { preserveState: true, replace: true });
}

function sortBy(column: string) {
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get('/admin/ai-content/image-prompts', { search: search.value || undefined, sort: column, direction }, { preserveState: true, replace: true });
}

async function remove(item: any) {
    if (await appConfirm(`Delete saved prompt “${item.title}”?`, { title: 'Delete saved prompt?', confirmLabel: 'Delete Prompt', destructive: true })) {
router.delete(`/admin/ai-content/image-prompts/${item.id}`);
}
}
</script>

<template>
    <Head title="Saved AI Image Prompts" />
    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader title="Saved AI Image Prompts" description="Search, sort, edit, refine, and reuse your generated image prompts.">
                <template #actions>
                    <Button as-child><Link href="/admin/ai-content/image-prompts/create"><Plus class="mr-2 h-4 w-4" />Create New Prompt</Link></Button>
                </template>
            </PageHeader>

            <div class="rounded-xl border bg-card">
                <div class="flex flex-col gap-3 border-b p-4 md:flex-row md:items-center">
                    <form class="flex flex-1 gap-2" @submit.prevent="runSearch">
                        <Input v-model="search" placeholder="Search title, description, prompt text, provider, or model..." />
                        <Button variant="outline"><Search class="mr-2 h-4 w-4" />Search</Button>
                    </form>
                    <p class="text-sm text-muted-foreground">{{ items.total }} saved prompt(s)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/30 text-left">
                            <tr>
                                <th class="px-4 py-3"><button class="flex items-center gap-1 font-medium" @click="sortBy('title')">Title <ArrowUpDown class="h-3.5 w-3.5" /></button></th>
                                <th class="px-4 py-3"><button class="flex items-center gap-1 font-medium" @click="sortBy('content_context')">Context <ArrowUpDown class="h-3.5 w-3.5" /></button></th>
                                <th class="px-4 py-3"><button class="flex items-center gap-1 font-medium" @click="sortBy('intended_use')">Use <ArrowUpDown class="h-3.5 w-3.5" /></button></th>
                                <th class="px-4 py-3">Provider / Model</th>
                                <th class="px-4 py-3">Versions</th>
                                <th class="px-4 py-3"><button class="flex items-center gap-1 font-medium" @click="sortBy('updated_at')">Updated <ArrowUpDown class="h-3.5 w-3.5" /></button></th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in items.data" :key="item.id" class="align-top hover:bg-muted/20">
                                <td class="px-4 py-4"><Link class="font-medium hover:underline" :href="`/admin/ai-content/image-prompts/${item.id}/edit`">{{ item.title }}</Link><p class="mt-1 max-w-xl line-clamp-2 text-muted-foreground">{{ item.prompt_text }}</p></td>
                                <td class="px-4 py-4">{{ item.content_context.replaceAll('_', ' ') }}</td>
                                <td class="px-4 py-4">{{ item.intended_use.replaceAll('_', ' ') }}</td>
                                <td class="px-4 py-4"><div>{{ item.provider || '—' }}</div><div class="text-xs text-muted-foreground">{{ item.model || '—' }}</div></td>
                                <td class="px-4 py-4">{{ item.versions_count }}</td>
                                <td class="px-4 py-4">{{ new Date(item.updated_at).toLocaleDateString() }}</td>
                                <td class="px-4 py-4"><div class="flex justify-end gap-2"><Button as-child size="sm" variant="outline"><Link :href="`/admin/ai-content/image-prompts/${item.id}/edit`">Edit</Link></Button><Button size="sm" variant="destructive" @click="remove(item)">Delete</Button></div></td>
                            </tr>
                            <tr v-if="!items.data.length"><td colspan="7" class="px-4 py-12 text-center text-muted-foreground">No saved prompts match your search.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-2 border-t p-4">
                    <Button v-for="link in items.links" :key="link.label" size="sm" variant="outline" :disabled="!link.url" @click="link.url && router.visit(link.url)"><span v-html="link.label" /></Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
