<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SiteSetting = {
    id: number;
    group_name: string;
    setting_key: string;
    setting_value: string | null;
    setting_type: string;
    description: string | null;
    is_public: boolean;
};

const props = defineProps<{
    settings: Record<string, SiteSetting[]>;
}>();

const form = useForm({
    settings: Object.values(props.settings)
        .flat()
        .map((setting) => ({
            id: setting.id,
            setting_value: setting.setting_value ?? '',
        })),
});

function groupLabel(groupName: string): string {
    return groupName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function settingLabel(settingKey: string): string {
    return settingKey
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function submit() {
    form.put('/admin/site-settings', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Site Settings" />

    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Site Settings</h1>
            <p class="text-sm text-muted-foreground">
                Manage branding, contact information, theme settings, SEO, social links, and public site configuration.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <div
                v-for="(groupSettings, groupName) in settings"
                :key="groupName"
                class="rounded-lg border bg-card p-6 shadow-sm"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{ groupLabel(String(groupName)) }}
                </h2>

                <div class="grid gap-5">
                    <div
                        v-for="setting in groupSettings"
                        :key="setting.id"
                        class="grid gap-2"
                    >
                        <template
                            v-for="formSetting in form.settings"
                            :key="formSetting.id"
                        >
                            <div
                                v-if="formSetting.id === setting.id"
                                class="grid gap-2"
                            >
                                <Label :for="`setting-${setting.id}`">
                                    {{ settingLabel(setting.setting_key) }}
                                </Label>

                                <div
                                    v-if="setting.setting_type === 'boolean'"
                                    class="flex items-center gap-3"
                                >
                                    <input
                                        :id="`setting-${setting.id}`"
                                        v-model="formSetting.setting_value"
                                        type="checkbox"
                                        true-value="true"
                                        false-value="false"
                                        class="h-4 w-4 rounded border-gray-300"
                                    />

                                    <span class="text-sm text-muted-foreground">
                                        Enabled
                                    </span>
                                </div>

                                <textarea
                                    v-else-if="setting.setting_type === 'textarea'"
                                    :id="`setting-${setting.id}`"
                                    v-model="formSetting.setting_value"
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                                />

                                <Input
                                    v-else
                                    :id="`setting-${setting.id}`"
                                    v-model="formSetting.setting_value"
                                    :type="
                                        setting.setting_type === 'email'
                                            ? 'email'
                                            : setting.setting_type === 'url'
                                                ? 'url'
                                                : setting.setting_type === 'color'
                                                    ? 'color'
                                                    : 'text'
                                    "
                                    :placeholder="
                                        setting.setting_type === 'image'
                                            ? 'Image path or URL'
                                            : ''
                                    "
                                />

                                <p
                                    v-if="setting.setting_type === 'image'"
                                    class="text-xs text-muted-foreground"
                                >
                                    For now, enter an image path or URL. We can add file upload support later.
                                </p>

                                <p
                                    v-if="setting.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ setting.description }}
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Settings' }}
                </Button>
            </div>
        </form>
    </div>
</template>