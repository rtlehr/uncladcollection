<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Permission = {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
    description: string | null;
    is_system: boolean;
    is_locked: boolean;
};

const props = defineProps<{
    permission: Permission;
}>();

const form = useForm({
    name: props.permission.name,
    label: props.permission.label,
    group_name: props.permission.group_name ?? '',
    description: props.permission.description ?? '',
    is_system: props.permission.is_system,
    is_locked: props.permission.is_locked,
});

function submit() {
    form.put(`/admin/permissions/${props.permission.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Permission" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Edit Permission</h1>
            <p class="text-sm text-muted-foreground">
                Update an application permission.
            </p>
        </div>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
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
                <Label for="group_name">Group</Label>
                <Input id="group_name" v-model="form.group_name" />
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

            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_system" type="checkbox" />
                    System
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_locked" type="checkbox" />
                    Locked
                </label>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Permission' }}
                </Button>

                <Button variant="outline" as-child>
                    <Link href="/admin/permissions">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>