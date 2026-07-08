<script setup lang="ts">
import UserAvatar from '@/Components/Shared/UserAvatar.vue';

defineProps<{
    authorName: string;
    authorAvatar?: string | null;
    avatarAlt: string;
    publishedDate: string;
    readingTime: string;
    viewsCount: number;
}>();
</script>

<template>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-sm text-muted-foreground">
        <div class="flex items-center gap-3">
            <UserAvatar
                :src="authorAvatar"
                :alt="avatarAlt"
            />

            <div class="text-left">
                <div class="font-medium text-foreground">
                    {{ authorName }}
                </div>

                <div>
                    {{ publishedDate }}
                </div>
            </div>
        </div>

        <span class="hidden sm:inline">•</span>

        <span>{{ readingTime }}</span>

        <span class="hidden sm:inline">•</span>

        <span>{{ viewsCount }} views</span>
    </div>
</template>