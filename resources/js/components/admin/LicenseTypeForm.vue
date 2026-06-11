<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Link } from '@inertiajs/vue3';

interface LicenseTypeForm {
    name: string;
    slug: string;
    description: string;
    price: string;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    max_resolution: string;
    usage_terms: string;
    is_active: boolean;
    sort_order: number;
}

defineProps<{
    form: LicenseTypeForm & {
        errors: Record<string, string>;
        processing: boolean;
    };
    submitLabel: string;
}>();

defineEmits<{
    submit: [];
}>();
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    placeholder="Personal Use"
                />
                <p v-if="form.errors.name" class="text-sm text-red-600">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    placeholder="personal-use"
                />
                <p class="text-xs text-muted-foreground">
                    Leave blank to auto-generate from the name.
                </p>
                <p v-if="form.errors.slug" class="text-sm text-red-600">
                    {{ form.errors.slug }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="price">Price</Label>
                <Input
                    id="price"
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="9.99"
                />
                <p v-if="form.errors.price" class="text-sm text-red-600">
                    {{ form.errors.price }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="currency">Currency</Label>
                <Input
                    id="currency"
                    v-model="form.currency"
                    maxlength="3"
                    placeholder="USD"
                />
                <p v-if="form.errors.currency" class="text-sm text-red-600">
                    {{ form.errors.currency }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="download_limit">Download Limit</Label>
                <Input
                    id="download_limit"
                    v-model.number="form.download_limit"
                    type="number"
                    min="1"
                    placeholder="Leave blank for unlimited"
                />
                <p
                    v-if="form.errors.download_limit"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.download_limit }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="expires_after_days">Expires After Days</Label>
                <Input
                    id="expires_after_days"
                    v-model.number="form.expires_after_days"
                    type="number"
                    min="1"
                    placeholder="Leave blank for no expiration"
                />
                <p
                    v-if="form.errors.expires_after_days"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.expires_after_days }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="max_resolution">Max Resolution</Label>
                <Select v-model="form.max_resolution">
                    <SelectTrigger id="max_resolution">
                        <SelectValue placeholder="Select resolution" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="icon">Icon</SelectItem>
                        <SelectItem value="thumbnail">Thumbnail</SelectItem>
                        <SelectItem value="high_res">High Res</SelectItem>
                        <SelectItem value="original">Original</SelectItem>
                    </SelectContent>
                </Select>

                <p
                    v-if="form.errors.max_resolution"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.max_resolution }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="sort_order">Sort Order</Label>
                <Input
                    id="sort_order"
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                />
                <p v-if="form.errors.sort_order" class="text-sm text-red-600">
                    {{ form.errors.sort_order }}
                </p>
            </div>
        </div>

        <div class="space-y-2">
            <Label for="description">Description</Label>
            <Textarea
                id="description"
                v-model="form.description"
                rows="3"
                placeholder="Short explanation shown to customers."
            />
            <p v-if="form.errors.description" class="text-sm text-red-600">
                {{ form.errors.description }}
            </p>
        </div>

        <div class="space-y-2">
            <Label for="usage_terms">Usage Terms</Label>
            <Textarea
                id="usage_terms"
                v-model="form.usage_terms"
                rows="8"
                placeholder="Full licensing terms for this option."
            />
            <p v-if="form.errors.usage_terms" class="text-sm text-red-600">
                {{ form.errors.usage_terms }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <Checkbox id="is_active" v-model:checked="form.is_active" />
            <Label for="is_active">Active</Label>
        </div>

        <div class="flex justify-end gap-2">
            <Button variant="outline" as-child>
                <Link href="/admin/license-types">
                    Cancel
                </Link>
            </Button>

            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>