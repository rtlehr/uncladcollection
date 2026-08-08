<script setup lang="ts">
import { AlertCircle, CheckCircle2, ChevronRight, Menu } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

export type AdminSectionDefinition = {
    id: string;
    title: string;
    description?: string;
    errorKeys?: string[];
    badge?: string | number | null;
    dirty?: boolean;
};

const props = withDefaults(
    defineProps<{
        sections: AdminSectionDefinition[];
        errors?: Record<string, string | undefined>;
        label?: string;
        storageKey?: string;
    }>(),
    {
        errors: () => ({}),
        label: 'Page sections',
        storageKey: '',
    },
);

const defaultSection = computed(() => props.sections[0]?.id ?? '');
const activeSection = ref(defaultSection.value);

const sectionStates = computed(() =>
    props.sections.map((section) => ({
        ...section,
        hasError: (section.errorKeys ?? []).some((key) =>
            Object.keys(props.errors).some(
                (errorKey) => errorKey === key || errorKey.startsWith(`${key}.`),
            ),
        ),
    })),
);

function isValidSection(id: string | null): id is string {
    return Boolean(id && props.sections.some((section) => section.id === id));
}

function selectSection(id: string): void {
    if (!isValidSection(id)) {
return;
}

    activeSection.value = id;
}

function onMobileChange(event: Event): void {
    selectSection((event.target as HTMLSelectElement).value);
}

function firstErrorSection(): string | null {
    return sectionStates.value.find((section) => section.hasError)?.id ?? null;
}

onMounted(() => {
    const errorSection = firstErrorSection();

    if (errorSection) {
        activeSection.value = errorSection;

        return;
    }

    if (!props.storageKey) {
return;
}

    const stored = window.localStorage.getItem(props.storageKey);

    if (isValidSection(stored)) {
activeSection.value = stored;
}
});

watch(activeSection, (value) => {
    if (props.storageKey && value) {
        window.localStorage.setItem(props.storageKey, value);
    }
});

watch(
    () => props.errors,
    () => {
        const errorSection = firstErrorSection();

        if (errorSection) {
selectSection(errorSection);
}
    },
    { deep: true },
);

watch(
    () => props.sections,
    () => {
        if (!isValidSection(activeSection.value)) {
            activeSection.value = defaultSection.value;
        }
    },
    { deep: true },
);
</script>

<template>
    <div class="grid items-start gap-6 lg:grid-cols-[240px_minmax(0,1fr)] xl:grid-cols-[270px_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-6">
            <div class="rounded-xl border bg-card p-3 shadow-sm">
                <div class="mb-3 flex items-center gap-2 px-2 text-sm font-semibold">
                    <Menu class="h-4 w-4 text-muted-foreground" />
                    {{ label }}
                </div>

                <select
                    class="h-10 w-full rounded-md border bg-background px-3 text-sm lg:hidden"
                    :value="activeSection"
                    aria-label="Choose a workspace"
                    @change="onMobileChange"
                >
                    <option v-for="section in sectionStates" :key="section.id" :value="section.id">
                        {{ section.title }}{{ section.badge !== null && section.badge !== undefined ? ` (${section.badge})` : '' }}{{ section.dirty ? ' — unsaved' : '' }}{{ section.hasError ? ' — needs attention' : '' }}
                    </option>
                </select>

                <nav class="hidden space-y-1 lg:block" :aria-label="label">
                    <button
                        v-for="section in sectionStates"
                        :key="section.id"
                        type="button"
                        :aria-current="activeSection === section.id ? 'page' : undefined"
                        :class="[
                            'group flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition-colors',
                            activeSection === section.id
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        @click="selectSection(section.id)"
                    >
                        <AlertCircle
                            v-if="section.hasError"
                            class="mt-0.5 h-4 w-4 shrink-0 text-destructive"
                        />
                        <CheckCircle2
                            v-else-if="activeSection === section.id"
                            class="mt-0.5 h-4 w-4 shrink-0"
                        />
                        <span v-else class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-current opacity-40" />

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2 text-sm font-medium">
                                <span class="flex items-center gap-2"><span>{{ section.title }}</span><span v-if="section.dirty" class="h-2 w-2 rounded-full bg-current" title="Unsaved changes" /></span>
                                <span
                                    v-if="section.badge !== null && section.badge !== undefined"
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        activeSection === section.id
                                            ? 'bg-primary-foreground/15 text-primary-foreground'
                                            : 'bg-muted text-muted-foreground',
                                    ]"
                                >
                                    {{ section.badge }}
                                </span>
                            </span>
                            <span
                                v-if="section.description"
                                :class="[
                                    'mt-0.5 block text-xs leading-4',
                                    activeSection === section.id ? 'text-primary-foreground/75' : 'text-muted-foreground',
                                ]"
                            >
                                {{ section.description }}
                            </span>
                        </span>
                        <ChevronRight class="mt-0.5 h-4 w-4 shrink-0 opacity-60" />
                    </button>
                </nav>
            </div>
        </aside>

        <div class="min-w-0">
            <slot :active-section="activeSection" :select-section="selectSection" />
        </div>
    </div>
</template>
