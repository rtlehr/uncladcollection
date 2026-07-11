<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type { AdminPermission } from '@/types/permission';

defineProps<{
    title: string;
    permissions: AdminPermission[];
}>();

const emit = defineEmits<{
    delete: [permission: AdminPermission];
}>();
</script>

<template>
    <section class="space-y-3">
        <h2 class="text-lg font-semibold">
            {{ title || 'Ungrouped' }}
        </h2>

        <DataTable min-width="900px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell label="Label" />
                    <DataTableHeaderCell label="Name" />
                    <DataTableHeaderCell label="Description" />
                    <DataTableHeaderCell label="System" />
                    <DataTableHeaderCell label="Locked" />
                    <DataTableHeaderCell label="Actions" align="right" />
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="permission in permissions"
                    :key="permission.id"
                    class="border-b last:border-0 hover:bg-muted/20"
                >
                    <td class="p-4 font-medium">
                        {{ permission.label }}
                    </td>

                    <td class="p-4 font-mono text-xs">
                        {{ permission.name }}
                    </td>

                    <td class="p-4 text-muted-foreground">
                        {{ permission.description || '—' }}
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="permission.is_system ? 'system' : 'custom'"
                            :label="permission.is_system ? 'Yes' : 'No'"
                            :tone="permission.is_system ? 'info' : 'neutral'"
                        />
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="permission.is_locked ? 'locked' : 'unlocked'"
                            :label="permission.is_locked ? 'Yes' : 'No'"
                            :tone="permission.is_locked ? 'warning' : 'neutral'"
                        />
                    </td>

                    <td class="p-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/admin/permissions/${permission.id}/edit`">
                                    Edit
                                </Link>
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                :disabled="permission.is_locked"
                                @click="emit('delete', permission)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="permissions.length === 0"
                    :colspan="6"
                    message="No permissions found in this group."
                />
            </tbody>
        </DataTable>
    </section>
</template>
