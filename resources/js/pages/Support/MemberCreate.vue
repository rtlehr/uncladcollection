<script setup lang="ts">
import PublicSupportCreatePage from '@/components/Support/PublicSupportCreatePage.vue';
import type { SupportPageContent } from '@/types/publicPages';

defineProps<{
    mode: 'member';
    supportPage: SupportPageContent;
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
</script>

<template>
    <PublicSupportCreatePage
        :mode="mode"
        :support-page="supportPage"
        :categories="categories"
        :initial-category-id="initialCategoryId"
        :attachment-rules="attachmentRules"
    />
</template>
