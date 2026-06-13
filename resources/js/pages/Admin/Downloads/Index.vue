<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type DownloadRecord = {
    id: number;
    download_type: string;
    ip_address: string | null;
    downloaded_at: string | null;

    user: {
        id: number;
        name: string;
        email: string;
    } | null;

    image: {
        id: number;
        title: string;
        slug: string;
    } | null;

    license: {
        id: number;
        license_key: string;
        license_name: string;
    } | null;

    order: {
        id: number;
        order_number: string;
    } | null;
};

const props = defineProps<{
    downloads: {
        data: DownloadRecord[];
        links: any[];
        meta: any;
    };
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search ?? '');

watch(search, () => {
    router.get('/admin/downloads', {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
});
</script>

<template>
    <Head title="Downloads" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">
                Downloads
            </h1>

            <p class="mt-1 text-muted-foreground">
                View customer image download history.
            </p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <Input
                v-model="search"
                placeholder="Search downloads, users, images, licenses, or orders..."
                class="md:max-w-md"
            />
        </div>

        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">
                            Downloaded
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            User
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Image
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            License
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Order
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Type
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            IP
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="download in downloads.data"
                        :key="download.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            {{ download.downloaded_at || '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div v-if="download.user">
                                <div class="font-medium">
                                    {{ download.user.name }}
                                </div>

                                <div class="text-xs text-muted-foreground">
                                    {{ download.user.email }}
                                </div>
                            </div>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div v-if="download.image">
                                <div class="font-medium">
                                    {{ download.image.title }}
                                </div>

                                <Link
                                    :href="`/images/${download.image.slug}`"
                                    class="text-xs text-primary hover:underline"
                                >
                                    View Image
                                </Link>
                            </div>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div v-if="download.license">
                                <Link
                                    :href="`/admin/licenses/${download.license.id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ download.license.license_name }}
                                </Link>

                                <div class="max-w-[220px] break-all text-xs text-muted-foreground">
                                    {{ download.license.license_key }}
                                </div>
                            </div>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <Link
                                v-if="download.order"
                                :href="`/admin/orders/${download.order.id}`"
                                class="text-primary hover:underline"
                            >
                                {{ download.order.order_number }}
                            </Link>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{ download.download_type }}
                        </td>

                        <td class="px-4 py-3">
                            {{ download.ip_address || '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    as-child
                                >
                                    <Link :href="`/admin/downloads/${download.id}`">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="downloads.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No downloads found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="downloads.links?.length"
            class="flex flex-wrap gap-2"
        >
            <Link
                v-for="link in downloads.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="rounded-md border px-3 py-2 text-sm"
                :class="{
                    'bg-primary text-primary-foreground': link.active,
                    'pointer-events-none opacity-50': !link.url,
                }"
            />
        </div>
    </div>
</template>