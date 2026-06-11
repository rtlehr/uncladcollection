<script setup lang="ts">
type Activity = {
    id: number;
    admin_name: string;
    action: string;
    field_name: string | null;
    old_value: string | null;
    new_value: string | null;
    description: string | null;
    created_at: string | null;
};

defineProps<{
    activities: Activity[];
}>();

function formatLabel(value: string | null): string {
    if (!value) {
        return '—';
    }

    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatValue(value: string | null): string {
    if (!value) {
        return '—';
    }

    if (value === '1') {
        return 'Yes';
    }

    if (value === '0') {
        return 'No';
    }

    if (value === 'true') {
        return 'Yes';
    }

    if (value === 'false') {
        return 'No';
    }

    try {
        const parsed = JSON.parse(value);

        if (Array.isArray(parsed)) {
            return parsed.length ? parsed.join(', ') : 'None';
        }

        if (typeof parsed === 'boolean') {
            return parsed ? 'Yes' : 'No';
        }

        return String(parsed);
    } catch {
        return value;
    }
}
</script>

<template>
    <div class="rounded-lg border bg-card p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">
            Change Log
        </h2>

        <div v-if="activities.length" class="overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-3">Date</th>
                        <th class="p-3">Admin</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">Field</th>
                        <th class="p-3">Old Value</th>
                        <th class="p-3">New Value</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="activity in activities"
                        :key="activity.id"
                        class="border-b last:border-0 align-top"
                    >
                        <td class="p-3 whitespace-nowrap">
                            {{ activity.created_at || '—' }}
                        </td>

                        <td class="p-3">
                            {{ activity.admin_name }}
                        </td>

                        <td class="p-3">
                            <div class="font-medium">
                                {{ formatLabel(activity.action) }}
                            </div>

                            <div
                                v-if="activity.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ activity.description }}
                            </div>
                        </td>

                        <td class="p-3">
                            {{ formatLabel(activity.field_name) }}
                        </td>

                        <td class="max-w-xs break-words p-3">
                            {{ formatValue(activity.old_value) }}
                        </td>

                        <td class="max-w-xs break-words p-3">
                            {{ formatValue(activity.new_value) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No activity has been recorded yet.
        </p>
    </div>
</template>