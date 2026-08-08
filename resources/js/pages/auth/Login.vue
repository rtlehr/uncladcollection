```vue
<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, LockKeyhole, ShieldCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import ContentSection from '@/components/Public/ContentSection.vue';
import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';




















defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Log in' },
];

const accountBenefits = [
    'Save your favorite assets',
    'Purchase licensed downloads',
    'Access your purchase history',
    'Manage your account and downloads',
];
</script>

<template>
    <PublicSeoHead
        title="Log in"
        description="Log in to your Unclad Collection account to access favorites, purchases, licensed downloads, and account tools."
        canonical-path="/login"
        noindex
    />

    <StructuredData
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Log in', url: '/login' },
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
                        <div class="max-w-2xl">
                            <p
                                class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
                            >
                                Welcome back
                            </p>

                            <h1
                                class="mt-4 text-4xl font-semibold tracking-tight text-stone-950 dark:text-white sm:text-5xl"
                            >
                                Log in to your account
                            </h1>

                            <p
                                class="mt-5 max-w-xl text-lg leading-8 text-stone-600 dark:text-stone-300"
                            >
                                Continue exploring Unclad Collection, access your
                                licensed downloads, and manage the content you
                                have saved.
                            </p>
                        </div>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="benefit in accountBenefits"
                                :key="benefit"
                                class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-white/80 p-4 dark:border-stone-800 dark:bg-stone-950/60"
                            >
                                <CheckCircle2
                                    class="mt-0.5 size-5 shrink-0 text-[var(--brand-accent)]"
                                    aria-hidden="true"
                                />

                                <span
                                    class="text-sm font-medium leading-6 text-stone-700 dark:text-stone-300"
                                >
                                    {{ benefit }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-8 flex items-start gap-3 border-t border-stone-200 pt-6 text-sm leading-6 text-stone-600 dark:border-stone-800 dark:text-stone-400"
                        >
                            <ShieldCheck
                                class="mt-0.5 size-5 shrink-0 text-[var(--brand-primary)]"
                                aria-hidden="true"
                            />

                            <p>
                                Your account information and purchase history
                                are protected through secure authentication.
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
                                <LockKeyhole
                                    class="size-6"
                                    aria-hidden="true"
                                />
                            </div>

                            <h2
                                class="text-2xl font-semibold tracking-tight text-stone-950 dark:text-white"
                            >
                                Account login
                            </h2>

                            <p
                                class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400"
                            >
                                Enter your email address and password below.
                            </p>
                        </div>

                        <div
                            v-if="status"
                            role="status"
                            class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900/70 dark:bg-green-950/40 dark:text-green-300"
                        >
                            {{ status }}
                        </div>

                        <div class="mb-6">
                            <PasskeyVerify />
                        </div>

                        <Form
                            v-bind="store.form()"
                            :reset-on-success="['password']"
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
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="email"
                                    placeholder="email@example.com"
                                    class="h-12 rounded-xl"
                                />

                                <InputError :message="errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <div class="flex items-center justify-between gap-4">
                                    <Label for="password">Password</Label>

                                    <Link
                                        v-if="canResetPassword"
                                        :href="request()"
                                        class="text-sm font-semibold text-[var(--brand-accent)] transition hover:opacity-80"
                                        :tabindex="5"
                                    >
                                        Forgot your password?
                                    </Link>
                                </div>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    :tabindex="2"
                                    autocomplete="current-password"
                                    placeholder="Password"
                                    class="h-12 rounded-xl"
                                />

                                <InputError :message="errors.password" />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label
                                    for="remember"
                                    class="flex cursor-pointer items-center gap-3 text-sm font-normal text-stone-700 dark:text-stone-300"
                                >
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        :tabindex="3"
                                    />

                                    <span>Remember me</span>
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                class="h-12 w-full rounded-full bg-[var(--brand-primary)] text-sm font-semibold text-white hover:bg-[color-mix(in_srgb,var(--brand-primary)_88%,black)]"
                                :tabindex="4"
                                :disabled="processing"
                                :aria-busy="processing"
                                data-test="login-button"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="mr-2"
                                    aria-hidden="true"
                                />

                                <span>
                                    {{ processing ? 'Logging in...' : 'Log in' }}
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
                            <p
                                class="text-sm text-stone-600 dark:text-stone-400"
                            >
                                Don’t have an account?
                                <Link
                                    :href="register()"
                                    :tabindex="6"
                                    class="font-semibold text-[var(--brand-accent)] transition hover:opacity-80"
                                >
                                    Create an account
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <ContentSection
            eyebrow="New to Unclad Collection?"
            title="Build your personal digital library"
            description="Create a free account to save favorites, purchase licensed files, and keep your downloads organized in one place."
            narrow
        >
            <div class="flex justify-center">
                <Link
                    :href="register()"
                    class="inline-flex h-12 items-center justify-center rounded-full border border-stone-300 bg-white px-6 text-sm font-semibold text-stone-900 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700 dark:bg-stone-900 dark:text-white"
                >
                    Create an account
                    <ArrowRight class="ml-2 size-4" aria-hidden="true" />
                </Link>
            </div>
        </ContentSection>
    </PublicPageLayout>
</template>
```
