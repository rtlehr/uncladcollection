<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';

import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        assetId: number;
        isFavorited: boolean;
        isLoggedIn: boolean;
        variant?: 'full' | 'compact' | 'icon';
    }>(),
    {
        variant: 'full',
    },
);

function favorite() {
    if (!props.isLoggedIn) {
        router.visit('/login');

        return;
    }

    router.post(
        `/images/${props.assetId}/favorite`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function unfavorite() {
    router.delete(`/images/${props.assetId}/favorite`, {
        preserveScroll: true,
    });
}

function toggleFavorite() {
    if (props.isFavorited) {
        unfavorite();

        return;
    }

    favorite();
}
</script>

<template>
    <Button
        type="button"
        variant="outline"
        :size="variant === 'icon' ? 'icon' : 'default'"
        :class="variant === 'full' ? 'gap-2' : ''"
        :aria-label="isFavorited ? 'Remove from favorites' : 'Add to favorites'"
        @click="toggleFavorite"
    >
        <Heart
            :class="[
                variant === 'compact' ? 'h-4 w-4' : 'h-4 w-4',
                isFavorited ? 'fill-current' : '',
            ]"
        />

        <span v-if="variant === 'full'">
            {{ isFavorited ? 'Favorited' : 'Favorite' }}
        </span>
    </Button>
</template>
