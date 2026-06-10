<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const form = useForm({
    name: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

function submit() {
    form.post('/admin/collections');
}
</script>

<template>
    <Head title="Create Collection" />

    <div class="max-w-3xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Create Collection</h1>
            <p class="text-sm text-muted-foreground">
                Create a new image collection.
            </p>
        </div>

        <form
            class="space-y-6 rounded-lg border bg-card p-6"
            @submit.prevent="submit"
        >
            <div class="space-y-2">
                <label class="text-sm font-medium">Name</label>

                <Input
                    v-model="form.name"
                    placeholder="Enter collection name"
                />

                <p v-if="form.errors.name" class="text-sm text-red-600">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">Description</label>

                <textarea
                    v-model="form.description"
                    rows="4"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Optional description"
                />

                <p v-if="form.errors.description" class="text-sm text-red-600">
                    {{ form.errors.description }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">Sort Order</label>

                <Input
                    v-model="form.sort_order"
                    type="number"
                    min="0"
                />

                <p v-if="form.errors.sort_order" class="text-sm text-red-600">
                    {{ form.errors.sort_order }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium">
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 rounded border-input"
                />

                Active
            </label>

            <p v-if="form.errors.is_active" class="text-sm text-red-600">
                {{ form.errors.is_active }}
            </p>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    Create Collection
                </Button>

                <Button type="button" variant="outline" as-child>
                    <Link href="/admin/collections">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>