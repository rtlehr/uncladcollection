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
            class="p-8 text-center text-muted-foreground"
        >
            {{ message }}
        </td>
    </tr>
</template>
