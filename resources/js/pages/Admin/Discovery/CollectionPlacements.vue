<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import PageHeader from '@/components/Shared/PageHeader.vue';
import StatusBadge from '@/components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

interface Option { value: string; label: string }
interface CollectionOption { id: number; name: string; slug: string }
interface Placement {
    id: number;
    collection_id: number;
    collection: CollectionOption;
    placement: string;
    content_type: string;
    audience: string;
    eyebrow: string | null;
    heading: string | null;
    description: string | null;
    call_to_action: string | null;
    sort_order: number;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    status: string;
}

const props = defineProps<{
    placements: Placement[];
    collections: CollectionOption[];
    options: { placements: Option[]; contentTypes: Option[]; audiences: Option[] };
}>();

const editing = ref<Placement | null>(null);
const form = useForm({
    collection_id: props.collections[0]?.id ?? null as number | null,
    placement: 'homepage_primary',
    content_type: 'featured',
    audience: 'all',
    eyebrow: '',
    heading: '',
    description: '',
    call_to_action: 'Explore collection',
    sort_order: 0,
    starts_at: '',
    ends_at: '',
    is_active: true,
});

const submitLabel = computed(() => editing.value ? 'Update placement' : 'Add placement');

function edit(item: Placement): void {
    editing.value = item;
    form.collection_id = item.collection_id;
    form.placement = item.placement;
    form.content_type = item.content_type;
    form.audience = item.audience;
    form.eyebrow = item.eyebrow ?? '';
    form.heading = item.heading ?? '';
    form.description = item.description ?? '';
    form.call_to_action = item.call_to_action ?? '';
    form.sort_order = item.sort_order;
    form.starts_at = item.starts_at ?? '';
    form.ends_at = item.ends_at ?? '';
    form.is_active = item.is_active;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.collection_id = props.collections[0]?.id ?? null;
    form.placement = 'homepage_primary';
    form.content_type = 'featured';
    form.audience = 'all';
    form.call_to_action = 'Explore collection';
    form.is_active = true;
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: reset };
    if (editing.value) {
        form.put(`/admin/discovery/collections/${editing.value.id}`, options);
    } else {
        form.post('/admin/discovery/collections', options);
    }
}

function remove(item: Placement): void {
    if (!confirm(`Remove the ${item.collection.name} placement?`)) return;
    router.delete(`/admin/discovery/collections/${item.id}`, { preserveScroll: true });
}

</script>

<template>
    <Head title="Featured & Seasonal Collections" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Featured & Seasonal Collections"
            description="Schedule curated collection placements for the public homepage and target them by audience."
        />

        <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ editing ? 'Edit placement' : 'New placement' }}</h2>
                    <p class="text-sm text-muted-foreground">Blank promotional text falls back to the collection details.</p>
                </div>
                <Button v-if="editing" type="button" variant="outline" @click="reset">Cancel edit</Button>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="grid gap-2 text-sm font-medium">
                    Collection
                    <select v-model="form.collection_id" class="h-10 rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option>
                    </select>
                    <span v-if="form.errors.collection_id" class="text-xs text-destructive">{{ form.errors.collection_id }}</span>
                </label>

                <label class="grid gap-2 text-sm font-medium">
                    Homepage position
                    <select v-model="form.placement" class="h-10 rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="option in options.placements" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium">
                    Content type
                    <select v-model="form.content_type" class="h-10 rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="option in options.contentTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium">
                    Audience
                    <select v-model="form.audience" class="h-10 rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="option in options.audiences" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium">Eyebrow<Input v-model="form.eyebrow" placeholder="Seasonal collection" /></label>
                <label class="grid gap-2 text-sm font-medium">Heading<Input v-model="form.heading" placeholder="Use collection name" /></label>
                <label class="grid gap-2 text-sm font-medium">Button label<Input v-model="form.call_to_action" /></label>
                <label class="grid gap-2 text-sm font-medium">Sort order<Input v-model="form.sort_order" type="number" min="0" /></label>

                <label class="grid gap-2 text-sm font-medium">Starts at<Input v-model="form.starts_at" type="datetime-local" /></label>
                <label class="grid gap-2 text-sm font-medium">Ends at<Input v-model="form.ends_at" type="datetime-local" /></label>
                <label class="md:col-span-2 grid gap-2 text-sm font-medium">
                    Promotional description
                    <textarea v-model="form.description" rows="3" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                </label>
            </div>

            <label class="mt-5 flex items-center gap-3 rounded-lg border bg-muted/20 p-4">
                <Checkbox :model-value="form.is_active" @update:model-value="form.is_active = $event === true" />
                <span><span class="font-medium">Active</span><span class="ml-2 text-sm text-muted-foreground">The schedule still controls when it appears.</span></span>
            </label>

            <div class="mt-5 flex justify-end gap-3">
                <Button type="submit" :disabled="form.processing || !form.collection_id">{{ submitLabel }}</Button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-6 py-4"><h2 class="font-semibold">Scheduled placements</h2></div>
            <div v-if="placements.length" class="divide-y">
                <article v-for="item in placements" :key="item.id" class="grid gap-4 p-5 lg:grid-cols-[1.2fr_.8fr_.8fr_.7fr_auto] lg:items-center">
                    <div>
                        <div class="font-semibold">{{ item.heading || item.collection.name }}</div>
                        <div class="mt-1 text-sm text-muted-foreground">{{ item.collection.name }} · {{ item.content_type }}</div>
                    </div>
                    <div class="text-sm"><div class="font-medium">{{ item.placement.replace('_', ' ') }}</div><div class="text-muted-foreground">{{ item.audience }}</div></div>
                    <div class="text-sm text-muted-foreground"><div>{{ item.starts_at || 'Starts immediately' }}</div><div>{{ item.ends_at || 'No end date' }}</div></div>
                    <StatusBadge :status="item.status" />
                    <div class="flex justify-end gap-2"><Button size="sm" variant="outline" @click="edit(item)">Edit</Button><Button size="sm" variant="destructive" @click="remove(item)">Delete</Button></div>
                </article>
            </div>
            <div v-else class="p-8 text-center text-sm text-muted-foreground">No scheduled collection placements yet.</div>
        </div>
    </div>
</template>
