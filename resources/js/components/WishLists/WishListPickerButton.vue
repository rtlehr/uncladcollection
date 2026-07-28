<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ListPlus } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{ assetId: number; assetTitle: string }>();
const page = usePage();
const open = ref(false);
const processing = ref<number | null>(null);
const lists = computed(() => ((page.props.wish_lists as any[]) ?? []).filter((list) => !list.is_default));

function save(listId: number): void {
    if (processing.value) return;
    processing.value = listId;
    router.post(`/account/wish-lists/${listId}/assets/${props.assetId}`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { open.value = false; },
        onFinish: () => { processing.value = null; },
    });
}
</script>

<template>
    <div v-if="lists.length" class="relative">
        <button type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-black/45 text-white shadow-lg backdrop-blur transition hover:scale-105 hover:bg-black/75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
            :aria-label="`Save ${assetTitle} to another wish list`"
            :aria-expanded="open"
            @click="open = !open">
            <ListPlus class="h-5 w-5" aria-hidden="true" />
        </button>
        <div v-if="open" class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-stone-200 bg-white p-2 text-stone-950 shadow-xl dark:border-stone-700 dark:bg-stone-900 dark:text-white">
            <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-stone-500">Save to wish list</p>
            <button v-for="list in lists" :key="list.id" type="button"
                class="flex min-h-10 w-full items-center rounded-lg px-2 text-left text-sm hover:bg-stone-100 disabled:opacity-50 dark:hover:bg-stone-800"
                :disabled="processing !== null"
                @click="save(list.id)">
                {{ processing === list.id ? 'Saving…' : list.name }}
            </button>
            <a href="/account/wish-lists" class="mt-1 flex min-h-10 items-center rounded-lg border-t px-2 text-sm font-medium text-[var(--brand-accent)]">Manage wish lists</a>
        </div>
    </div>
</template>
