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
    ArrowRight,
    CheckCircle2,
    ShieldCheck,
    Sparkles,
    UserPlus,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { store } from '@/routes/register';

















defineProps<{
    passwordRules: string;
}>();

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Create an account' },
];

const membershipBenefits = [
    'Save favorite assets and articles',
    'Purchase licensed digital downloads',
    'Access your complete purchase history',
    'Participate in community discussions',
];
</script>

<template>
    <PublicSeoHead
        title="Create an account"
        description="Create an Unclad Collection account to save favorites, purchase licensed downloads, and participate in the community."
        canonical-path="/register"
        noindex
    />

    <StructuredData
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Create an account', url: '/register' },
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
                    class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_500px] lg:gap-12"
                >
                    <div
                        class="flex flex-col justify-center rounded-3xl border border-stone-200/80 bg-white/75 p-7 shadow-sm backdrop-blur-sm dark:border-stone-800 dark:bg-stone-900/75 sm:p-10 lg:sticky lg:top-24 lg:p-12"
                    >
                        <div class="max-w-2xl">
                            <p
                                class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
                            >
                                Join the collection
                            </p>

                            <h1
                                class="mt-4 text-4xl font-semibold tracking-tight text-stone-950 dark:text-white sm:text-5xl"
                            >
                                Create your account
                            </h1>

                            <p
                                class="mt-5 max-w-xl text-lg leading-8 text-stone-600 dark:text-stone-300"
                            >
                                Build your personal library, save the content
                                that inspires you, and access licensed digital
                                downloads from one secure account.
                            </p>
                        </div>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="benefit in membershipBenefits"
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
                                Your private account information remains
                                separate from the public username shown in
                                comments and community activity.
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
                                <UserPlus class="size-6" aria-hidden="true" />
                            </div>

                            <h2
                                class="text-2xl font-semibold tracking-tight text-stone-950 dark:text-white"
                            >
                                Account details
                            </h2>

                            <p
                                class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400"
                            >
                                Enter your information below to create your
                                Unclad Collection account.
                            </p>
                        </div>

                        <Form
                            v-bind="store.form()"
                            :reset-on-success="[
                                'password',
                                'password_confirmation',
                            ]"
                            v-slot="{ errors, processing }"
                            class="grid gap-6"
                        >
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>

                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="name"
                                    name="name"
                                    placeholder="Full name"
                                    class="h-12 rounded-xl"
                                />

                                <p
                                    class="text-xs leading-5 text-stone-500 dark:text-stone-400"
                                >
                                    Used for your account and purchase records.
                                </p>

                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="username">Username</Label>

                                <Input
                                    id="username"
                                    type="text"
                                    required
                                    :tabindex="2"
                                    autocomplete="nickname"
                                    name="username"
                                    placeholder="Public username"
                                    class="h-12 rounded-xl"
                                />

                                <p
                                    class="text-xs leading-5 text-stone-500 dark:text-stone-400"
                                >
                                    This public name will appear on comments and
                                    other community activity.
                                </p>

                                <InputError :message="errors.username" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    :tabindex="3"
                                    autocomplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                    class="h-12 rounded-xl"
                                />

                                <InputError :message="errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password">Password</Label>

                                <PasswordInput
                                    id="password"
                                    required
                                    :tabindex="4"
                                    autocomplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    :passwordrules="passwordRules"
                                    class="h-12 rounded-xl"
                                />

                                <InputError :message="errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation">
                                    Confirm password
                                </Label>

                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    :tabindex="5"
                                    autocomplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    :passwordrules="passwordRules"
                                    class="h-12 rounded-xl"
                                />

                                <InputError
                                    :message="errors.password_confirmation"
                                />
                            </div>

                            <div
                                class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-stone-800 dark:bg-stone-950/60"
                            >
                                <Sparkles
                                    class="mt-0.5 size-5 shrink-0 text-[var(--brand-accent)]"
                                    aria-hidden="true"
                                />

                                <p
                                    class="text-xs leading-5 text-stone-600 dark:text-stone-400"
                                >
                                    By creating an account, you agree to follow
                                    the site’s terms, licensing requirements,
                                    and community standards.
                                </p>
                            </div>

                            <Button
                                type="submit"
                                class="h-12 w-full rounded-full bg-[var(--brand-primary)] text-sm font-semibold text-white hover:bg-[color-mix(in_srgb,var(--brand-primary)_88%,black)]"
                                :tabindex="6"
                                :disabled="processing"
                                :aria-busy="processing"
                                data-test="register-user-button"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="mr-2"
                                    aria-hidden="true"
                                />

                                <span>
                                    {{
                                        processing
                                            ? 'Creating account...'
                                            : 'Create account'
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
                            <p
                                class="text-sm text-stone-600 dark:text-stone-400"
                            >
                                Already have an account?
                                <Link
                                    :href="login()"
                                    class="font-semibold text-[var(--brand-accent)] transition hover:opacity-80"
                                    :tabindex="7"
                                >
                                    Log in
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <ContentSection
            eyebrow="Your account"
            title="One place for everything you collect"
            description="Keep favorites, purchases, licenses, and downloads organized as your personal collection grows."
            narrow
        >
            <div class="flex justify-center">
                <Link
                    :href="login()"
                    class="inline-flex h-12 items-center justify-center rounded-full border border-stone-300 bg-white px-6 text-sm font-semibold text-stone-900 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700 dark:bg-stone-900 dark:text-white"
                >
                    Already registered? Log in
                    <ArrowRight class="ml-2 size-4" aria-hidden="true" />
                </Link>
            </div>
        </ContentSection>
    </PublicPageLayout>
</template>
```
