<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Permission = {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
    description: string | null;
};

type Role = {
    id: number;
    name: string;
    label: string;
    description: string | null;
    permissions: Permission[];
};

type UserRecord = {
    id: number;
    name: string;
    email: string;
    roles: Role[];
    permissions: Permission[];
    is_disabled: boolean;
    username: string | null;
};

const props = defineProps<{
    userRecord: UserRecord;
    roles: Role[];
    permissions: Record<string, Permission[]>;
}>();

const initialRoleIds = props.userRecord.roles.map((role) => role.id);
const initialDirectPermissionIds = props.userRecord.permissions.map((permission) => permission.id);

function getPermissionIdsForSelectedRoles(selectedRoleIds: number[]) {
    return props.roles
        .filter((role) => selectedRoleIds.includes(role.id))
        .flatMap((role) => role.permissions.map((permission) => permission.id));
}

const manuallySelectedPermissions = ref<number[]>([...initialDirectPermissionIds]);

const form = useForm({
    name: props.userRecord.name,
    email: props.userRecord.email,
    roles: initialRoleIds,
    permissions: Array.from(new Set([
        ...getPermissionIdsForSelectedRoles(initialRoleIds),
        ...initialDirectPermissionIds,
    ])),
    is_disabled: props.userRecord.is_disabled,
    username: props.userRecord.username ?? '',
});

function syncPermissionsFromRoles() {
    const rolePermissionIds = getPermissionIdsForSelectedRoles(form.roles);

    form.permissions = Array.from(new Set([
        ...rolePermissionIds,
        ...manuallySelectedPermissions.value,
    ]));
}

function toggleRole(roleId: number, checked: boolean) {
    if (checked) {
        if (!form.roles.includes(roleId)) {
            form.roles.push(roleId);
        }
    } else {
        form.roles = form.roles.filter((id) => id !== roleId);
    }

    syncPermissionsFromRoles();
}

function togglePermission(permissionId: number, checked: boolean) {
    if (checked) {
        if (!manuallySelectedPermissions.value.includes(permissionId)) {
            manuallySelectedPermissions.value.push(permissionId);
        }
    } else {
        manuallySelectedPermissions.value = manuallySelectedPermissions.value.filter(
            (id) => id !== permissionId
        );
    }

    syncPermissionsFromRoles();
}

function submit() {
    form.put(`/admin/users/${props.userRecord.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit User" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Edit User</h1>
            <p class="text-sm text-muted-foreground">
                Update user details, assigned roles, and direct permissions.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <div class="max-w-2xl space-y-5 rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">User Information</h2>

                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input id="username" v-model="form.username" />
                    <p class="text-xs text-muted-foreground">
                        Public name shown on comments and community activity.
                    </p>
                    <p v-if="form.errors.username" class="text-sm text-red-600">
                        {{ form.errors.username }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" />
                    <p v-if="form.errors.email" class="text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border p-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" v-model="form.is_disabled" />

                    <div>
                        <div class="font-medium text-red-600">
                            Disable User Account
                        </div>

                        <div class="text-sm text-muted-foreground">
                            Disabled users cannot log into the website.
                        </div>
                    </div>
                </label>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Roles</h2>
                <p class="mb-4 text-sm text-muted-foreground">
                    Select the roles assigned to this user. Role permissions will be automatically selected.
                </p>

                <div class="grid gap-4 md:grid-cols-2">
                    <label
                        v-for="role in roles"
                        :key="role.id"
                        class="flex items-start gap-3 rounded-md border p-3"
                    >
                        <input
                            type="checkbox"
                            class="mt-1 h-4 w-4 rounded border-gray-300"
                            :checked="form.roles.includes(role.id)"
                            @change="toggleRole(
                                role.id,
                                ($event.target as HTMLInputElement).checked
                            )"
                        />

                        <div>
                            <div class="font-medium">
                                {{ role.label }}
                            </div>

                            <div class="font-mono text-xs text-muted-foreground">
                                {{ role.name }}
                            </div>

                            <p
                                v-if="role.description"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ role.description }}
                            </p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">Permissions</h2>
                    <p class="text-sm text-muted-foreground">
                        Permissions from selected roles are automatically checked.
                        You may also add direct permissions manually.
                    </p>
                </div>

                <div
                    v-for="(groupPermissions, groupName) in permissions"
                    :key="groupName"
                    class="rounded-lg border bg-card p-6 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold">
                        {{ groupName || 'Ungrouped' }}
                    </h3>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label
                            v-for="permission in groupPermissions"
                            :key="permission.id"
                            class="flex items-start gap-3 rounded-md border p-3"
                        >
                            <input
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-gray-300"
                                :checked="form.permissions.includes(permission.id)"
                                @change="togglePermission(
                                    permission.id,
                                    ($event.target as HTMLInputElement).checked
                                )"
                            />

                            <div>
                                <div class="font-medium">
                                    {{ permission.label }}
                                </div>

                                <div class="font-mono text-xs text-muted-foreground">
                                    {{ permission.name }}
                                </div>

                                <p
                                    v-if="permission.description"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ permission.description }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save User' }}
                </Button>

                <Button variant="outline" as-child>
                    <Link href="/admin/users">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>