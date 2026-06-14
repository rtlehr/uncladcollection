<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Payment Cancelled" />

    <div class="mx-auto max-w-2xl p-8">
        <div class="rounded-lg border bg-card p-8 text-center">
            <h1 class="text-3xl font-semibold">
                Payment Cancelled
            </h1>

            <p class="mt-4 text-muted-foreground">
                Your payment was cancelled and no charges were made.
            </p>

            <div class="mt-6">
                <Link
                    href="/images"
                    class="rounded-md border px-4 py-2 hover:bg-muted"
                >
                    Continue Browsing
                </Link>
            </div>
        </div>
    </div>
</template>