<script setup lang="ts">
import { BadgeCheck, CreditCard, Download, RefreshCcw } from "@lucide/vue";
</script>

<template>
  <section
    class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-stone-800 dark:bg-stone-900/50"
  >
    <p
      class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
    >
      Purchase with confidence
    </p>
    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="flex gap-3">
        <CreditCard
          class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]"
        />
        <div>
          <h3 class="font-semibold">Secure payment</h3>
          <p class="mt-1 text-sm text-stone-500">
            Checkout is handled securely through Stripe.
          </p>
        </div>
      </div>
      <div class="flex gap-3">
        <Download class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]" />
        <div>
          <h3 class="font-semibold">Instant access</h3>
          <p class="mt-1 text-sm text-stone-500">
            Eligible digital files are available after payment.
          </p>
        </div>
      </div>
      <div class="flex gap-3">
        <BadgeCheck
          class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]"
        />
        <div>
          <h3 class="font-semibold">License recorded</h3>
          <p class="mt-1 text-sm text-stone-500">
            Your exact package and terms are saved with the order.
          </p>
        </div>
      </div>
      <div class="flex gap-3">
        <RefreshCcw
          class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-primary)]"
        />
        <div>
          <h3 class="font-semibold">My Library</h3>
          <p class="mt-1 text-sm text-stone-500">
            Review purchases and available downloads from your account.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
