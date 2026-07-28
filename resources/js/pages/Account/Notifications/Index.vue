<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, Settings } from '@lucide/vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';

interface NotificationItem {
    id: string;
    title: string;
    message: string;
    action_label: string | null;
    read_at: string | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface NotificationPaginator {
    data: NotificationItem[];
    links?: PaginationLink[];
}

defineProps<{
    notifications: NotificationPaginator;
    filter: string;
    unread_count: number;
}>();

function markAll(): void {
    router.patch('/account/notifications/read-all', {}, { preserveScroll: true });
}

function openNotification(item: NotificationItem): void {
    router.patch(`/account/notifications/${item.id}/read`);
}
</script>

<template>
    <Head title="Notifications" />

    <AccountPageLayout>
        <template #title>Notifications</template>
        <template #description>Review account, order, license, and download updates.</template>

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2">
                    <Link
                        href="/account/notifications"
                        class="rounded-full border px-4 py-2 text-sm"
                        :class="filter !== 'unread' ? 'bg-[var(--brand-primary)] text-white' : ''"
                    >
                        All
                    </Link>
                    <Link
                        href="/account/notifications?filter=unread"
                        class="rounded-full border px-4 py-2 text-sm"
                        :class="filter === 'unread' ? 'bg-[var(--brand-primary)] text-white' : ''"
                    >
                        Unread ({{ unread_count }})
                    </Link>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm"
                        @click="markAll"
                    >
                        <CheckCheck class="h-4 w-4" aria-hidden="true" />
                        Mark all read
                    </button>
                    <Link
                        href="/account/notification-preferences"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm"
                    >
                        <Settings class="h-4 w-4" aria-hidden="true" />
                        Preferences
                    </Link>
                </div>
            </div>

            <div v-if="notifications.data.length" class="space-y-3">
                <button
                    v-for="item in notifications.data"
                    :key="item.id"
                    type="button"
                    class="block w-full rounded-2xl border p-5 text-left transition hover:bg-stone-50 dark:hover:bg-stone-900"
                    :class="!item.read_at
                        ? 'border-[var(--brand-accent)]/50 bg-[var(--brand-accent)]/5'
                        : 'border-stone-200 dark:border-stone-800'"
                    @click="openNotification(item)"
                >
                    <span class="flex gap-4">
                        <span class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                            <Bell class="h-4 w-4" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center justify-between gap-2">
                                <strong>{{ item.title }}</strong>
                                <span class="text-xs text-stone-500">{{ item.created_at }}</span>
                            </span>
                            <span class="mt-1 block text-sm leading-6 text-stone-600 dark:text-stone-400">
                                {{ item.message }}
                            </span>
                            <span
                                v-if="item.action_label"
                                class="mt-2 block text-sm font-medium text-[var(--brand-accent)]"
                            >
                                {{ item.action_label }}
                            </span>
                        </span>
                    </span>
                </button>
            </div>

            <div v-else class="rounded-2xl border border-dashed p-12 text-center text-stone-500">
                You have no notifications in this view.
            </div>

            <div v-if="notifications.links?.length" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-lg border px-3 py-2 text-sm"
                    :class="{
                        'pointer-events-none opacity-40': !link.url,
                        'bg-[var(--brand-primary)] text-white': link.active,
                    }"
                >
                    <span v-html="link.label" />
                </Link>
            </div>
        </div>
    </AccountPageLayout>
</template>
