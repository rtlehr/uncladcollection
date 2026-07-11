<script setup lang="ts">
withDefaults(
    defineProps<{
        colspan: number;
        message?: string;
    }>(),
    {
        message: 'No records found.',
    },
);
</script>

<template>
    <tr>
        <td
            :colspan="colspan"
            class="px-6 py-14 text-center"
        >
            <div class="mx-auto max-w-md">
                <p class="text-sm font-medium text-foreground">
                    {{ message }}
                </p>

                <p class="mt-1 text-xs text-muted-foreground">
                    Try adjusting your filters or adding a new record.
                </p>
            </div>
        </td>
    </tr>
</template>
