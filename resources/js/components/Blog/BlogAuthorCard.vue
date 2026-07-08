<script setup lang="ts">
import SidebarCard from '@/Components/Shared/SidebarCard.vue';
import type { Author } from '@/types/blog';

defineProps<{
    author: Author | null;
}>();
</script>

<template>
    <SidebarCard title="About the Author">
        <div>
            <div class="text-lg font-bold">
                {{ author?.name ?? 'Unclad Collection' }}
            </div>

            <div
                v-if="author?.author_title"
                class="mt-1 text-sm font-medium text-muted-foreground"
            >
                {{ author.author_title }}
            </div>

            <p class="mt-3 text-sm leading-6 text-muted-foreground">
                {{
                    author?.author_bio
                        ?? 'Contributor to Unclad Collection, sharing articles, photography, and resources for the nudist and naturist community.'
                }}
            </p>

            <a
                v-if="author?.author_website_url"
                :href="author.author_website_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-4 inline-flex text-sm font-semibold text-primary hover:underline"
            >
                Author Website →
            </a>
        </div>
    </SidebarCard>
</template>