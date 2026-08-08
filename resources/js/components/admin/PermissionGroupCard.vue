<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';

import type { Permission } from '@/types/role';

defineProps<{
    title: string;
    permissions: Permission[];
    selectedPermissionIds: number[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    toggle: [permissionId: number, checked: boolean];
}>();
</script>

<template>
    <section class="rounded-lg border bg-card p-6 shadow-sm">
        <h3 class="mb-4 font-semibold">
            {{ title || 'Ungrouped' }}
        </h3>

        <div class="grid gap-4 md:grid-cols-2">
            <label
                v-for="permission in permissions"
                :key="permission.id"
                class="flex items-start gap-3 rounded-md border p-3 transition hover:bg-muted/30"
            >
                <Checkbox
                    :checked="selectedPermissionIds.includes(permission.id)"
                    :disabled="disabled"
                    @update:checked="
                        emit(
                            'toggle',
                            permission.id,
                            Boolean($event),
                        )
                    "
                />

                <div class="min-w-0">
                    <div class="font-medium">
                        {{ permission.label }}
                    </div>

                    <div class="break-all font-mono text-xs text-muted-foreground">
                        {{ permission.name }}
                    </div>

                    <p
                        v-if="permission.description"
                        class="mt-1 text-xs leading-5 text-muted-foreground"
                    >
                        {{ permission.description }}
                    </p>
                </div>
            </label>
        </div>
    </section>
</template>
