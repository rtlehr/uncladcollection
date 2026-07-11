<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SettingsSaveButton from '@/components/settings/SettingsSaveButton.vue';
import SettingsSection from '@/components/settings/SettingsSection.vue';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-8">
        <SettingsSection
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure."
        >
            <Form
                v-bind="SecurityController.update.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="current_password">
                        Current password
                    </Label>

                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        placeholder="Current password"
                        :aria-invalid="Boolean(errors.current_password) || undefined"
                        :aria-describedby="
                            errors.current_password
                                ? 'current-password-error'
                                : undefined
                        "
                    />

                    <InputError
                        id="current-password-error"
                        :message="errors.current_password"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="password">
                        New password
                    </Label>

                    <PasswordInput
                        id="password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="New password"
                        :passwordrules="props.passwordRules"
                        :aria-invalid="Boolean(errors.password) || undefined"
                        :aria-describedby="
                            errors.password
                                ? 'password-error'
                                : undefined
                        "
                    />

                    <InputError
                        id="password-error"
                        :message="errors.password"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">
                        Confirm password
                    </Label>

                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        :passwordrules="props.passwordRules"
                        :aria-invalid="
                            Boolean(errors.password_confirmation)
                            || undefined
                        "
                        :aria-describedby="
                            errors.password_confirmation
                                ? 'password-confirmation-error'
                                : undefined
                        "
                    />

                    <InputError
                        id="password-confirmation-error"
                        :message="errors.password_confirmation"
                    />
                </div>

                <SettingsSaveButton
                    :processing="processing"
                    processing-label="Saving password..."
                    test-id="update-password-button"
                />
            </Form>
        </SettingsSection>

        <ManageTwoFactor
            :canManageTwoFactor="canManageTwoFactor"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />

        <ManagePasskeys
            :canManagePasskeys="canManagePasskeys"
            :passkeys="passkeys"
        />
    </div>
</template>
