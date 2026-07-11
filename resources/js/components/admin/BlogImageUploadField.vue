<script setup lang="ts">
import FormField from '@/Components/Forms/FormField.vue';
import { Input } from '@/components/ui/input';

withDefaults(defineProps<{
    id: string;
    label: string;
    description: string;
    currentUrl?: string | null;
    error?: string;
    previewClass?: string;
}>(), {
    currentUrl: null,
    error: undefined,
    previewClass: 'h-28 w-full',
});

const emit = defineEmits<{
    change: [event: Event];
}>();
</script>

<template>
    <FormField
        :label="label"
        :for-id="id"
        :description="description"
        :error="error"
    >
        <Input
            :id="id"
            type="file"
            accept="image/*"
            @change="emit('change', $event)"
        />

        <img
            v-if="currentUrl"
            :src="currentUrl"
            :alt="`Current ${label.toLowerCase()}`"
            :class="['mt-3 rounded-md border object-cover', previewClass]"
        />
    </FormField>
</template>
