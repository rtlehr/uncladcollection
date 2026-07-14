<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ChevronRight,
    CircleAlert,
    Settings2,
} from '@lucide/vue';
import { computed, ref } from 'vue';

import SiteSettingField from '@/Components/Admin/SiteSettingField.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';

import type {
    GroupedSiteSettings,
    SiteSettingFormValue,
} from '@/types/siteSetting';

type GroupName = keyof GroupedSiteSettings & string;

type SettingsGroupDefinition = {
    title: string;
    description: string;
};

const props = defineProps<{
    settings: GroupedSiteSettings;
    heroAssetOptions: Array<{
        value: string;
        label: string;
    }>;
}>();

const groupDefinitions: Record<string, SettingsGroupDefinition> = {
    general: {
        title: 'General',
        description:
            'Control the public site name, tagline, contact information, and other general identity settings.',
    },

    branding: {
        title: 'Branding',
        description:
            'Manage logos, icons, brand imagery, and other visual identity elements used throughout the site.',
    },

    theme: {
        title: 'Theme & Appearance',
        description:
            'Set the primary colors, visual theme, and appearance options used across public and administrative pages.',
    },

    homepage: {
        title: 'Homepage',
        description:
            'Control homepage headlines, calls to action, visible sections, hero presentation, and featured content.',
    },

    hero: {
        title: 'Homepage Hero',
        description:
            'Configure the homepage hero media, layout, overlay, autoplay behavior, positioning, and promotional text.',
    },

    contact: {
        title: 'Contact Information',
        description:
            'Manage the public contact details shown in the footer, support pages, invoices, and other customer-facing areas.',
    },

    social: {
        title: 'Social Media',
        description:
            'Add links to the organization’s social profiles and control which social channels appear publicly.',
    },

    seo: {
        title: 'SEO & Sharing',
        description:
            'Set default search-engine titles, descriptions, sharing images, and metadata used when a page has no custom SEO content.',
    },

    commerce: {
        title: 'Commerce',
        description:
            'Manage marketplace, purchasing, licensing, checkout, and customer-order settings.',
    },

    email: {
        title: 'Email',
        description:
            'Control the sender identity and default wording used for automated site and commerce emails.',
    },

    legal: {
        title: 'Legal',
        description:
            'Manage copyright notices, licensing language, privacy references, terms, and other legal information.',
    },

    footer: {
        title: 'Footer',
        description:
            'Control the text, links, contact details, and supporting information displayed in the public site footer.',
    },

    analytics: {
        title: 'Analytics',
        description:
            'Configure analytics, tracking, and measurement settings used to understand site usage and campaign performance.',
    },

    integrations: {
        title: 'Integrations',
        description:
            'Manage settings for connected services and external platforms used by the application.',
    },
};

const preferredGroupOrder = [
    'general',
    'branding',
    'theme',
    'homepage',
    'hero',
    'commerce',
    'contact',
    'social',
    'seo',
    'email',
    'legal',
    'footer',
    'analytics',
    'integrations',
];

const orderedGroups = computed(() => {
    const availableGroups = Object.keys(props.settings);

    return [...availableGroups].sort((first, second) => {
        const firstPosition = preferredGroupOrder.indexOf(first);
        const secondPosition = preferredGroupOrder.indexOf(second);

        if (firstPosition === -1 && secondPosition === -1) {
            return first.localeCompare(second);
        }

        if (firstPosition === -1) {
            return 1;
        }

        if (secondPosition === -1) {
            return -1;
        }

        return firstPosition - secondPosition;
    });
});

const activeGroup = ref<GroupName>(
    (orderedGroups.value[0] ?? '') as GroupName,
);

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

const activeGroupSettings = computed(() => {
    if (!activeGroup.value) {
        return [];
    }

    return props.settings[activeGroup.value] ?? [];
});

const activeGroupDefinition = computed(() => {
    return groupDefinition(activeGroup.value);
});

function fallbackGroupLabel(groupName: string): string {
    return groupName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function groupDefinition(
    groupName: string,
): SettingsGroupDefinition {
    return (
        groupDefinitions[groupName] ?? {
            title: fallbackGroupLabel(groupName),
            description:
                'Manage the settings and public behavior associated with this section of the site.',
        }
    );
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
): void {
    const index = formValuesById.value.get(settingId);

    if (index === undefined) {
        return;
    }

    form.settings[index].setting_value = value;
}

function settingError(
    settingId: number,
): string | undefined {
    return (
        form.errors[`settings.${settingId}.setting_value`]
        ?? form.errors[`settings.${settingId}`]
        ?? undefined
    );
}

function groupHasError(groupName: string): boolean {
    const groupSettings = props.settings[groupName] ?? [];

    return groupSettings.some(
        (setting) => Boolean(settingError(setting.id)),
    );
}

function groupDirtyCount(groupName: string): number {
    const groupSettings = props.settings[groupName] ?? [];

    return groupSettings.filter((setting) => {
        const formIndex = formValuesById.value.get(setting.id);

        if (formIndex === undefined) {
            return false;
        }

        const originalValue = setting.setting_value ?? '';
        const currentValue =
            form.settings[formIndex].setting_value ?? '';

        return originalValue !== currentValue;
    }).length;
}

function selectGroup(groupName: string): void {
    activeGroup.value = groupName as GroupName;
}

function submit(): void {
    form.put('/admin/site-settings', {
        preserveScroll: true,

        onError: () => {
            const groupWithError = orderedGroups.value.find(
                (groupName) => groupHasError(groupName),
            );

            if (groupWithError) {
                activeGroup.value = groupWithError as GroupName;
            }
        },
    });
}
</script>

<template>
    <Head title="Site Settings" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Site Settings"
            description="Manage branding, homepage presentation, commerce preferences, contact information, SEO, social links, and other public site configuration."
        />

        <form
            class="space-y-6"
            @submit.prevent="submit"
        >
            <div
                class="overflow-hidden rounded-2xl border border-border bg-background shadow-sm"
            >
                <!-- Mobile tab selector -->
                <div
                    class="border-b border-border p-4 lg:hidden"
                >
                    <label
                        for="settings-section"
                        class="mb-2 block text-sm font-medium"
                    >
                        Settings section
                    </label>

                    <select
                        id="settings-section"
                        v-model="activeGroup"
                        class="h-11 w-full rounded-lg border border-input bg-background px-3 text-sm"
                    >
                        <option
                            v-for="groupName in orderedGroups"
                            :key="groupName"
                            :value="groupName"
                        >
                            {{ groupDefinition(groupName).title }}
                        </option>
                    </select>
                </div>

                <div class="lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
                    <!-- Desktop tabs -->
                    <aside
                        class="hidden border-r border-border bg-muted/20 p-3 lg:block"
                    >
                        <div class="px-3 pb-3 pt-2">
                            <div
                                class="flex items-center gap-2 text-sm font-semibold"
                            >
                                <Settings2 class="h-4 w-4" />
                                Settings Sections
                            </div>

                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                Select a section to review and update its
                                settings.
                            </p>
                        </div>

                        <nav
                            class="space-y-1"
                            aria-label="Site settings sections"
                        >
                            <button
                                v-for="groupName in orderedGroups"
                                :key="groupName"
                                type="button"
                                :class="[
                                    'group flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition',
                                    activeGroup === groupName
                                        ? 'bg-background text-foreground shadow-sm ring-1 ring-border'
                                        : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
                                ]"
                                @click="selectGroup(groupName)"
                            >
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="truncate text-sm font-medium"
                                        >
                                            {{
                                                groupDefinition(
                                                    groupName,
                                                ).title
                                            }}
                                        </span>

                                        <CircleAlert
                                            v-if="
                                                groupHasError(
                                                    groupName,
                                                )
                                            "
                                            class="h-4 w-4 shrink-0 text-destructive"
                                        />
                                    </div>

                                    <div
                                        class="mt-1 flex items-center gap-2 text-xs text-muted-foreground"
                                    >
                                        <span>
                                            {{
                                                settings[groupName]
                                                    ?.length ?? 0
                                            }}
                                            settings
                                        </span>

                                        <span
                                            v-if="
                                                groupDirtyCount(
                                                    groupName,
                                                )
                                            "
                                            class="rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary"
                                        >
                                            {{
                                                groupDirtyCount(
                                                    groupName,
                                                )
                                            }}
                                            changed
                                        </span>
                                    </div>
                                </div>

                                <ChevronRight
                                    :class="[
                                        'h-4 w-4 shrink-0 transition',
                                        activeGroup === groupName
                                            ? 'translate-x-0 text-foreground'
                                            : '-translate-x-1 opacity-0 group-hover:translate-x-0 group-hover:opacity-100',
                                    ]"
                                />
                            </button>
                        </nav>
                    </aside>

                    <!-- Active section -->
                    <section class="min-w-0">
                        <header
                            class="border-b border-border bg-muted/10 px-5 py-6 sm:px-7"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="max-w-3xl">
                                    <p
                                        class="text-xs font-semibold uppercase tracking-[0.16em] text-primary"
                                    >
                                        Site Settings
                                    </p>

                                    <h2
                                        class="mt-2 text-2xl font-semibold tracking-tight"
                                    >
                                        {{
                                            activeGroupDefinition.title
                                        }}
                                    </h2>

                                    <p
                                        class="mt-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        {{
                                            activeGroupDefinition.description
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="shrink-0 rounded-full border border-border bg-background px-3 py-1.5 text-xs font-medium text-muted-foreground"
                                >
                                    {{ activeGroupSettings.length }}
                                    setting{{
                                        activeGroupSettings.length === 1
                                            ? ''
                                            : 's'
                                    }}
                                </div>
                            </div>
                        </header>

                        <div class="p-5 sm:p-7">
                            <div
                                v-if="activeGroupSettings.length"
                                class="grid gap-x-8 gap-y-7 xl:grid-cols-2"
                            >
                                <SiteSettingField
                                    v-for="setting in activeGroupSettings"
                                    :key="setting.id"
                                    :setting="setting"
                                    :model-value="
                                        settingValue(setting.id)
                                    "
                                    :error="
                                        settingError(setting.id)
                                    "
                                    :options="
                                        setting.setting_key
                                            === 'hero_asset_id'
                                            ? heroAssetOptions
                                            : undefined
                                    "
                                    @update:model-value="
                                        updateSettingValue(
                                            setting.id,
                                            $event,
                                        )
                                    "
                                />
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed border-border p-10 text-center"
                            >
                                <Settings2
                                    class="mx-auto h-8 w-8 text-muted-foreground"
                                />

                                <h3 class="mt-3 font-semibold">
                                    No settings in this section
                                </h3>

                                <p
                                    class="mt-1 text-sm text-muted-foreground"
                                >
                                    Settings added to this group will
                                    appear here automatically.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <FormActions
                submit-label="Save Settings"
                processing-label="Saving..."
                :processing="form.processing"
                :disabled="!form.isDirty"
                :show-cancel="false"
                sticky
                @submit="submit"
            />
        </form>
    </div>
</template>