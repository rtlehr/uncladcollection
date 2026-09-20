<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import PageHeader from '@/components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { appConfirm } from '@/lib/appDialog';

type Campaign = {
    id: number | null;
    name: string;
    subject: string;
    updated_at?: string | null;
};

type PromptAddition = {
    id: number;
    name: string;
    category?: string | null;
    prompt_text: string;
    is_active: boolean;
    sort_order: number;
    updated_at?: string | null;
};

const props = defineProps<{
    suggestions: any;
    campaigns: Campaign[];
    promptAdditions: PromptAddition[];
    filters: { campaign?: string; status?: string };
}>();

const form = useForm({
    subject: '',
    campaign: '',
    count: 5,
    prompt_addition_ids: [] as number[],
});

const similarCounts = reactive<Record<number, number>>({});
const campaignTopicLoaded = ref(false);
const showLibrary = ref(false);
const editingAdditionId = ref<number | null>(null);

const activePromptAdditions = computed(() => props.promptAdditions.filter((item) => item.is_active));

const createAdditionForm = useForm({
    name: '',
    category: '',
    prompt_text: '',
    is_active: true,
    sort_order: 0,
});

const editAdditionForm = useForm({
    name: '',
    category: '',
    prompt_text: '',
    is_active: true,
    sort_order: 0,
});

watch(() => form.campaign, (campaignName) => {
    const normalized = campaignName.trim().toLocaleLowerCase();
    const campaign = props.campaigns.find((item) => item.name.trim().toLocaleLowerCase() === normalized);

    if (!campaign) {
        campaignTopicLoaded.value = false;
        return;
    }

    form.subject = campaign.subject ?? '';
    campaignTopicLoaded.value = true;
});

function generate() {
    form.post('/admin/social-posts/ai/generate', {
        preserveScroll: true,
    });
}

function togglePromptAddition(id: number, checked: boolean) {
    if (checked) {
        if (!form.prompt_addition_ids.includes(id)) {
            form.prompt_addition_ids.push(id);
        }
        return;
    }

    form.prompt_addition_ids = form.prompt_addition_ids.filter((item) => item !== id);
}

function createPromptAddition() {
    createAdditionForm.post('/admin/social-posts/ai/prompt-additions', {
        preserveScroll: true,
        onSuccess: () => createAdditionForm.reset(),
    });
}

function beginEditPromptAddition(item: PromptAddition) {
    editingAdditionId.value = item.id;
    editAdditionForm.name = item.name;
    editAdditionForm.category = item.category ?? '';
    editAdditionForm.prompt_text = item.prompt_text;
    editAdditionForm.is_active = item.is_active;
    editAdditionForm.sort_order = item.sort_order ?? 0;
    editAdditionForm.clearErrors();
}

function cancelEditPromptAddition() {
    editingAdditionId.value = null;
    editAdditionForm.reset();
    editAdditionForm.clearErrors();
}

function updatePromptAddition(id: number) {
    editAdditionForm.put(`/admin/social-posts/ai/prompt-additions/${id}`, {
        preserveScroll: true,
        onSuccess: () => cancelEditPromptAddition(),
    });
}

async function deletePromptAddition(item: PromptAddition) {
    const confirmed = await appConfirm(
        `Delete “${item.name}” from the Prompt Additions Library? Historical AI suggestions will keep the saved snapshot that was used when they were generated.`,
        {
            title: 'Delete prompt addition?',
            confirmLabel: 'Delete',
            destructive: true,
        },
    );

    if (!confirmed) {
        return;
    }

    router.delete(`/admin/social-posts/ai/prompt-additions/${item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            form.prompt_addition_ids = form.prompt_addition_ids.filter((id) => id !== item.id);
            if (editingAdditionId.value === item.id) {
                cancelEditPromptAddition();
            }
        },
    });
}

function accept(id:number) {
    router.post(`/admin/social-posts/ai/${id}/accept`, {}, {preserveScroll:true});
}

function reject(id:number) {
    router.post(`/admin/social-posts/ai/${id}/reject`, {}, {preserveScroll:true});
}

function regenerate(id:number) {
    router.post(`/admin/social-posts/ai/${id}/regenerate`, {}, {preserveScroll:true});
}

function generateMore(id:number) {
    router.post(`/admin/social-posts/ai/${id}/generate-more`, {
        count: similarCounts[id] ?? 3,
    }, {preserveScroll:true});
}

function publishNow(id:number) {
    router.post(`/admin/social-posts/ai/${id}/publish-now`, {}, {preserveScroll:true});
}

function applyFilters(campaign = props.filters.campaign ?? '', status = props.filters.status ?? '') {
    router.get('/admin/social-posts/ai', {
        ...(campaign ? {campaign} : {}),
        ...(status ? {status} : {}),
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    router.get('/admin/social-posts/ai', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function hashtags(item:any) {
    return (item.hashtags ?? []).map((tag:string) => `#${String(tag).replace(/^#+/, '')}`).join(' ');
}

function promptAdditionNames(item:any) {
    return (item.prompt_additions ?? []).map((addition:any) => addition.name).filter(Boolean);
}

function statusClass(status:string) {
    return {
        pending:'text-amber-600',
        accepted:'text-emerald-600',
        rejected:'text-muted-foreground',
    }[status] || 'text-muted-foreground';
}
</script>

<template>
    <Head title="AI X Post Generator" />
    <div class="space-y-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <PageHeader
                title="AI X Post Generator"
                description="Generate campaign-ready post ideas, review them, regenerate rejects, and create more from the ideas you like."
            />
            <Button variant="outline" as-child><Link href="/admin/social-posts">Back to X Posts</Link></Button>
        </div>

        <form class="space-y-5 rounded-xl border bg-card p-5" @submit.prevent="generate">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Subject / topic</label>
                    <textarea
                        v-model="form.subject"
                        rows="4"
                        class="w-full rounded-md border bg-background px-3 py-2"
                        placeholder="Example: Why nudist resorts can be welcoming places for first-time visitors"
                    />
                    <p class="text-xs text-muted-foreground">This is the goal for this batch. Reusable business context, tone and special instructions can be selected separately below.</p>
                    <p v-if="form.errors.subject" class="text-xs text-destructive">{{ form.errors.subject }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Campaign / group <span class="font-normal text-muted-foreground">(optional)</span></label>
                    <input
                        v-model="form.campaign"
                        list="social-campaigns"
                        maxlength="150"
                        class="w-full rounded-md border bg-background px-3 py-2"
                        placeholder="Example: Body Acceptance Week"
                    />
                    <datalist id="social-campaigns">
                        <option v-for="campaign in campaigns" :key="campaign.name" :value="campaign.name" />
                    </datalist>
                    <p class="text-xs text-muted-foreground">Use the same name to keep related AI batches together. Existing campaigns restore their last saved Subject / Topic.</p>
                    <p v-if="campaignTopicLoaded" class="text-xs text-emerald-600">Saved Subject / Topic loaded. Generate again after editing it to save the update.</p>
                    <p v-if="form.errors.campaign" class="text-xs text-destructive">{{ form.errors.campaign }}</p>
                </div>
            </div>

            <div class="space-y-3 rounded-lg border bg-muted/20 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">Prompt additions <span class="font-normal text-muted-foreground">(optional)</span></div>
                        <p class="mt-1 text-xs text-muted-foreground">Select reusable context or instructions only when you want them included in this AI request.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="showLibrary = !showLibrary">
                        {{ showLibrary ? 'Hide Library' : 'Manage Library' }}
                    </Button>
                </div>

                <div v-if="activePromptAdditions.length" class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    <label
                        v-for="addition in activePromptAdditions"
                        :key="addition.id"
                        class="flex cursor-pointer gap-3 rounded-md border bg-background p-3"
                    >
                        <input
                            type="checkbox"
                            class="mt-1 h-4 w-4"
                            :checked="form.prompt_addition_ids.includes(addition.id)"
                            @change="togglePromptAddition(addition.id, ($event.target as HTMLInputElement).checked)"
                        />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">{{ addition.name }}</span>
                            <span v-if="addition.category" class="block text-xs text-muted-foreground">{{ addition.category }}</span>
                            <span class="mt-1 block line-clamp-2 text-xs text-muted-foreground">{{ addition.prompt_text }}</span>
                        </span>
                    </label>
                </div>
                <p v-else class="text-sm text-muted-foreground">No active prompt additions yet. Open the library to create one.</p>

                <p v-if="form.prompt_addition_ids.length" class="text-xs font-medium text-primary">
                    {{ form.prompt_addition_ids.length }} prompt addition{{ form.prompt_addition_ids.length === 1 ? '' : 's' }} will be included.
                </p>
                <p v-if="form.errors.prompt_addition_ids" class="text-xs text-destructive">{{ form.errors.prompt_addition_ids }}</p>
            </div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Number of posts</label>
                    <select v-model.number="form.count" class="rounded-md border bg-background px-3 py-2">
                        <option v-for="n in 20" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
                <Button type="submit" :disabled="form.processing || !form.subject.trim()">
                    {{ form.processing ? 'Generating…' : 'Generate Post Ideas' }}
                </Button>
            </div>
            <p v-if="form.errors.count" class="text-xs text-destructive">{{ form.errors.count }}</p>
        </form>

        <section v-if="showLibrary" class="space-y-5 rounded-xl border bg-card p-5">
            <div>
                <h2 class="text-lg font-semibold">Prompt Additions Library</h2>
                <p class="mt-1 text-sm text-muted-foreground">These are reusable building blocks, not hard-coded generator rules. Edit them whenever your business, tone or campaign needs change.</p>
            </div>

            <form class="space-y-3 rounded-lg border p-4" @submit.prevent="createPromptAddition">
                <div class="font-medium">Add prompt addition</div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-medium">Name</label>
                        <input v-model="createAdditionForm.name" class="mt-1 w-full rounded-md border bg-background px-3 py-2" placeholder="Example: About Unclad Collection" />
                        <p v-if="createAdditionForm.errors.name" class="mt-1 text-xs text-destructive">{{ createAdditionForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium">Category <span class="font-normal text-muted-foreground">(optional)</span></label>
                        <input v-model="createAdditionForm.category" class="mt-1 w-full rounded-md border bg-background px-3 py-2" placeholder="Brand context, Tone, Promotion…" />
                        <p v-if="createAdditionForm.errors.category" class="mt-1 text-xs text-destructive">{{ createAdditionForm.errors.category }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium">Prompt text</label>
                    <textarea v-model="createAdditionForm.prompt_text" rows="4" class="mt-1 w-full rounded-md border bg-background px-3 py-2" placeholder="Write the reusable context or instruction exactly as you want it sent to the AI." />
                    <p v-if="createAdditionForm.errors.prompt_text" class="mt-1 text-xs text-destructive">{{ createAdditionForm.errors.prompt_text }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 text-sm"><input v-model="createAdditionForm.is_active" type="checkbox" /> Active</label>
                    <label class="flex items-center gap-2 text-sm">Sort <input v-model.number="createAdditionForm.sort_order" type="number" min="0" max="9999" class="w-24 rounded-md border bg-background px-2 py-1" /></label>
                    <Button type="submit" size="sm" :disabled="createAdditionForm.processing">{{ createAdditionForm.processing ? 'Saving…' : 'Add to Library' }}</Button>
                </div>
            </form>

            <div class="space-y-3">
                <div v-for="addition in promptAdditions" :key="addition.id" class="rounded-lg border p-4">
                    <form v-if="editingAdditionId === addition.id" class="space-y-3" @submit.prevent="updatePromptAddition(addition.id)">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium">Name</label>
                                <input v-model="editAdditionForm.name" class="mt-1 w-full rounded-md border bg-background px-3 py-2" />
                            </div>
                            <div>
                                <label class="text-xs font-medium">Category</label>
                                <input v-model="editAdditionForm.category" class="mt-1 w-full rounded-md border bg-background px-3 py-2" />
                            </div>
                        </div>
                        <textarea v-model="editAdditionForm.prompt_text" rows="5" class="w-full rounded-md border bg-background px-3 py-2" />
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-sm"><input v-model="editAdditionForm.is_active" type="checkbox" /> Active</label>
                            <label class="flex items-center gap-2 text-sm">Sort <input v-model.number="editAdditionForm.sort_order" type="number" min="0" max="9999" class="w-24 rounded-md border bg-background px-2 py-1" /></label>
                            <Button type="submit" size="sm" :disabled="editAdditionForm.processing">Save Changes</Button>
                            <Button type="button" size="sm" variant="outline" @click="cancelEditPromptAddition">Cancel</Button>
                        </div>
                        <div v-if="Object.keys(editAdditionForm.errors).length" class="text-xs text-destructive">
                            {{ Object.values(editAdditionForm.errors).join(' ') }}
                        </div>
                    </form>

                    <div v-else class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ addition.name }}</span>
                                <span v-if="addition.category" class="rounded-full border px-2 py-0.5 text-xs">{{ addition.category }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs" :class="addition.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-muted text-muted-foreground'">
                                    {{ addition.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">{{ addition.prompt_text }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <Button type="button" size="sm" variant="outline" @click="beginEditPromptAddition(addition)">Edit</Button>
                            <Button type="button" size="sm" variant="destructive" @click="deletePromptAddition(addition)">Delete</Button>
                        </div>
                    </div>
                </div>
                <p v-if="!promptAdditions.length" class="text-sm text-muted-foreground">The prompt library is empty.</p>
            </div>
        </section>

        <div class="rounded-xl border bg-card p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="space-y-1">
                    <label class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Campaign</label>
                    <select
                        :value="filters.campaign ?? ''"
                        class="min-w-56 rounded-md border bg-background px-3 py-2 text-sm"
                        @change="applyFilters(($event.target as HTMLSelectElement).value, filters.status ?? '')"
                    >
                        <option value="">All campaigns</option>
                        <option v-for="campaign in campaigns" :key="campaign.name" :value="campaign.name">{{ campaign.name }}</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</label>
                    <select
                        :value="filters.status ?? ''"
                        class="rounded-md border bg-background px-3 py-2 text-sm"
                        @change="applyFilters(filters.campaign ?? '', ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <Button v-if="filters.campaign || filters.status" variant="outline" size="sm" @click="clearFilters">Clear filters</Button>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Review AI Suggestions</h2>
                <span class="text-sm text-muted-foreground">{{ suggestions.total ?? suggestions.data?.length ?? 0 }} suggestions</span>
            </div>

            <div v-for="item in suggestions.data" :key="item.id" class="rounded-xl border bg-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1 space-y-4">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <span class="font-medium capitalize" :class="statusClass(item.status)">{{ item.status }}</span>
                            <span v-if="item.campaign" class="rounded-full border px-2 py-0.5 font-medium">{{ item.campaign }}</span>
                            <span v-if="item.source_suggestion_id" class="text-muted-foreground">Derived from suggestion #{{ item.source_suggestion_id }}</span>
                            <span class="text-muted-foreground">{{ new Date(item.created_at).toLocaleString() }}</span>
                        </div>

                        <div>
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Subject</div>
                            <div class="text-sm">{{ item.subject }}</div>
                        </div>

                        <div v-if="promptAdditionNames(item).length">
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Prompt additions used</div>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="name in promptAdditionNames(item)" :key="name" class="rounded-full border px-2 py-0.5 text-xs">{{ name }}</span>
                            </div>
                        </div>

                        <div>
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Post</div>
                            <div class="whitespace-pre-wrap text-base leading-relaxed">{{ item.text }}</div>
                            <div v-if="hashtags(item)" class="mt-3 text-sm font-medium text-primary">{{ hashtags(item) }}</div>
                        </div>

                        <div v-if="item.image_suggestion" class="rounded-lg border bg-muted/30 p-4">
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Suggested image</div>
                            <div class="text-sm leading-relaxed">{{ item.image_suggestion }}</div>
                            <p class="mt-2 text-xs text-muted-foreground">Accept the post first, then add an image or video from the normal X Post edit screen.</p>
                        </div>

                        <div v-if="item.social_post" class="rounded-lg border p-3 text-sm">
                            <span class="font-medium">X Post draft:</span>
                            <span class="ml-1 capitalize">{{ item.social_post.status }}</span>
                            <span v-if="item.social_post.scheduled_at" class="ml-2 text-muted-foreground">Scheduled {{ new Date(item.social_post.scheduled_at).toLocaleString() }}</span>
                            <a v-if="item.social_post.x_post_url" :href="item.social_post.x_post_url" target="_blank" rel="noopener" class="ml-2 text-primary underline">View on X</a>
                        </div>
                    </div>

                    <div class="flex max-w-sm shrink-0 flex-wrap items-center justify-end gap-2">
                        <template v-if="item.status === 'pending'">
                            <Button size="sm" @click="accept(item.id)">Accept</Button>
                            <Button size="sm" variant="outline" @click="reject(item.id)">Reject</Button>
                        </template>

                        <template v-else-if="item.status === 'rejected'">
                            <Button size="sm" variant="outline" @click="regenerate(item.id)">Regenerate</Button>
                        </template>

                        <template v-else-if="item.status === 'accepted' && item.social_post">
                            <Button v-if="item.social_post.status !== 'published'" size="sm" @click="publishNow(item.id)">Post Now</Button>
                            <Button v-if="['draft','scheduled','failed'].includes(item.social_post.status)" size="sm" variant="outline" as-child>
                                <Link :href="`/admin/social-posts/${item.social_post.id}/edit`">Edit / Schedule</Link>
                            </Button>
                            <div class="flex items-center gap-1 rounded-md border p-1">
                                <select v-model.number="similarCounts[item.id]" class="border-0 bg-background px-1 py-1 text-sm outline-none">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                                <Button size="sm" variant="ghost" @click="generateMore(item.id)">More like this</Button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div v-if="!suggestions.data?.length" class="rounded-xl border p-10 text-center text-muted-foreground">
                No AI post suggestions match the current filters.
            </div>
        </div>

        <div v-if="suggestions.links?.length > 3" class="flex flex-wrap gap-1">
            <Button
                v-for="link in suggestions.links"
                :key="link.label"
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                @click="link.url && router.get(link.url)"
                v-html="link.label"
            />
        </div>
    </div>
</template>
