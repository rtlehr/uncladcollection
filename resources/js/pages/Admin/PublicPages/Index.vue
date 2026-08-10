<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Download,
    ExternalLink,
    Pencil,
    Plus,
    Trash2,
    Upload,
} from '@lucide/vue';
import { ref } from 'vue';

import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { appConfirm } from '@/lib/appDialog';

type PublicPageListItem = {
    id: number;
    title: string;
    slug: string;
    page_type: string;
    status: string;
    navigation_locations?: string[] | null;
};

const props = defineProps<{
    pages: {
        data: PublicPageListItem[];
        links?: Array<{
            label: string;
            url: string | null;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function apply(): void {
    router.get(
        '/admin/public-pages',
        {
            search: search.value,
            status: status.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

async function remove(page: PublicPageListItem): Promise<void> {
    if (await appConfirm(`Delete ${page.title}?`, { title: 'Delete public page?', confirmLabel: 'Delete Page', destructive: true })) {
        router.delete(`/admin/public-pages/${page.slug}`);
    }
}

function publicPageHref(page: PublicPageListItem): string {
    return page.page_type === 'support'
        ? '/support'
        : `/${page.slug}`;
}

function importSeed(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('mode', 'merge');

    router.post('/admin/public-pages/import', formData);
}
</script>

<template>
    <Head title="Public Pages" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="Public Pages"
                description="Manage lightweight informational pages for the public site."
            >
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" as-child>
                            <a href="/admin/public-pages/export">
                                <Download class="mr-2 h-4 w-4" />
                                Export Seed
                            </a>
                        </Button>

                        <label
                            class="inline-flex cursor-pointer items-center rounded-md border px-4 py-2 text-sm font-medium"
                        >
                            <Upload class="mr-2 h-4 w-4" />
                            Import Seed

                            <input
                                type="file"
                                accept="application/json,.json"
                                class="sr-only"
                                @change="importSeed"
                            />
                        </label>

                        <Button as-child>
                            <Link href="/admin/public-pages/create">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Page
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div
                class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row"
            >
                <Input
                    v-model="search"
                    placeholder="Search title or slug"
                    @keyup.enter="apply"
                />

                <select
                    v-model="status"
                    class="h-10 rounded-md border bg-background px-3"
                >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>

                <Button variant="outline" @click="apply">
                    Filter
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="p-4">Page</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Navigation</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="page in pages.data"
                            :key="page.id"
                            class="border-t"
                        >
                            <td class="p-4">
                                <div class="font-medium">
                                    {{ page.title }}
                                </div>

                                <div class="text-muted-foreground">
                                    {{ publicPageHref(page) }}
                                </div>
                            </td>

                            <td class="p-4 capitalize">
                                {{ page.page_type }}
                            </td>

                            <td class="p-4">
                                <span
                                    class="rounded-full border px-2.5 py-1 text-xs capitalize"
                                >
                                    {{ page.status }}
                                </span>
                            </td>

                            <td class="p-4">
                                {{
                                    (page.navigation_locations ?? []).length
                                        ? page.navigation_locations?.join(', ')
                                        : 'Hidden'
                                }}
                            </td>

                            <td class="p-4">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        v-if="page.status === 'published'"
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                    >
                                        <a
                                            :href="publicPageHref(page)"
                                            target="_blank"
                                            :aria-label="`Preview ${page.title}`"
                                        >
                                            <ExternalLink class="h-4 w-4" />
                                        </a>
                                    </Button>

                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                    >
                                        <Link
                                            :href="`/admin/public-pages/${page.slug}/edit`"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>

                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        @click="remove(page)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!pages.data.length">
                            <td
                                colspan="5"
                                class="p-10 text-center text-muted-foreground"
                            >
                                No public pages found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="pages.links?.length"
                class="flex flex-wrap gap-2"
            >
                <template
                    v-for="link in pages.links"
                    :key="link.label"
                >
                    <Button
                        v-if="link.url"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        as-child
                    >
                        <Link
                            :href="link.url"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </Button>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
