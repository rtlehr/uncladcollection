<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';

type Option = {
    id: number;
    name: string;
};

type LicenseType = {
    id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
};

type ImageRecord = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    original_url: string | null;
    high_res_url: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    photographer: string | null;
    is_ai_generated: boolean;
    is_favorited: boolean;
    is_purchased: boolean;
    can_purchase: boolean;
    can_download: boolean;
    favorites_count: number;
    downloads_count: number;
    purchases_count: number;
    views_count: number;
    collection: Option | null;
    categories: Option[];
    tags: Option[];
    created_at: string | null;
};

type ImageCard = {
    id: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    is_ai_generated: boolean;
    favorites_count: number;
    views_count: number;
};

const props = defineProps<{
    imageRecord: ImageRecord;
    relatedImages: ImageCard[];
    licenseTypes: LicenseType[];
}>();

const page = usePage();

const selectedLicenseTypeId = ref<number | null>(
    props.licenseTypes.length ? props.licenseTypes[0].id : null
);

const isLoggedIn = computed(() => Boolean((page.props as any).auth?.user));

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}

function formatPrice(priceCents: number): string {
    return `$${(priceCents / 100).toFixed(2)}`;
}

function favoriteImage() {
    if (!isLoggedIn.value) {
        router.visit('/login');
        return;
    }

    router.post(`/images/${props.imageRecord.id}/favorite`, {}, {
        preserveScroll: true,
    });
}

function unfavoriteImage() {
    router.delete(`/images/${props.imageRecord.id}/favorite`, {
        preserveScroll: true,
    });
}

function purchaseImage() {
    if (!isLoggedIn.value) {
        window.location.href = '/login';
        return;
    }

    if (!selectedLicenseTypeId.value) {
        return;
    }

    const form = document.createElement('form');

    form.method = 'POST';
    form.action = `/checkout/${props.imageRecord.id}`;

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (csrfToken) {
        const csrfInput = document.createElement('input');

        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;

        form.appendChild(csrfInput);
    }

    const licenseInput = document.createElement('input');

    licenseInput.type = 'hidden';
    licenseInput.name = 'license_type_id';
    licenseInput.value = String(selectedLicenseTypeId.value);

    form.appendChild(licenseInput);

    document.body.appendChild(form);
    form.submit();
}

function addToCart() {
    if (!isLoggedIn.value) {
        router.visit('/login');
        return;
    }

    if (!selectedLicenseTypeId.value) {
        return;
    }

    router.post('/cart/items', {
        image_id: props.imageRecord.id,
        license_type_id: selectedLicenseTypeId.value,
    }, {
        preserveScroll: true,
    });
}

</script>

<template>
    <Head :title="imageRecord.title" />

    <div class="space-y-8 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <Link
                    href="/images"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    ← Back to Images
                </Link>

                <h1 class="mt-2 text-3xl font-semibold">
                    {{ imageRecord.title }}
                </h1>

                <p
                    v-if="imageRecord.collection"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ imageRecord.collection.name }}
                </p>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <Button
                    v-if="!imageRecord.is_favorited"
                    variant="outline"
                    class="gap-2"
                    @click="favoriteImage"
                >
                    <Heart class="h-4 w-4" />
                    Favorite
                </Button>

                <Button
                    v-else
                    variant="outline"
                    class="gap-2"
                    @click="unfavoriteImage"
                >
                    <Heart class="h-4 w-4 fill-current" />
                    Favorited
                </Button>

                <Button v-if="imageRecord.can_download" as-child>
                    <a :href="`/images/${imageRecord.id}/download`">
                        Download
                    </a>
                </Button>

                <Button
                    v-else-if="imageRecord.is_purchased"
                    disabled
                    variant="secondary"
                >
                    Purchased
                </Button>

                <div
                    v-else-if="imageRecord.can_purchase"
                    class="flex flex-wrap items-center gap-2"
                >
                    <select
                        v-model.number="selectedLicenseTypeId"
                        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option
                            v-for="licenseType in licenseTypes"
                            :key="licenseType.id"
                            :value="licenseType.id"
                        >
                            {{ licenseType.name }} -
                            {{ formatPrice(licenseType.price_cents) }}
                        </option>
                    </select>

                   <div class="flex gap-2">
                        <Button
                            :disabled="!selectedLicenseTypeId"
                            @click="purchaseImage"
                        >
                            Buy Now
                        </Button>

                        <Button
                            variant="outline"
                            :disabled="!selectedLicenseTypeId"
                            @click="addToCart"
                        >
                            Add to Cart
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
                <div
                    v-if="imageRecord.thumbnail_url || imageRecord.high_res_url"
                    class="rounded-lg border bg-muted p-4"
                >
                    <img
                        :src="imageRecord.thumbnail_url || imageRecord.high_res_url || ''"
                        :alt="imageRecord.title"
                        class="max-h-[700px] w-full rounded object-contain"
                    />
                </div>

                <div
                    v-else
                    class="flex h-96 items-center justify-center rounded-lg border text-muted-foreground"
                >
                    No preview available.
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Image Details</h2>

                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Photographer
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.photographer || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                AI Generated
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.is_ai_generated ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Added
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.created_at || '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Stats</h2>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-md border p-3">
                            <div class="text-xs text-muted-foreground">Views</div>
                            <div class="text-xl font-semibold">
                                {{ formatNumber(imageRecord.views_count) }}
                            </div>
                        </div>

                        <div class="rounded-md border p-3">
                            <div class="text-xs text-muted-foreground">Favorites</div>
                            <div class="text-xl font-semibold">
                                {{ formatNumber(imageRecord.favorites_count) }}
                            </div>
                        </div>

                        <div class="rounded-md border p-3">
                            <div class="text-xs text-muted-foreground">Downloads</div>
                            <div class="text-xl font-semibold">
                                {{ formatNumber(imageRecord.downloads_count) }}
                            </div>
                        </div>

                        <div class="rounded-md border p-3">
                            <div class="text-xs text-muted-foreground">Purchases</div>
                            <div class="text-xl font-semibold">
                                {{ formatNumber(imageRecord.purchases_count) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Categories</h2>

                    <div v-if="imageRecord.categories.length" class="flex flex-wrap gap-2">
                        <span
                            v-for="category in imageRecord.categories"
                            :key="category.id"
                            class="rounded-full border px-3 py-1 text-sm"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No categories assigned.
                    </p>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Tags</h2>

                    <div v-if="imageRecord.tags.length" class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in imageRecord.tags"
                            :key="tag.id"
                            class="rounded-full border px-3 py-1 text-sm"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No tags assigned.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="imageRecord.description"
            class="rounded-lg border bg-card p-6 shadow-sm"
        >
            <h2 class="mb-4 text-lg font-semibold">Description</h2>

            <div class="whitespace-pre-line text-sm leading-7">
                {{ imageRecord.description }}
            </div>
        </div>

        <div v-if="relatedImages.length" class="space-y-4">
            <h2 class="text-xl font-semibold">Related Images</h2>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="image in relatedImages"
                    :key="image.id"
                    :href="`/images/${image.slug}`"
                    class="group overflow-hidden rounded-lg border bg-card shadow-sm transition hover:shadow-md"
                >
                    <div class="aspect-square bg-muted">
                        <img
                            v-if="image.thumbnail_url"
                            :src="image.thumbnail_url"
                            :alt="image.title"
                            class="h-full w-full object-cover transition group-hover:scale-105"
                        />

                        <div
                            v-else
                            class="flex h-full items-center justify-center text-sm text-muted-foreground"
                        >
                            No preview
                        </div>
                    </div>

                    <div class="space-y-2 p-4">
                        <h3 class="line-clamp-1 font-semibold">
                            {{ image.title }}
                        </h3>

                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>{{ formatNumber(image.views_count) }} views</span>
                            <span>{{ formatNumber(image.favorites_count) }} favorites</span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>