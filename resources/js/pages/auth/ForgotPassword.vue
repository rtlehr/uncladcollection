```vue
<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    KeyRound,
    Mail,
    ShieldCheck,
} from '@lucide/vue';

import InputError from '@/components/InputError.vue';
import ContentSection from '@/components/Public/ContentSection.vue';
import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Forgot password' },
];
</script>

<template>
    <PublicSeoHead
        title="Forgot password"
        description="Request a secure password reset link for your Unclad Collection account."
        canonical-path="/forgot-password"
        noindex
    />

    <StructuredData
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Forgot password', url: '/forgot-password' },
        ]"
    />

    <PublicPageLayout>
        <section
            class="relative overflow-hidden border-b border-stone-200 bg-stone-50 dark:border-stone-800 dark:bg-stone-950"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,color-mix(in_srgb,var(--brand-secondary)_22%,transparent),transparent_45%),radial-gradient(circle_at_bottom_right,color-mix(in_srgb,var(--brand-primary)_16%,transparent),transparent_48%)]"
                aria-hidden="true"
            />

            <div
                class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16"
            >
                <PublicBreadcrumbs :items="breadcrumbs" />

                <div
                    class="mt-8 grid items-stretch gap-8 lg:grid-cols-[minmax(0,1fr)_460px] lg:gap-12"
                >
                    <div
                        class="flex flex-col justify-center rounded-3xl border border-stone-200/80 bg-white/75 p-7 shadow-sm backdrop-blur-sm dark:border-stone-800 dark:bg-stone-900/75 sm:p-10 lg:p-12"
                    >
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
                        >
                            Account recovery
                        </p>

                        <h1
                            class="mt-4 text-4xl font-semibold tracking-tight text-stone-950 dark:text-white sm:text-5xl"
                        >
                            Reset your password
                        </h1>

                        <p
                            class="mt-5 max-w-xl text-lg leading-8 text-stone-600 dark:text-stone-300"
                        >
                            Enter the email address associated with your
                            account. We’ll send you a secure link so you can
                            choose a new password.
                        </p>

                        <div
                            class="mt-8 rounded-2xl border border-stone-200 bg-white/80 p-5 dark:border-stone-800 dark:bg-stone-950/60"
                        >
                            <div class="flex items-start gap-3">
                                <Mail
                                    class="mt-0.5 size-5 shrink-0 text-[var(--brand-accent)]"
                                    aria-hidden="true"
                                />

                                <div>
                                    <h2
                                        class="text-sm font-semibold text-stone-950 dark:text-white"
                                    >
                                        Check your inbox
                                    </h2>

                                    <p
                                        class="mt-1 text-sm leading-6 text-stone-600 dark:text-stone-400"
                                    >
                                        The reset link will be sent only when
                                        the email address matches an existing
                                        account.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-6 flex items-start gap-3 border-t border-stone-200 pt-6 text-sm leading-6 text-stone-600 dark:border-stone-800 dark:text-stone-400"
                        >
                            <ShieldCheck
                                class="mt-0.5 size-5 shrink-0 text-[var(--brand-primary)]"
                                aria-hidden="true"
                            />

                            <p>
                                Password reset links are time-limited and can
                                only be used to update the account associated
                                with the requested email address.
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-3xl border border-stone-200 bg-white p-6 shadow-xl shadow-stone-950/5 dark:border-stone-800 dark:bg-stone-900 sm:p-8"
                    >
                        <div class="mb-7">
                            <div
                                class="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-[color-mix(in_srgb,var(--brand-primary)_12%,white)] text-[var(--brand-primary)] dark:bg-[color-mix(in_srgb,var(--brand-primary)_22%,black)]"
                            >
                                <KeyRound class="size-6" aria-hidden="true" />
                            </div>

                            <h2
                                class="text-2xl font-semibold tracking-tight text-stone-950 dark:text-white"
                            >
                                Request a reset link
                            </h2>

                            <p
                                class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400"
                            >
                                Enter the email address used to create your
                                account.
                            </p>
                        </div>

                        <div
                            v-if="status"
                            role="status"
                            class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900/70 dark:bg-green-950/40 dark:text-green-300"
                        >
                            {{ status }}
                        </div>

                        <Form
                            v-bind="email.form()"
                            v-slot="{ errors, processing }"
                            class="grid gap-6"
                        >
                            <div class="grid gap-2">
                                <Label for="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autocomplete="email"
                                    autofocus
                                    placeholder="email@example.com"
                                    class="h-12 rounded-xl"
                                />

                                <InputError :message="errors.email" />
                            </div>

                            <Button
                                type="submit"
                                class="h-12 w-full rounded-full bg-[var(--brand-primary)] text-sm font-semibold text-white hover:bg-[color-mix(in_srgb,var(--brand-primary)_88%,black)]"
                                :disabled="processing"
                                :aria-busy="processing"
                                data-test="email-password-reset-link-button"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="mr-2"
                                    aria-hidden="true"
                                />

                                <span>
                                    {{
                                        processing
                                            ? 'Sending reset link...'
                                            : 'Email password reset link'
                                    }}
                                </span>

                                <ArrowRight
                                    v-if="!processing"
                                    class="ml-2 size-4"
                                    aria-hidden="true"
                                />
                            </Button>
                        </Form>

                        <div
                            class="mt-7 border-t border-stone-200 pt-6 text-center dark:border-stone-800"
                        >
                            <Link
                                :href="login()"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)] transition hover:opacity-80"
                            >
                                <ArrowLeft
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                Return to log in
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <ContentSection
            eyebrow="Still having trouble?"
            title="Make sure you use the right email address"
            description="Try the email address connected to your purchases, comments, or previous Unclad Collection account activity."
            narrow
        >
            <div class="flex justify-center">
                <a
                    href="mailto:info@uncladcollection.com"
                    class="inline-flex h-12 items-center justify-center rounded-full border border-stone-300 bg-white px-6 text-sm font-semibold text-stone-900 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700 dark:bg-stone-900 dark:text-white"
                >
                    Contact support
                    <ArrowRight class="ml-2 size-4" aria-hidden="true" />
                </a>
            </div>
        </ContentSection>
    </PublicPageLayout>
</template>
```
