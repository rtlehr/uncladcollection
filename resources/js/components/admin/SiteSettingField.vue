<script setup lang="ts">
import FormField from '@/Components/Forms/FormField.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

import type { SiteSetting } from '@/types/siteSetting';

const props = defineProps<{
    setting: SiteSetting;
    modelValue: string;
    error?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function settingLabel(settingKey: string): string {
    return settingKey
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function updateBoolean(value: boolean) {
    emit('update:modelValue', value ? 'true' : 'false');
}

function updateValue(value: string | number) {
    emit('update:modelValue', String(value));
}
</script>

<template>
    <FormField
        :label="settingLabel(setting.setting_key)"
        :for-id="`setting-${setting.id}`"
        :description="setting.description ?? undefined"
        :error="error"
    >
        <div
            v-if="setting.setting_type === 'boolean'"
            class="flex items-start justify-between gap-4 rounded-md border p-4"
        >
            <div>
                <div class="text-sm font-medium">
                    Enabled
                </div>

                <p class="mt-1 text-xs text-muted-foreground">
                    Toggle this setting on or off.
                </p>
            </div>

            <Checkbox
                :id="`setting-${setting.id}`"
                :checked="modelValue === 'true'"
                @update:checked="updateBoolean(Boolean($event))"
            />
        </div>

        <textarea
            v-else-if="setting.setting_type === 'textarea'"
            :id="`setting-${setting.id}`"
            :value="modelValue"
            rows="4"
            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
            @input="emit(
                'update:modelValue',
                ($event.target as HTMLTextAreaElement).value,
            )"
        />

        <div
            v-else-if="setting.setting_type === 'color'"
            class="flex items-center gap-3"
        >
            <Input
                :id="`setting-${setting.id}`"
                :model-value="modelValue"
                type="color"
                class="h-10 w-16 cursor-pointer p-1"
                @update:model-value="updateValue"
            />

            <Input
                :model-value="modelValue"
                type="text"
                placeholder="#000000"
                class="font-mono"
                @update:model-value="updateValue"
            />
        </div>

        <div
            v-else-if="setting.setting_type === 'image'"
            class="space-y-2"
        >
            <Input
                :id="`setting-${setting.id}`"
                :model-value="modelValue"
                type="text"
                placeholder="Image path or URL"
                @update:model-value="updateValue"
            />

            <p class="text-xs text-muted-foreground">
                Enter a storage path or full image URL.
            </p>

            <img
                v-if="modelValue"
                :src="modelValue"
                :alt="settingLabel(setting.setting_key)"
                class="max-h-40 rounded-md border object-contain"
            />
        </div>

        <Input
            v-else
            :id="`setting-${setting.id}`"
            :model-value="modelValue"
            :type="
                setting.setting_type === 'email'
                    ? 'email'
                    : setting.setting_type === 'url'
                        ? 'url'
                        : 'text'
            "
            @update:model-value="updateValue"
        />

        <div class="mt-2">
            <span
                :class="[
                    'inline-flex rounded-full border px-2 py-0.5 text-xs',
                    setting.is_public
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                        : 'bg-muted text-muted-foreground',
                ]"
            >
                {{ setting.is_public ? 'Public setting' : 'Private setting' }}
            </span>
        </div>
    </FormField>
</template>
