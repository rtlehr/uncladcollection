<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    sessionId: string;
}>();
</script>

<template>
    <Head title="Payment Successful" />

    <div class="mx-auto max-w-2xl p-8">
        <div class="rounded-lg border bg-card p-8 text-center">
            <h1 class="text-3xl font-semibold">
                Payment Received
            </h1>

            <p class="mt-4 text-muted-foreground">
                Your payment has been successfully processed.
            </p>

            <p class="mt-2 text-sm text-muted-foreground">
                Your purchase will be activated shortly.
            </p>

            <div class="mt-6">
                <Link
                    href="/purchases"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    View My Purchases
                </Link>
            </div>
        </div>
    </div>
</template>