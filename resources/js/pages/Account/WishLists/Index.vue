<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, Heart, LockKeyhole, Plus, Share2 } from '@lucide/vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';




interface WishListSummary {
    id: number;
    name: string;
    description: string | null;
    visibility: 'private' | 'unlisted';
    is_default: boolean;
    items_count: number;
    share_url: string | null;
    last_activity_at: string | null;
}

defineProps<{ lists: WishListSummary[]; default_list_id: number }>();

const form = useForm({
    name: '',
    description: '',
    visibility: 'private',
});

function submit(): void {
    form.post('/account/wish-lists', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Wish Lists" />
    <AccountPageLayout>
        <template #title>Wish Lists</template>
        <template #description>Organize assets into private lists or share a read-only collection with a private link.</template>

        <div class="space-y-8">
            <section class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900 sm:p-7">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-stone-100 dark:bg-stone-800">
                        <Plus class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Create a wish list</h2>
                        <p class="text-sm text-stone-500">Use separate lists for projects, ideas, or future purchases.</p>
                    </div>
                </div>

                <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label for="wish-list-name" class="text-sm font-medium">List name</label>
                        <input id="wish-list-name" v-model="form.name" required maxlength="80"
                            class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-background px-3 dark:border-stone-700" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="wish-list-visibility" class="text-sm font-medium">Visibility</label>
                        <select id="wish-list-visibility" v-model="form.visibility"
                            class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-background px-3 dark:border-stone-700">
                            <option value="private">Private</option>
                            <option value="unlisted">Anyone with the link</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="wish-list-description" class="text-sm font-medium">Description <span class="text-stone-400">(optional)</span></label>
                        <textarea id="wish-list-description" v-model="form.description" maxlength="1000" rows="3"
                            class="mt-2 w-full rounded-xl border border-stone-300 bg-background px-3 py-2 dark:border-stone-700" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex min-h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white disabled:opacity-50">
                            Create wish list
                        </button>
                    </div>
                </form>
            </section>

            <section>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Your lists</h2>
                        <p class="mt-1 text-sm text-stone-500">{{ lists.length }} {{ lists.length === 1 ? 'list' : 'lists' }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link v-for="list in lists" :key="list.id" :href="`/account/wish-lists/${list.id}`"
                        class="group rounded-3xl border border-stone-200 bg-white p-5 transition hover:-translate-y-1 hover:border-stone-300 hover:shadow-lg dark:border-stone-800 dark:bg-stone-900 dark:hover:border-stone-700">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-stone-100 text-[var(--brand-accent)] dark:bg-stone-800">
                                <Heart class="h-5 w-5" :class="{ 'fill-current': list.is_default }" aria-hidden="true" />
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium dark:bg-stone-800">
                                <Share2 v-if="list.visibility === 'unlisted'" class="h-3.5 w-3.5" aria-hidden="true" />
                                <LockKeyhole v-else class="h-3.5 w-3.5" aria-hidden="true" />
                                {{ list.visibility === 'unlisted' ? 'Shareable' : 'Private' }}
                            </span>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold group-hover:text-[var(--brand-accent)]">{{ list.name }}</h3>
                        <p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-stone-500">
                            {{ list.description || (list.is_default ? 'Your default saved assets.' : 'No description yet.') }}
                        </p>
                        <div class="mt-5 flex items-center justify-between border-t border-stone-100 pt-4 text-sm dark:border-stone-800">
                            <span>{{ list.items_count }} {{ list.items_count === 1 ? 'asset' : 'assets' }}</span>
                            <span class="inline-flex items-center gap-1 font-medium text-[var(--brand-accent)]"><Eye class="h-4 w-4" /> Open</span>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </AccountPageLayout>
</template>
