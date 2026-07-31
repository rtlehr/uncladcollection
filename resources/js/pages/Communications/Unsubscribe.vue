<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    category: { key: string; label: string; description: string };
    maskedEmail: string;
    unsubscribeUrl: string;
    preferencesUrl: string;
}>();

const form = useForm({});
</script>

<template>
    <Head title="Unsubscribe" />
    <main class="mx-auto flex min-h-[70vh] max-w-2xl items-center px-6 py-16">
        <section class="w-full rounded-3xl border border-stone-200 bg-white p-8 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--brand-accent)]">Email preferences</p>
            <h1 class="mt-3 text-3xl font-semibold">Stop {{ category.label.toLowerCase() }} emails?</h1>
            <p class="mt-4 text-stone-600 dark:text-stone-300">{{ category.description }}</p>
            <p class="mt-3 text-sm text-stone-500">This will update the preference for {{ maskedEmail }}. Required purchase, license, support, and security emails will continue.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <form @submit.prevent="form.post(unsubscribeUrl)">
                    <button type="submit" class="rounded-xl bg-[var(--brand-primary)] px-5 py-3 font-medium text-white disabled:opacity-50" :disabled="form.processing">
                        Unsubscribe from these emails
                    </button>
                </form>
                <Link :href="preferencesUrl" class="rounded-xl border border-stone-300 px-5 py-3 font-medium dark:border-stone-700">Manage all preferences</Link>
            </div>
        </section>
    </main>
</template>
