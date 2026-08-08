<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';

import PageHeader from '@/components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    entries: any;
    filters: any;
    audiences: Array<{ value: string; label: string }>;
}>();

const page = usePage<any>();
const search = ref(props.filters.search ?? '');
const audience = ref(props.filters.audience ?? '');
const importInput = ref<HTMLInputElement | null>(null);
const importMode = ref<'merge' | 'replace'>('merge');

const importSummary = computed(() => page.props.flash?.page_help_import_summary ?? null);

const importForm = useForm<{
    file: File | null;
    mode: 'merge' | 'replace';
    confirm_replace: boolean;
}>({
    file: null,
    mode: 'merge',
    confirm_replace: false,
});

function apply(): void {
    router.get(
        '/admin/page-help',
        { search: search.value, audience: audience.value },
        { preserveState: true, replace: true },
    );
}

function chooseImport(mode: 'merge' | 'replace'): void {
    importMode.value = mode;
    importInput.value?.click();
}

function importSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
return;
}

    if (
        importMode.value === 'replace'
        && !window.confirm('Replace all existing Page Help content with this export? Entries missing from the file will be deleted.')
    ) {
        input.value = '';

        return;
    }

    importForm.file = file;
    importForm.mode = importMode.value;
    importForm.confirm_replace = importMode.value === 'replace';
    importForm.post('/admin/page-help/import', {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            importForm.reset();
            input.value = '';
        },
    });
}
</script>

<template>
    <Head title="Page Help" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Page Help"
            description="Manage contextual documentation by page key and audience"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/admin/page-help/coverage">Coverage</Link>
                </Button>
                <Button variant="outline" as-child>
                    <a href="/admin/page-help/export">
                        <Download class="mr-2 h-4 w-4" aria-hidden="true" />
                        Export Seed
                    </a>
                </Button>
                <Button variant="outline" type="button" @click="chooseImport('merge')">
                    <Upload class="mr-2 h-4 w-4" aria-hidden="true" />
                    Import Seed
                </Button>
                <Button as-child>
                    <Link href="/admin/page-help/create">Add Help</Link>
                </Button>
            </template>
        </PageHeader>

        <input
            ref="importInput"
            type="file"
            accept="application/json,.json"
            class="sr-only"
            @change="importSelected"
        />

        <div
            v-if="importSummary"
            class="rounded-lg border bg-card p-4 text-sm"
            role="status"
        >
            <div class="font-medium">Last import</div>
            <div class="mt-1 text-muted-foreground">
                {{ importSummary.created }} created,
                {{ importSummary.updated }} updated,
                {{ importSummary.unchanged }} unchanged,
                {{ importSummary.deleted }} removed.
            </div>
            <div v-if="importSummary.missing_roles?.length" class="mt-2 text-amber-700 dark:text-amber-300">
                Missing roles: {{ importSummary.missing_roles.join(', ') }}
            </div>
            <div v-if="importSummary.missing_permissions?.length" class="mt-1 text-amber-700 dark:text-amber-300">
                Missing permissions: {{ importSummary.missing_permissions.join(', ') }}
            </div>
        </div>

        <div v-if="importForm.errors.file" class="rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive" role="alert">
            {{ importForm.errors.file }}
        </div>

        <div class="flex flex-wrap gap-3 rounded-lg border bg-card p-4">
            <input
                v-model="search"
                class="h-10 min-w-64 rounded-md border bg-background px-3"
                placeholder="Search key or title"
                @keyup.enter="apply"
            />
            <select v-model="audience" class="h-10 rounded-md border bg-background px-3">
                <option value="">All audiences</option>
                <option v-for="item in audiences" :key="item.value" :value="item.value">
                    {{ item.label }}
                </option>
            </select>
            <Button @click="apply">Filter</Button>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Page key</th>
                        <th class="p-3">Title</th>
                        <th class="p-3">Audience</th>
                        <th class="p-3">State</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in entries.data" :key="entry.id" class="border-t">
                        <td class="p-3 font-mono text-xs">{{ entry.page_key }}</td>
                        <td class="p-3">
                            <div class="font-medium">{{ entry.title }}</div>
                            <div class="text-muted-foreground">{{ entry.summary }}</div>
                        </td>
                        <td class="p-3 capitalize">{{ entry.audience }}</td>
                        <td class="p-3">
                            {{ entry.is_published ? 'Published' : 'Draft' }}
                            <span v-if="!entry.is_active"> · Inactive</span>
                        </td>
                        <td class="p-3 text-right">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/admin/page-help/${entry.id}/edit`">Edit</Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="!entries.data.length">
                        <td colspan="5" class="p-8 text-center text-muted-foreground">
                            No Page Help entries found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between rounded-lg border bg-card p-4 text-sm">
            <div>
                <div class="font-medium">Restore options</div>
                <p class="text-muted-foreground">Merge is safest. Replace deletes entries that are not in the selected export.</p>
            </div>
            <Button variant="destructive" type="button" :disabled="importForm.processing" @click="chooseImport('replace')">
                Import and Replace
            </Button>
        </div>
    </div>
</template>
