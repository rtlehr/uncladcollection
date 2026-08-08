<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Activity, CircleAlert, MailCheck, RotateCcw } from '@lucide/vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Log = {
    id: number;
    template_key: string;
    recipient_email: string;
    subject: string;
    status: string;
    failure_message: string | null;
    retry_count: number;
    created_at: string;
    sent_at: string | null;
    failed_at: string | null;
};

const props = defineProps<{
    logs: { data: Log[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: { search?: string; status?: string; template?: string };
    templateOptions: string[];
    summary: { sent: number; failed: number; queued: number };
}>();

const filterForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    template: props.filters.template ?? '',
});

const applyFilters = () => {
    router.get('/admin/communications/delivery-activity', filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const retry = (log: Log) => {
    router.post(`/admin/communications/delivery-activity/${log.id}/retry`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Email Delivery Activity" />
    <div class="space-y-6 p-6">
        <PageHeader title="Email Delivery Activity" description="Review delivery history, investigate failures, and safely retry supported messages." />

        <div class="flex flex-wrap gap-2">
            <Link href="/admin/communications/email-templates"><Button variant="outline">Email templates</Button></Link>
            <Link href="/admin/communications/settings"><Button variant="outline">Communication settings</Button></Link>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card><CardHeader><CardTitle class="flex items-center gap-2 text-base"><MailCheck class="h-4 w-4" /> Sent</CardTitle></CardHeader><CardContent class="text-3xl font-semibold">{{ summary.sent }}</CardContent></Card>
            <Card><CardHeader><CardTitle class="flex items-center gap-2 text-base"><CircleAlert class="h-4 w-4" /> Failed</CardTitle></CardHeader><CardContent class="text-3xl font-semibold">{{ summary.failed }}</CardContent></Card>
            <Card><CardHeader><CardTitle class="flex items-center gap-2 text-base"><Activity class="h-4 w-4" /> Queued</CardTitle></CardHeader><CardContent class="text-3xl font-semibold">{{ summary.queued }}</CardContent></Card>
        </div>

        <Card>
            <CardContent class="grid gap-3 pt-6 md:grid-cols-4">
                <Input v-model="filterForm.search" placeholder="Recipient, subject, or template" @keyup.enter="applyFilters" />
                <select v-model="filterForm.status" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="">All statuses</option><option value="sent">Sent</option><option value="failed">Failed</option><option value="queued">Queued</option><option value="pending">Pending</option>
                </select>
                <select v-model="filterForm.template" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="">All templates</option><option v-for="key in templateOptions" :key="key" :value="key">{{ key }}</option>
                </select>
                <Button @click="applyFilters">Apply filters</Button>
            </CardContent>
        </Card>

        <div class="space-y-3">
            <Card v-for="log in logs.data" :key="log.id">
                <CardContent class="flex flex-col gap-4 pt-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge :variant="log.status === 'failed' ? 'destructive' : log.status === 'sent' ? 'default' : 'secondary'">{{ log.status }}</Badge>
                            <span class="font-mono text-xs text-muted-foreground">{{ log.template_key }}</span>
                        </div>
                        <p class="font-medium">{{ log.subject }}</p>
                        <p class="text-sm text-muted-foreground">{{ log.recipient_email }} · {{ new Date(log.created_at).toLocaleString() }}</p>
                        <p v-if="log.failure_message" class="max-w-4xl text-sm text-destructive">{{ log.failure_message }}</p>
                    </div>
                    <Button v-if="log.status === 'failed'" size="sm" variant="outline" class="gap-2" @click="retry(log)">
                        <RotateCcw class="h-4 w-4" /> Retry
                    </Button>
                </CardContent>
            </Card>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link v-for="link in logs.links" :key="link.label" :href="link.url ?? '#'" preserve-scroll>
                <Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url"><span v-html="link.label" /></Button>
            </Link>
        </div>
    </div>
</template>
