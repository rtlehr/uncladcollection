<script setup lang="ts">
import {
  ArrowDown,
  CheckCircle2,
  CreditCard,
  Download,
  ShieldCheck,
} from "@lucide/vue";
import type { PublicAssetOffering } from "@/types/publicAsset";

const props = defineProps<{
  offerings: PublicAssetOffering[];
  formats: string[];
}>();

function money(cents: number, currency: string): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
  }).format(cents / 100);
}

function scrollToPurchase(): void {
  document.getElementById("purchase")?.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}
</script>

<template>
  <section
    class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900"
  >
    <p
      class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
    >
      Choose your license
    </p>
    <h2 class="mt-2 text-2xl font-semibold">Ready when you are</h2>
    <p class="mt-2 text-sm leading-6 text-stone-500">
      Compare packages, configure your purchase, and see your exact total before
      adding anything to the cart.
    </p>

    <div
      v-if="offerings.length"
      class="mt-5 rounded-2xl bg-stone-100 p-4 dark:bg-stone-800/70"
    >
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">
        Starting at
      </p>
      <p class="mt-1 text-3xl font-bold">
        {{
          money(
            Math.min(...offerings.map((offering) => offering.price_cents)),
            offerings[0].currency,
          )
        }}
      </p>
      <p class="mt-1 text-sm text-stone-500">
        {{ offerings.length }} license package{{
          offerings.length === 1 ? "" : "s"
        }}
        · {{ formats.length }} format{{ formats.length === 1 ? "" : "s" }}
      </p>
    </div>

    <button
      type="button"
      class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-primary)] focus-visible:ring-offset-2"
      @click="scrollToPurchase"
    >
      Configure your purchase
      <ArrowDown class="h-4 w-4" />
    </button>

    <div class="mt-5 grid gap-3 text-sm text-stone-600 dark:text-stone-300">
      <div class="flex items-center gap-3">
        <ShieldCheck class="h-4 w-4 text-emerald-600" />
        Secure Stripe checkout
      </div>
      <div class="flex items-center gap-3">
        <Download class="h-4 w-4 text-emerald-600" />
        Instant digital delivery after payment
      </div>
      <div class="flex items-center gap-3">
        <CreditCard class="h-4 w-4 text-emerald-600" />
        Clear pricing before checkout
      </div>
      <div class="flex items-center gap-3">
        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
        License and files recorded with your order
      </div>
    </div>
  </section>
</template>
