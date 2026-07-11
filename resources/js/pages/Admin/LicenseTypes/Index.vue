<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type { AdminLicenseType } from '@/types/licenseType';

defineProps<{
    licenseTypes: AdminLicenseType[];
}>();

const selectedLicenseType = ref<AdminLicenseType | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function formatPrice(
    priceCents: number,
    currency: string,
): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(priceCents / 100);
}

function requestDelete(licenseType: AdminLicenseType) {
    selectedLicenseType.value = licenseType;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    selectedLicenseType.value = null;
    deleteDialogOpen.value = false;
}

function confirmDelete() {
    if (!selectedLicenseType.value) {
        return;
    }

    deleting.value = true;

    router.delete(
        `/admin/license-types/${selectedLicenseType.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
                selectedLicenseType.value = null;
                deleteDialogOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="License Types" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="License Types"
                description="Manage image licensing options available for purchase."
            />

            <ActionToolbar align="end">
                <template #secondary>
                    <Button as-child>
                        <Link href="/admin/license-types/create">
                            Add License Type
                        </Link>
                    </Button>
                </template>
            </ActionToolbar>

            <DataTable min-width="900px">
                <thead>
                    <tr class="border-b bg-muted/30">
                        <DataTableHeaderCell label="Name" />
                        <DataTableHeaderCell label="Price" />
                        <DataTableHeaderCell label="Resolution" />
                        <DataTableHeaderCell label="Downloads" />
                        <DataTableHeaderCell label="Status" />
                        <DataTableHeaderCell label="Sort" />
                        <DataTableHeaderCell
                            label="Actions"
                            align="right"
                        />
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="licenseType in licenseTypes"
                        :key="licenseType.id"
                        class="border-b last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-4">
                            <div class="font-medium">
                                {{ licenseType.name }}
                            </div>

                            <div class="font-mono text-xs text-muted-foreground">
                                {{ licenseType.slug }}
                            </div>
                        </td>

                        <td class="p-4 font-medium">
                            {{
                                formatPrice(
                                    licenseType.price_cents,
                                    licenseType.currency,
                                )
                            }}
                        </td>

                        <td class="p-4 capitalize">
                            {{ licenseType.max_resolution.replaceAll('_', ' ') }}
                        </td>

                        <td class="p-4">
                            {{ licenseType.download_limit ?? 'Unlimited' }}
                        </td>

                        <td class="p-4">
                            <StatusBadge
                                :status="
                                    licenseType.is_active
                                        ? 'active'
                                        : 'inactive'
                                "
                            />
                        </td>

                        <td class="p-4">
                            {{ licenseType.sort_order }}
                        </td>

                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <Link
                                        :href="`/admin/license-types/${licenseType.id}/edit`"
                                    >
                                        Edit
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="requestDelete(licenseType)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <DataTableEmpty
                        v-if="licenseTypes.length === 0"
                        :colspan="7"
                        message="No license types found."
                    />
                </tbody>
            </DataTable>

            <ConfirmActionDialog
                v-model:open="deleteDialogOpen"
                title="Delete license type?"
                :description="
                    selectedLicenseType
                        ? `Delete '${selectedLicenseType.name}'? This action cannot be undone.`
                        : 'This action cannot be undone.'
                "
                confirm-label="Delete License Type"
                destructive
                :loading="deleting"
                @confirm="confirmDelete"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
