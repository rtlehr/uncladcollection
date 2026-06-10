<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const form = useForm({
    name: '',
    tag_type: 'image',
    description: '',
});

function submit() {
    form.post('/admin/tags');
}
</script>

<template>
    <Head title="Create Tag" />

    <div class="p-6 max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Create Tag</h1>
            <p class="text-sm text-muted-foreground">
                Create a new image or blog tag.
            </p>
        </div>

        <form
            class="space-y-6 rounded-lg border bg-card p-6"
            @submit.prevent="submit"
        >
            <div class="space-y-2">
                <label class="text-sm font-medium">
                    Name
                </label>

                <Input
                    v-model="form.name"
                    placeholder="Enter tag name"
                />

                <p
                    v-if="form.errors.name"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">
                    Type
                </label>

                <select
                    v-model="form.tag_type"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                    <option value="image">
                        Image
                    </option>

                    <option value="blog">
                        Blog
                    </option>
                </select>

                <p
                    v-if="form.errors.tag_type"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.tag_type }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">
                    Description
                </label>

                <textarea
                    v-model="form.description"
                    rows="4"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Optional description"
                />

                <p
                    v-if="form.errors.description"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.description }}
                </p>
            </div>

            <div class="flex gap-3">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    Create Tag
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    as-child
                >
                    <Link href="/admin/tags">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>