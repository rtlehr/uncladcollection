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
    LockKeyhole,
    ShieldCheck,
} from '@lucide/vue';
import { ref } from 'vue';
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
import { update } from '@/routes/password';


















const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Reset password' },
];
</script>

<template>
    <PublicSeoHead
        title="Reset password"
        description="Choose a new password for your Unclad Collection account."
        canonical-path="/reset-password"
        noindex
    />

    <StructuredData
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Reset password', url: '/reset-password' },
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
                            Secure account recovery
                        </p>

                        <h1
                            class="mt-4 text-4xl font-semibold tracking-tight text-stone-950 dark:text-white sm:text-5xl"
                        >
                            Choose a new password
                        </h1>

                        <p
                            class="mt-5 max-w-xl text-lg leading-8 text-stone-600 dark:text-stone-300"
                        >
                            Create a new password for your account. Once saved,
                            you can return to the login page and continue using
                            Unclad Collection.
                        </p>

                        <div
                            class="mt-8 rounded-2xl border border-stone-200 bg-white/80 p-5 dark:border-stone-800 dark:bg-stone-950/60"
                        >
                            <div class="flex items-start gap-3">
                                <LockKeyhole
                                    class="mt-0.5 size-5 shrink-0 text-[var(--brand-accent)]"
                                    aria-hidden="true"
                                />

                                <div>
                                    <h2
                                        class="text-sm font-semibold text-stone-950 dark:text-white"
                                    >
                                        Use a strong, unique password
                                    </h2>

                                    <p
                                        class="mt-1 text-sm leading-6 text-stone-600 dark:text-stone-400"
                                    >
                                        Avoid reusing a password from another
                                        website or account.
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
                                The reset token is securely submitted with this
                                form and is valid only for the account shown.
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
                                Reset your password
                            </h2>

                            <p
                                class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400"
                            >
                                Enter and confirm the new password you want to
                                use.
                            </p>
                        </div>

                        <Form
                            v-bind="update.form()"
                            :transform="
                                (data) => ({
                                    ...data,
                                    token,
                                    email: inputEmail,
                                })
                            "
                            :reset-on-success="[
                                'password',
                                'password_confirmation',
                            ]"
                            v-slot="{ errors, processing }"
                            class="grid gap-6"
                        >
                            <div class="grid gap-2">
                                <Label for="email">Email address</Label>

                                <Input
                                    id="email"
                                    v-model="inputEmail"
                                    type="email"
                                    name="email"
                                    autocomplete="email"
                                    readonly
                                    class="h-12 rounded-xl bg-stone-50 text-stone-600 dark:bg-stone-950 dark:text-stone-400"
                                />

                                <p
                                    class="text-xs leading-5 text-stone-500 dark:text-stone-400"
                                >
                                    This password reset applies to the account
                                    associated with this email address.
                                </p>

                                <InputError :message="errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password">New password</Label>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    autofocus
                                    placeholder="New password"
                                    :passwordrules="passwordRules"
                                    class="h-12 rounded-xl"
                                />

                                <InputError :message="errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation">
                                    Confirm new password
                                </Label>

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Confirm new password"
                                    :passwordrules="passwordRules"
                                    class="h-12 rounded-xl"
                                />

                                <InputError
                                    :message="errors.password_confirmation"
                                />
                            </div>

                            <Button
                                type="submit"
                                class="h-12 w-full rounded-full bg-[var(--brand-primary)] text-sm font-semibold text-white hover:bg-[color-mix(in_srgb,var(--brand-primary)_88%,black)]"
                                :disabled="processing"
                                :aria-busy="processing"
                                data-test="reset-password-button"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="mr-2"
                                    aria-hidden="true"
                                />

                                <span>
                                    {{
                                        processing
                                            ? 'Resetting password...'
                                            : 'Reset password'
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
            eyebrow="After resetting"
            title="Return to your account"
            description="Use your new password to log in and access favorites, purchases, licenses, and downloads."
            narrow
        >
            <div class="flex justify-center">
                <Link
                    :href="login()"
                    class="inline-flex h-12 items-center justify-center rounded-full border border-stone-300 bg-white px-6 text-sm font-semibold text-stone-900 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700 dark:bg-stone-900 dark:text-white"
                >
                    Go to login
                    <ArrowRight class="ml-2 size-4" aria-hidden="true" />
                </Link>
            </div>
        </ContentSection>
    </PublicPageLayout>
</template>
```
