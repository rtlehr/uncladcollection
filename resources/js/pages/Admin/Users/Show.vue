<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ActivityLog from '@/components/admin/ActivityLog.vue';
import ShowDetailsGrid from '@/Components/Show/ShowDetailsGrid.vue';
import ShowPageHeader from '@/Components/Show/ShowPageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ShowSummaryGrid from '@/Components/Show/ShowSummaryGrid.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import StatCard from '@/Components/Shared/StatCard.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminActivityRecord,
    AdminUserRecord,
} from '@/types/adminUser';

defineProps<{
    userRecord: AdminUserRecord;
    activities: AdminActivityRecord[];
}>();
</script>

<template>
    <Head :title="`User: ${userRecord.name}`" />

    <div class="space-y-6 p-6">
        <ShowPageHeader
            title="User Details"
            description="View account information, roles, permissions, and change history."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/admin/users">Back</Link>
                </Button>

                <Button as-child>
                    <Link :href="`/admin/users/${userRecord.id}/edit`">
                        Edit User
                    </Link>
                </Button>
            </template>
        </ShowPageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <ShowSection title="Account Information" class="lg:col-span-2">
                <ShowDetailsGrid :columns="2">
                    <DetailRow label="Name" :value="userRecord.name" />
                    <DetailRow label="Username" :value="userRecord.username" />
                    <DetailRow label="Email" :value="userRecord.email" />

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">Status</div>
                        <div class="mt-1">
                            <StatusBadge
                                :status="userRecord.is_disabled ? 'disabled' : 'active'"
                                size="md"
                            />
                        </div>
                    </div>

                    <DetailRow label="Created" :value="userRecord.created_at" />
                    <DetailRow label="Last Updated" :value="userRecord.updated_at" />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection title="Summary">
                <ShowSummaryGrid :columns="1">
                    <StatCard label="Roles" :value="userRecord.roles.length" />
                    <StatCard label="Direct Permissions" :value="userRecord.permissions.length" />
                    <StatCard label="Total Permissions" :value="userRecord.all_permissions_count" />
                </ShowSummaryGrid>
            </ShowSection>
        </div>

        <ShowSection title="Roles" description="Roles assigned directly to this user.">
            <div v-if="userRecord.roles.length" class="flex flex-wrap gap-3">
                <div
                    v-for="role in userRecord.roles"
                    :key="role.id"
                    class="rounded-md border px-3 py-2 text-sm"
                >
                    <div class="font-medium">{{ role.label }}</div>
                    <div class="font-mono text-xs text-muted-foreground">
                        {{ role.name }}
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-muted-foreground">
                This user does not have any assigned roles.
            </p>
        </ShowSection>

        <ShowSection
            title="Direct Permissions"
            description="Permissions assigned directly to this user, excluding role-based permissions."
        >
            <DataTable min-width="700px">
                <thead>
                    <tr class="border-b bg-muted/30">
                        <DataTableHeaderCell label="Permission" />
                        <DataTableHeaderCell label="Name" />
                        <DataTableHeaderCell label="Group" />
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="permission in userRecord.permissions"
                        :key="permission.id"
                        class="border-b last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-4 font-medium">{{ permission.label }}</td>
                        <td class="p-4 font-mono text-xs">{{ permission.name }}</td>
                        <td class="p-4">{{ permission.group_name || 'Ungrouped' }}</td>
                    </tr>

                    <DataTableEmpty
                        v-if="userRecord.permissions.length === 0"
                        :colspan="3"
                        message="This user does not have any direct permissions."
                    />
                </tbody>
            </DataTable>
        </ShowSection>

        <ActivityLog :activities="activities" />
    </div>
</template>
