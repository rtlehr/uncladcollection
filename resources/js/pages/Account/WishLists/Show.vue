<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Copy, LockKeyhole, Share2, Trash2 } from '@lucide/vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';
import PublicPagination from '@/components/Gallery/PublicPagination.vue';
import type { GalleryAsset, GalleryPaginationLink } from '@/types/gallery';

interface WishListSummary {
    id: number;
    name: string;
    description: string | null;
    visibility: 'private' | 'unlisted';
    is_default: boolean;
    items_count: number;
    share_url: string | null;
    notify_price_changes: boolean;
    notify_availability_changes: boolean;
    notify_collection_changes: boolean;
}
interface WishListItem { id: number; note: string | null; added_at: string | null; asset: GalleryAsset }
interface PaginatedItems {
    data: WishListItem[];
    links: GalleryPaginationLink[];
    current_page: number; last_page: number; per_page: number; from: number | null; to: number | null;
    total: number; next_page_url: string | null; prev_page_url: string | null;
}

const props = defineProps<{
    wish_list: WishListSummary;
    items: PaginatedItems;
    lists: { id: number; name: string; is_default: boolean }[];
}>();

const assets = computed(() => props.items.data.map((item) => item.asset));
const editOpen = ref(false);
const copied = ref(false);
const form = useForm({ name: props.wish_list.name, description: props.wish_list.description ?? '' });
const notificationForm = useForm({
    notify_price_changes: props.wish_list.notify_price_changes,
    notify_availability_changes: props.wish_list.notify_availability_changes,
    notify_collection_changes: props.wish_list.notify_collection_changes,
});

function update(): void {
    form.patch(`/account/wish-lists/${props.wish_list.id}`, {
        preserveScroll: true,
        onSuccess: () => { editOpen.value = false; },
    });
}
function updateNotifications(): void {
    notificationForm.patch(`/account/wish-lists/${props.wish_list.id}/notifications`, { preserveScroll: true });
}
function setVisibility(visibility: 'private' | 'unlisted'): void {
    router.patch(`/account/wish-lists/${props.wish_list.id}/sharing`, { visibility }, { preserveScroll: true });
}
async function copyShareLink(): Promise<void> {
    if (!props.wish_list.share_url) return;
    await navigator.clipboard.writeText(props.wish_list.share_url);
    copied.value = true;
    window.setTimeout(() => { copied.value = false; }, 2000);
}
function destroyList(): void {
    if (props.wish_list.is_default || !window.confirm(`Delete "${props.wish_list.name}"? Assets saved only here will no longer be favorited.`)) return;
    router.delete(`/account/wish-lists/${props.wish_list.id}`);
}
</script>

<template>
    <Head :title="wish_list.name" />
    <AccountPageLayout>
        <template #title>{{ wish_list.name }}</template>
        <template #description>{{ wish_list.description || (wish_list.is_default ? 'Your default saved assets.' : 'A collection of assets saved for later.') }}</template>

        <div class="space-y-6">
            <div class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900 sm:flex-row sm:items-center sm:justify-between">
                <Link href="/account/wish-lists" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium">
                    <ArrowLeft class="h-4 w-4" /> All wish lists
                </Link>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="min-h-11 rounded-full border px-4 text-sm font-medium" @click="editOpen = !editOpen">Edit details</button>
                    <button v-if="wish_list.visibility === 'private'" type="button" class="inline-flex min-h-11 items-center gap-2 rounded-full border px-4 text-sm font-medium" @click="setVisibility('unlisted')">
                        <Share2 class="h-4 w-4" /> Enable share link
                    </button>
                    <button v-else type="button" class="inline-flex min-h-11 items-center gap-2 rounded-full border px-4 text-sm font-medium" @click="setVisibility('private')">
                        <LockKeyhole class="h-4 w-4" /> Make private
                    </button>
                    <button v-if="!wish_list.is_default" type="button" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-red-300 px-4 text-sm font-medium text-red-700" @click="destroyList">
                        <Trash2 class="h-4 w-4" /> Delete
                    </button>
                </div>
            </div>

            <form v-if="editOpen" class="grid gap-4 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900" @submit.prevent="update">
                <div><label class="text-sm font-medium" for="edit-list-name">Name</label><input id="edit-list-name" v-model="form.name" class="mt-2 h-11 w-full rounded-xl border bg-background px-3" /></div>
                <div><label class="text-sm font-medium" for="edit-list-description">Description</label><textarea id="edit-list-description" v-model="form.description" rows="3" class="mt-2 w-full rounded-xl border bg-background px-3 py-2" /></div>
                <div><button class="min-h-11 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white" :disabled="form.processing">Save changes</button></div>
            </form>

            <form class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900" @submit.prevent="updateNotifications">
                <div>
                    <h2 class="font-semibold">Wish-list notifications</h2>
                    <p class="mt-1 text-sm text-stone-500">Choose which meaningful changes you want to hear about for this list. Your global notification preferences still control email and in-app delivery.</p>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <label class="flex min-h-11 items-center gap-3 rounded-xl border p-3"><input v-model="notificationForm.notify_price_changes" type="checkbox" /><span class="text-sm font-medium">Price changes</span></label>
                    <label class="flex min-h-11 items-center gap-3 rounded-xl border p-3"><input v-model="notificationForm.notify_availability_changes" type="checkbox" /><span class="text-sm font-medium">Availability changes</span></label>
                    <label class="flex min-h-11 items-center gap-3 rounded-xl border p-3"><input v-model="notificationForm.notify_collection_changes" type="checkbox" /><span class="text-sm font-medium">Collection placement</span></label>
                </div>
                <button type="submit" class="mt-4 min-h-11 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white disabled:opacity-50" :disabled="notificationForm.processing">Save notification settings</button>
            </form>

            <div v-if="wish_list.share_url" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                <p class="font-medium">Anyone with this private link can view the list.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input :value="wish_list.share_url" readonly class="h-11 min-w-0 flex-1 rounded-xl border border-amber-300 bg-white px-3 text-sm text-stone-900" aria-label="Wish list share link" />
                    <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-900 px-4 text-sm font-semibold text-white" @click="copyShareLink">
                        <Copy class="h-4 w-4" /> {{ copied ? 'Copied' : 'Copy link' }}
                    </button>
                </div>
            </div>

            <GalleryGrid v-if="assets.length" :assets="assets" />
            <div v-else class="rounded-3xl border border-dashed border-stone-300 px-6 py-16 text-center dark:border-stone-700">
                <h2 class="text-xl font-semibold">This list is empty</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-stone-500">Use the heart on any marketplace asset to save it to Favorites. Additional list-selection controls are available from your wish-list pages.</p>
                <Link href="/images" class="mt-6 inline-flex min-h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white">Browse Marketplace</Link>
            </div>

            <PublicPagination v-if="items.last_page > 1" :pagination="items" />
        </div>
    </AccountPageLayout>
</template>
