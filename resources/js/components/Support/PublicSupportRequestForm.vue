<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    mode: 'guest' | 'member';
    categories: Array<{
        id: number;
        name: string;
        description?: string | null;
    }>;
    initialCategoryId?: number | null;
    attachmentRules: {
        max_kb: number;
        extensions: string[];
    };
}>();

const form = useForm({
    guest_name: '',
    guest_email: '',
    category_id: props.initialCategoryId
        ? String(props.initialCategoryId)
        : '',
    subject: '',
    description: '',
    attachments: [] as File[],
});

function submit(): void {
    form.post(
        props.mode === 'guest' ? '/support' : '/support/tickets',
        {
            forceFormData: true,
            preserveScroll: true,
        },
    );
}

function handleAttachments(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.attachments = Array.from(input.files ?? []);
}

function selectCategory(categoryId: number): void {
    const allowed = props.categories.some(
        (category) => category.id === categoryId,
    );

    if (allowed) {
        form.category_id = String(categoryId);
    }
}

defineExpose({
    selectCategory,
});
</script>

<template>
    <form
        class="space-y-6 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 sm:p-8"
        @submit.prevent="submit"
    >
        <div v-if="mode === 'guest'" class="grid gap-5 sm:grid-cols-2">
            <div>
                <Label for="guest_name">Name</Label>
                <Input
                    id="guest_name"
                    v-model="form.guest_name"
                    class="mt-2"
                    required
                />
                <InputError :message="form.errors.guest_name" />
            </div>

            <div>
                <Label for="guest_email">Email</Label>
                <Input
                    id="guest_email"
                    v-model="form.guest_email"
                    type="email"
                    class="mt-2"
                    required
                />
                <InputError :message="form.errors.guest_email" />
            </div>
        </div>

        <div>
            <Label for="category">What do you need help with?</Label>
            <select
                id="category"
                v-model="form.category_id"
                class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="">Choose a support category</option>
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="String(category.id)"
                >
                    {{ category.name }}
                </option>
            </select>
            <p
                v-if="categories.find((category) => String(category.id) === form.category_id)?.description"
                class="mt-2 text-sm text-muted-foreground"
            >
                {{ categories.find((category) => String(category.id) === form.category_id)?.description }}
            </p>
            <InputError :message="form.errors.category_id" />
        </div>

        <div>
            <Label for="subject">Subject</Label>
            <Input
                id="subject"
                v-model="form.subject"
                class="mt-2"
                maxlength="180"
                required
            />
            <InputError :message="form.errors.subject" />
        </div>

        <div>
            <Label for="description">Tell us what happened</Label>
            <Textarea
                id="description"
                v-model="form.description"
                class="mt-2 min-h-48"
                placeholder="Include what you were trying to do, what happened, and any error message you saw."
                required
            />
            <InputError :message="form.errors.description" />
        </div>

        <div>
            <Label for="attachments">Attachments</Label>
            <Input
                id="attachments"
                type="file"
                multiple
                class="mt-2"
                @change="handleAttachments"
            />
            <p class="mt-2 text-xs leading-5 text-muted-foreground">
                Up to 5 files,
                {{ Math.round(attachmentRules.max_kb / 1024) }} MB each.
                Accepted: {{ attachmentRules.extensions.join(', ') }}.
            </p>
            <InputError :message="form.errors.attachments" />
        </div>

        <div class="flex flex-wrap gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Submitting…' : 'Submit request' }}
            </Button>

            <Button v-if="mode === 'member'" as-child variant="outline">
                <Link href="/support/tickets">View my tickets</Link>
            </Button>
        </div>
    </form>
</template>
