<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type LicenseRecord = {
    id: number;
    license_key: string;
    status: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    downloads_count: number;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string | null;

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

    order: {
        id: number;
        order_number: string;
    } | null;
};

const props = defineProps<{
    licenses: {
        data: LicenseRecord[];
        links: any[];
        meta: any;
    };
    filters: {
        search: string;
        status: string;
    };
    statuses: string[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

watch([search, status], () => {
    router.get('/admin/licenses', {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
});
</script>

<template>
    <Head title="Licenses" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">
                Licenses
            </h1>

            <p class="mt-1 text-muted-foreground">
                View customer image licenses and download usage.
            </p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <Input
                v-model="search"
                placeholder="Search license, user, image, or order..."
                class="md:max-w-sm"
            />

            <select
                v-model="status"
                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="">All Statuses</option>

                <option
                    v-for="licenseStatus in statuses"
                    :key="licenseStatus"
                    :value="licenseStatus"
                >
                    {{ licenseStatus }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">
                            License
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            User
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Image
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Order
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Status
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Downloads
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Expires
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Created
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="license in licenses.data"
                        :key="license.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ license.license_name }}
                            </div>

                            <div class="max-w-[240px] break-all text-xs text-muted-foreground">
                                {{ license.license_key }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <div v-if="license.user">
                                <div class="font-medium">
                                    {{ license.user.name }}
                                </div>

                                <div class="text-xs text-muted-foreground">
                                    {{ license.user.email }}
                                </div>
                            </div>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div v-if="license.image">
                                <div class="font-medium">
                                    {{ license.image.title }}
                                </div>

                                <Link
                                    :href="`/images/${license.image.slug}`"
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
                            <Link
                                v-if="license.order"
                                :href="`/admin/orders/${license.order.id}`"
                                class="text-primary hover:underline"
                            >
                                {{ license.order.order_number }}
                            </Link>

                            <span v-else class="text-muted-foreground">
                                —
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span class="rounded-full border px-2 py-1 text-xs">
                                {{ license.status }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{ license.downloads_used }}

                            <span v-if="license.download_limit !== null">
                                / {{ license.download_limit }}
                            </span>

                            <span v-else>
                                / Unlimited
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{ license.expires_at || 'Never' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ license.created_at || '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    as-child
                                >
                                    <Link :href="`/admin/licenses/${license.id}`">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="licenses.data.length === 0">
                        <td
                            colspan="9"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No licenses found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="licenses.links?.length"
            class="flex flex-wrap gap-2"
        >
            <Link
                v-for="link in licenses.links"
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