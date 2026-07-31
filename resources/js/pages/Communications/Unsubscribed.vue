<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
    <Head title="Email preference updated" />
    <main class="mx-auto flex min-h-[70vh] max-w-2xl items-center px-6 py-16">
        <section class="w-full rounded-3xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <h1 class="text-3xl font-semibold">Your email preference was updated</h1>
            <p class="mt-4 text-stone-600 dark:text-stone-300">You will no longer receive that optional email category. Required service and security messages are unaffected.</p>
            <Link href="/" class="mt-8 inline-flex rounded-xl bg-[var(--brand-primary)] px-5 py-3 font-medium text-white">Return to Unclad Collection</Link>
        </section>
    </main>
</template>
