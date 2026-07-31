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

const props = defineProps<{ categories: NotificationCategory[] }>();
const form = useForm({
    preferences: props.categories.map((item) => ({
        category: item.key,
        in_app_enabled: item.in_app_enabled,
        email_enabled: item.email_enabled,
        email_frequency: item.email_frequency,
    })),
});

function submit(): void {
    form.put('/account/notification-preferences', { preserveScroll: true });
}
</script>

<template>
    <Head title="Communication Preferences" />
    <AccountPageLayout>
        <template #title>Communication Preferences</template>
        <template #description>Control optional alerts and emails. Required service communications remain enabled so we can complete purchases, deliver licenses, answer support requests, and protect your account.</template>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                <h2 class="font-semibold">Required service communications</h2>
                <p class="mt-1">Email cannot be disabled for transactions, fulfillment, licenses, downloads, and security. These are not marketing subscriptions.</p>
            </section>

            <section v-for="(category, index) in categories" :key="category.key" class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold">{{ category.label }}</h2>
                            <span v-if="category.transactional" class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700 dark:bg-stone-800 dark:text-stone-200">Required email</span>
                            <span v-else class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">Optional</span>
                        </div>
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">{{ category.description }}</p>
                    </div>

                    <div class="grid min-w-56 gap-3 text-sm">
                        <label class="flex items-center justify-between gap-5 rounded-xl border border-stone-200 px-4 py-3 dark:border-stone-700">
                            <span>In-app alerts</span>
                            <input v-model="form.preferences[index].in_app_enabled" type="checkbox" class="h-4 w-4" />
                        </label>
                        <label class="flex items-center justify-between gap-5 rounded-xl border border-stone-200 px-4 py-3 dark:border-stone-700" :class="{ 'opacity-70': category.transactional }">
                            <span>Email</span>
                            <input v-model="form.preferences[index].email_enabled" type="checkbox" class="h-4 w-4" :disabled="category.transactional" />
                        </label>
                        <p v-if="category.transactional" class="text-xs text-stone-500">Required to provide this service.</p>
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-xl bg-[var(--brand-primary)] px-5 py-3 font-medium text-white disabled:opacity-50" :disabled="form.processing">Save preferences</button>
                <p v-if="form.recentlySuccessful" class="text-sm text-emerald-700 dark:text-emerald-300">Preferences saved.</p>
            </div>
        </form>
    </AccountPageLayout>
</template>
