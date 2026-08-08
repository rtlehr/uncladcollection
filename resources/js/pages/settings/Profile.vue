<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import ReadOnlySetting from '@/components/settings/ReadOnlySetting.vue';
import SettingsSaveButton from '@/components/settings/SettingsSaveButton.vue';
import SettingsSection from '@/components/settings/SettingsSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <SettingsSection
            title="Profile"
            description="Update your name, username, and email address."
        >

        <Form
            v-bind="ProfileController.update.form()"
            :options="{ forceFormData: true }"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="username">Username</Label>
                <Input
                    id="username"
                    class="mt-1 block w-full"
                    name="username"
                    :default-value="user.username"
                    required
                    autocomplete="nickname"
                    placeholder="Public username"
                />
                <p class="text-xs text-muted-foreground">
                    This is the name shown on comments and public activity.
                </p>
                <InputError class="mt-2" :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="avatar">Profile Icon</Label>

                <div class="flex items-center gap-4">
                    <img
                        v-if="user.avatar_url"
                        :src="user.avatar_url"
                        :alt="user.name"
                        class="h-16 w-16 rounded-full object-cover border"
                    />

                    <div
                        v-else
                        class="flex h-16 w-16 items-center justify-center rounded-full border bg-muted text-lg font-semibold"
                    >
                        {{ user.name?.charAt(0) ?? '?' }}
                    </div>

                    <Input
                        id="avatar"
                        name="avatar"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                    />
                </div>

                <p id="avatar-help" class="text-xs text-muted-foreground">
                    Recommended: square image, 256×256 or larger. JPG, PNG, or WebP.
                </p>

                <InputError class="mt-2" :message="errors.avatar" />
            </div>

            <ReadOnlySetting
                label="Member Role"
                :value="user.roles"
                empty-label="No role assigned"
                description="Roles are managed by site administrators."
            />

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                    role="status"
                    aria-live="polite"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <SettingsSection
                title="Author Profile"
                description="Optional information shown on your public blog articles."
                divided
            >

            <div class="grid gap-2">
                <Label for="author_title">Author Title</Label>
                <Input
                    id="author_title"
                    name="author_title"
                    :default-value="user.author_title"
                    placeholder="Photographer, Writer, Contributor..."
                />
                <InputError class="mt-2" :message="errors.author_title" />
            </div>

            <div class="grid gap-2">
                <Label for="author_bio">Author Bio</Label>
                <textarea
                    id="author_bio"
                    name="author_bio"
                    class="min-h-32 rounded-md border bg-background px-3 py-2 text-sm"
                    :default-value="user.author_bio"
                    placeholder="Write a short bio for your blog author profile..."
                />
                <InputError class="mt-2" :message="errors.author_bio" />
            </div>

            <div class="grid gap-2">
                <Label for="author_website_url">Author Website URL</Label>
                <Input
                    id="author_website_url"
                    name="author_website_url"
                    type="url"
                    :default-value="user.author_website_url"
                    placeholder="https://example.com"
                />
                <InputError class="mt-2" :message="errors.author_website_url" />
            </div>

            </SettingsSection>

            <SettingsSaveButton
                :processing="processing"
                processing-label="Saving profile..."
                test-id="update-profile-button"
            />
        </Form>

        </SettingsSection>
    </div>

    <DeleteUser />
</template>
