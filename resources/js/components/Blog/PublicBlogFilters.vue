<script setup lang="ts">
import { X } from '@lucide/vue';

import type {
    Category,
    Tag,
} from '@/types/blog';

defineProps<{
    categories: Category[];
    tags: Tag[];
    categoryId: string;
    tagId: string;
}>();

const emit = defineEmits<{
    'update:categoryId': [value: string];
    'update:tagId': [value: string];
    apply: [];
    reset: [];
}>();
</script>

<template>
    <div class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto grid max-w-[1440px] gap-3 px-5 py-4 sm:px-8 md:grid-cols-[1fr_1fr_auto_auto] lg:px-12">
            <select
                :value="categoryId"
                class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                aria-label="Filter articles by category"
                @change="emit('update:categoryId', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">
                    All Categories
                </option>

                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="String(category.id)"
                >
                    {{ category.name }}
                </option>
            </select>

            <select
                :value="tagId"
                class="h-11 rounded-full border border-stone-300 bg-transparent px-4 text-sm dark:border-stone-700"
                aria-label="Filter articles by tag"
                @change="emit('update:tagId', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">
                    All Tags
                </option>

                <option
                    v-for="tag in tags"
                    :key="tag.id"
                    :value="String(tag.id)"
                >
                    {{ tag.name }}
                </option>
            </select>

            <button
                type="button"
                class="inline-flex h-11 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                @click="emit('apply')"
            >
                Apply Filters
            </button>

            <button
                type="button"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-full border border-stone-300 px-5 text-sm font-semibold dark:border-stone-700"
                @click="emit('reset')"
            >
                <X class="h-4 w-4" />
                Reset
            </button>
        </div>
    </div>
</template>
