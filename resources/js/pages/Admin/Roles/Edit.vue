<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    is_system: boolean;
    is_locked: boolean;
    permissions: Permission[];
};

const props = defineProps<{
    role: Role;
    permissions: Record<string, Permission[]>;
}>();

const form = useForm({
    name: props.role.name,
    label: props.role.label,
    description: props.role.description ?? '',
    permissions: props.role.permissions.map((permission) => permission.id),
});

function togglePermission(permissionId: number, checked: boolean) {
    if (checked) {
        if (!form.permissions.includes(permissionId)) {
            form.permissions.push(permissionId);
        }
    } else {
        form.permissions = form.permissions.filter((id) => id !== permissionId);
    }
}

function submit() {
    form.put(`/admin/roles/${props.role.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Role" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Edit Role</h1>
            <p class="text-sm text-muted-foreground">
                Update role details and assigned permissions.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <div class="max-w-2xl space-y-5 rounded-lg border bg-card p-6 shadow-sm">
                <div class="grid gap-2">
                    <Label for="label">Label</Label>
                    <Input id="label" v-model="form.label" />
                    <p v-if="form.errors.label" class="text-sm text-red-600">
                        {{ form.errors.label }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    />
                </div>

                <div class="flex gap-6 text-sm text-muted-foreground">
                    <span>System: {{ role.is_system ? 'Yes' : 'No' }}</span>
                    <span>Locked: {{ role.is_locked ? 'Yes' : 'No' }}</span>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">Permissions</h2>
                    <p class="text-sm text-muted-foreground">
                        Select the permissions this role should have.
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
                    {{ form.processing ? 'Saving...' : 'Save Role' }}
                </Button>

                <Button variant="outline" as-child>
                    <Link href="/admin/roles">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>