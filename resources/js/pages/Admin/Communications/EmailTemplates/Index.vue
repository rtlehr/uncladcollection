<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Activity, Mail, Settings2, ShieldCheck } from '@lucide/vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type EmailTemplate = {
    id: number;
    key: string;
    name: string;
    category: string;
    description: string | null;
    subject: string;
    is_active: boolean;
    is_transactional: boolean;
    updated_at: string;
    updated_by?: { id: number; name: string } | null;
};

const props = defineProps<{
    templates: EmailTemplate[];
    filters: { search?: string; category?: string; status?: string };
    categories: string[];
    deliverySummary: { sent: number; failed: number; queued: number };
    defaultTestRecipient: string | null;
}>();

const filterForm = useForm({
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
    status: props.filters.status ?? '',
});

const applyFilters = () => {
    router.get('/admin/communications/email-templates', filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Email Templates" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Email Templates"
            description="Manage customer-facing email content without changing application code. System defaults remain available as a safe fallback."
        />

        <div class="flex flex-wrap gap-2">
            <Link href="/admin/communications/delivery-activity"><Button variant="outline" class="gap-2"><Activity class="h-4 w-4" /> Delivery activity</Button></Link>
            <Link href="/admin/communications/settings"><Button variant="outline" class="gap-2"><Settings2 class="h-4 w-4" /> Communication settings</Button></Link>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card><CardContent class="pt-6"><p class="text-sm text-muted-foreground">Sent</p><p class="text-3xl font-semibold">{{ deliverySummary.sent }}</p></CardContent></Card>
            <Card><CardContent class="pt-6"><p class="text-sm text-muted-foreground">Failed</p><p class="text-3xl font-semibold">{{ deliverySummary.failed }}</p></CardContent></Card>
            <Card><CardContent class="pt-6"><p class="text-sm text-muted-foreground">Queued</p><p class="text-3xl font-semibold">{{ deliverySummary.queued }}</p></CardContent></Card>
        </div>

        <Card>
            <CardContent class="grid gap-3 pt-6 md:grid-cols-4">
                <Input v-model="filterForm.search" placeholder="Search name, key, or subject" @keyup.enter="applyFilters" />
                <select v-model="filterForm.category" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="">All categories</option>
                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                </select>
                <select v-model="filterForm.status" class="h-10 rounded-md border bg-background px-3 text-sm">
                    <option value="">All statuses</option><option value="active">Active</option><option value="disabled">Disabled</option>
                </select>
                <Button @click="applyFilters">Apply filters</Button>
            </CardContent>
        </Card>

        <div v-if="templates.length" class="grid gap-4 xl:grid-cols-2">
            <Card v-for="template in templates" :key="template.id">
                <CardHeader class="space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg border bg-muted/40 p-2"><Mail class="h-5 w-5" /></div>
                            <div><CardTitle class="text-lg">{{ template.name }}</CardTitle><p class="mt-1 font-mono text-xs text-muted-foreground">{{ template.key }}</p></div>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            <Badge :variant="template.is_active ? 'default' : 'secondary'">{{ template.is_active ? 'Active' : 'Disabled' }}</Badge>
                            <Badge v-if="template.is_transactional" variant="outline" class="gap-1"><ShieldCheck class="h-3 w-3" /> Transactional</Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div><p class="text-sm text-muted-foreground">{{ template.description }}</p><p class="mt-3 text-sm"><span class="font-medium">Subject:</span> {{ template.subject }}</p></div>
                    <div class="flex items-center justify-between border-t pt-4">
                        <div class="text-xs text-muted-foreground">{{ template.category }}<span v-if="template.updated_by"> · Updated by {{ template.updated_by.name }}</span></div>
                        <Link :href="`/admin/communications/email-templates/${template.id}/edit`"><Button size="sm">Edit template</Button></Link>
                    </div>
                </CardContent>
            </Card>
        </div>
        <Card v-else><CardContent class="py-12 text-center text-muted-foreground">No email templates match these filters.</CardContent></Card>
    </div>
</template>
