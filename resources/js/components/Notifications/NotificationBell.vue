<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const count = computed(() => Number((page.props.notifications_summary as any)?.unread_count ?? 0));
</script>

<template>
    <Link href="/account/notifications" class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-stone-300 transition hover:bg-stone-100 dark:border-stone-700 dark:hover:bg-stone-800" :aria-label="count ? `${count} unread notifications` : 'Notifications'">
        <Bell class="h-4 w-4" aria-hidden="true" />
        <span v-if="count" class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-accent)] px-1 text-[10px] font-bold text-white">{{ count > 99 ? '99+' : count }}</span>
    </Link>
</template>
