<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteSettingField from '@/Components/Admin/SiteSettingField.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';

import type {
    GroupedSiteSettings,
    SiteSettingFormValue,
} from '@/types/siteSetting';

const props = defineProps<{
    settings: GroupedSiteSettings;
}>();

const form = useForm<{
    settings: SiteSettingFormValue[];
}>({
    settings: Object.values(props.settings)
        .flat()
        .map((setting) => ({
            id: setting.id,
            setting_value: setting.setting_value ?? '',
        })),
});

const formValuesById = computed(() => {
    return new Map(
        form.settings.map((setting, index) => [
            setting.id,
            index,
        ]),
    );
});

function groupLabel(groupName: string): string {
    return groupName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function settingValue(settingId: number): string {
    const index = formValuesById.value.get(settingId);

    return index === undefined
        ? ''
        : form.settings[index].setting_value;
}

function updateSettingValue(
    settingId: number,
    value: string,
) {
    const index = formValuesById.value.get(settingId);

    if (index === undefined) {
        return;
    }

    form.settings[index].setting_value = value;
}

function settingError(settingId: number): string | undefined {
    return (
        form.errors[`settings.${settingId}.setting_value`]
        ?? form.errors[`settings.${settingId}`]
        ?? undefined
    );
}

function submit() {
    form.put('/admin/site-settings', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Site Settings" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Site Settings"
            description="Manage branding, contact information, theme settings, SEO, social links, and public site configuration."
        />

        <form class="space-y-8" @submit.prevent="submit">
            <FormSection
                v-for="(groupSettings, groupName) in settings"
                :key="groupName"
                :title="groupLabel(String(groupName))"
                :description="`${groupSettings.length} setting${groupSettings.length === 1 ? '' : 's'} in this group.`"
            >
                <div class="grid gap-6 lg:grid-cols-2">
                    <SiteSettingField
                        v-for="setting in groupSettings"
                        :key="setting.id"
                        :setting="setting"
                        :model-value="settingValue(setting.id)"
                        :error="settingError(setting.id)"
                        @update:model-value="
                            updateSettingValue(setting.id, $event)
                        "
                    />
                </div>
            </FormSection>

            <FormActions
                submit-label="Save Settings"
                processing-label="Saving..."
                :processing="form.processing"
                :disabled="!form.isDirty"
                :show-cancel="false"
                @submit="submit"
            />
        </form>
    </div>
</template>
