<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Ban, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface Item {
    id: number;
    keyword: string;
    is_active: boolean;
    notes?: string | null;
    created_by?: string | null;
    created_at?: string | null;
}

const props = defineProps<{ items: Item[]; filters: { search?: string | null } }>();
const search = ref(props.filters.search ?? '');
const selected = ref<Item | null>(null);
const deleteOpen = ref(false);

const singleForm = useForm({ keyword: '', notes: '' });
const bulkForm = useForm({ keywords: '' });

function addSingle(): void {
    singleForm.post('/admin/ai-keyword-exclusions', {
        preserveScroll: true,
        onSuccess: () => singleForm.reset(),
    });
}

function addBulk(): void {
    bulkForm.post('/admin/ai-keyword-exclusions/bulk', {
        preserveScroll: true,
        onSuccess: () => bulkForm.reset(),
    });
}

function runSearch(): void {
    router.get('/admin/ai-keyword-exclusions', { search: search.value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function toggle(item: Item): void {
    router.patch(`/admin/ai-keyword-exclusions/${item.id}`, { is_active: !item.is_active }, { preserveScroll: true });
}

function requestDelete(item: Item): void {
    selected.value = item;
    deleteOpen.value = true;
}

function confirmDelete(): void {
    if (!selected.value) {
return;
}

    router.delete(`/admin/ai-keyword-exclusions/${selected.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
 deleteOpen.value = false; selected.value = null; 
},
    });
}
</script>

<template>
    <Head title="AI Keyword Exclusions" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="AI Keyword Exclusions"
            description="Prevent selected words and phrases from appearing in new AI keyword suggestions. Matching is exact and case-insensitive."
        />

        <div class="grid gap-6 xl:grid-cols-2">
            <ShowSection title="Add one exclusion" description="Add a word or phrase and optionally record why it is excluded.">
                <form class="space-y-4" @submit.prevent="addSingle">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="keyword">Word or phrase</label>
                        <Input id="keyword" v-model="singleForm.keyword" placeholder="Example: provocative" />
                        <p v-if="singleForm.errors.keyword" class="mt-1 text-sm text-destructive">{{ singleForm.errors.keyword }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="notes">Notes</label>
                        <Textarea id="notes" v-model="singleForm.notes" placeholder="Optional reason or usage note" />
                    </div>
                    <Button type="submit" :disabled="singleForm.processing || !singleForm.keyword.trim()">
                        <Plus class="mr-2 h-4 w-4" /> Add exclusion
                    </Button>
                </form>
            </ShowSection>

            <ShowSection title="Bulk add" description="Paste keywords separated by commas or new lines.">
                <form class="space-y-4" @submit.prevent="addBulk">
                    <Textarea v-model="bulkForm.keywords" rows="7" placeholder="sensual&#10;erotic&#10;adult entertainment" />
                    <p v-if="bulkForm.errors.keywords" class="text-sm text-destructive">{{ bulkForm.errors.keywords }}</p>
                    <Button type="submit" :disabled="bulkForm.processing || !bulkForm.keywords.trim()">
                        <Plus class="mr-2 h-4 w-4" /> Add list
                    </Button>
                </form>
            </ShowSection>
        </div>

        <ShowSection title="Exclusion list" :description="`${items.length} matching exclusion(s)`">
            <form class="mb-4 flex gap-2" @submit.prevent="runSearch">
                <Input v-model="search" placeholder="Search exclusions..." />
                <Button type="submit" variant="outline"><Search class="mr-2 h-4 w-4" /> Search</Button>
            </form>

            <div v-if="items.length" class="divide-y rounded-xl border">
                <div v-for="item in items" :key="item.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <Ban class="h-4 w-4 text-muted-foreground" />
                            <span class="font-medium">{{ item.keyword }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="item.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-muted text-muted-foreground'">
                                {{ item.is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </div>
                        <p v-if="item.notes" class="mt-1 text-sm text-muted-foreground">{{ item.notes }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Exact, case-insensitive match</p>
                    </div>
                    <div class="flex gap-2">
                        <Button type="button" variant="outline" size="sm" @click="toggle(item)">
                            {{ item.is_active ? 'Disable' : 'Enable' }}
                        </Button>
                        <Button type="button" variant="destructive" size="sm" @click="requestDelete(item)">
                            <Trash2 class="mr-2 h-4 w-4" /> Delete
                        </Button>
                    </div>
                </div>
            </div>
            <div v-else class="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                No keyword exclusions found.
            </div>
        </ShowSection>
    </div>

    <ConfirmActionDialog
        :open="deleteOpen"
        title="Delete keyword exclusion?"
        :description="selected ? `The AI may suggest “${selected.keyword}” again.` : ''"
        confirm-label="Delete"
        destructive
        @cancel="deleteOpen = false"
        @confirm="confirmDelete"
    />
</template>
