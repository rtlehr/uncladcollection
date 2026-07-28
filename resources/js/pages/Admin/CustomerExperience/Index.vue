<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, BellRing, FileArchive, HeartPulse, KeyRound } from '@lucide/vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{ health: Record<string, number | boolean> }>();
const run = (dryRun: boolean) => router.post('/admin/customer-experience/maintain', { dry_run: dryRun }, { preserveScroll: true });
</script>

<template>
    <Head title="Customer Experience Operations" />
    <div class="space-y-8 p-6">
        <header><div class="flex items-center gap-3"><HeartPulse class="h-7 w-7" /><h1 class="text-3xl font-semibold">Customer Experience Operations</h1></div><p class="mt-2 max-w-3xl text-muted-foreground">Operational health for licenses, notification watches, and temporary download packages.</p></header>
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-5"><KeyRound class="h-5 w-5" /><p class="mt-4 text-sm text-muted-foreground">Active but expired licenses</p><p class="mt-1 text-3xl font-semibold">{{ health.active_but_expired_licenses }}</p></div>
            <div class="rounded-xl border bg-card p-5"><BellRing class="h-5 w-5" /><p class="mt-4 text-sm text-muted-foreground">Old watch events</p><p class="mt-1 text-3xl font-semibold">{{ health.old_notification_watch_events }}</p></div>
            <div class="rounded-xl border bg-card p-5"><FileArchive class="h-5 w-5" /><p class="mt-4 text-sm text-muted-foreground">Stale download packages</p><p class="mt-1 text-3xl font-semibold">{{ health.stale_download_packages }}</p></div>
            <div class="rounded-xl border bg-card p-5"><AlertTriangle class="h-5 w-5" /><p class="mt-4 text-sm text-muted-foreground">Notification storage ready</p><p class="mt-1 text-xl font-semibold">{{ health.notifications_table_ready ? 'Yes' : 'No' }}</p></div>
        </section>
        <ShowSection title="Safe maintenance" description="Removes notification-watch history beyond retention and ZIP packages older than 24 hours. It does not alter orders, licenses, downloads, or customer notifications.">
            <div class="flex flex-wrap gap-3"><Button variant="outline" @click="run(true)">Preview cleanup</Button><Button @click="run(false)">Run cleanup</Button></div>
        </ShowSection>
    </div>
</template>
