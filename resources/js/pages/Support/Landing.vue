<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import { Button } from '@/components/ui/button';
defineOptions({ layout: PublicPageLayout });
</script>
<template>
<Head title="Support" />
<section class="mx-auto max-w-5xl px-4 py-16 sm:px-8">
  <div class="rounded-3xl border bg-white p-8 shadow-sm dark:bg-stone-900 sm:p-12">
    <p class="text-sm font-semibold uppercase tracking-widest text-[var(--brand-accent)]">Unclad Collection Support</p>
    <h1 class="mt-3 text-4xl font-semibold tracking-tight">How can we help?</h1>
    <p class="mt-4 max-w-2xl text-lg text-muted-foreground">Send a support request for account, purchase, licensing, download, asset, advertising, or general questions.</p>
    <div class="mt-8 flex flex-wrap gap-3">
      <Button as-child><Link href="/support/create">Submit a request</Link></Button>
      <Button as-child variant="outline"><Link href="/login">Sign in to track requests</Link></Button>
    </div>
  </div>
</section>
</template>
