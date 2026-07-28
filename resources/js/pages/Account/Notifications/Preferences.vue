<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';

interface NotificationCategory {
    key: string;
    label: string;
    description: string;
    transactional: boolean;
    in_app_enabled: boolean;
    email_enabled: boolean;
    email_frequency: string;
}

const props = defineProps<{
    categories: NotificationCategory[];
}>();

const form = useForm({
    preferences: props.categories.map((item) => ({
        category: item.key,
        in_app_enabled: item.in_app_enabled,
        email_enabled: item.email_enabled,
        email_frequency: item.email_frequency,
    })),
});

function submit(): void {
    form.put('/account/notification-preferences', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Notification Preferences" />

    <AccountPageLayout>
        <template #title>Notification Preferences</template>
        <template #description>
            Choose how Unclad Collection contacts you. Essential transaction and security emails remain enabled.
        </template>

        <form class="space-y-4" @submit.prevent="submit">
            <section
                v-for="(category, index) in categories"
                :key="category.key"
                class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-semibold">{{ category.label }}</h2>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                            {{ category.description }}
                        </p>
                        <p
                            v-if="category.transactional"
                            class="mt-2 text-xs font-medium text-[var(--brand-accent)]"
                        >
                            Essential account notice
                        </p>
                    </div>

                    <div class="grid min-w-52 gap-3 text-sm">
                        <label class="flex items-center justify-between gap-4">
                            <span>In-app</span>
                            <input
                                v-model="form.preferences[index].in_app_enabled"
                                type="checkbox"
                            />
                        </label>
                        <label class="flex items-center justify-between gap-4">
                            <span>Email</span>
                            <input
                                v-model="form.preferences[index].email_enabled"
                                type="checkbox"
                                :disabled="category.transactional"
                            />
                        </label>
                    </div>
                </div>
            </section>

            <button
                type="submit"
                class="rounded-xl bg-[var(--brand-primary)] px-5 py-3 font-medium text-white disabled:opacity-50"
                :disabled="form.processing"
            >
                Save preferences
            </button>
        </form>
    </AccountPageLayout>
</template>
