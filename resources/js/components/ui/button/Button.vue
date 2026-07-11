<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import type { ButtonVariants } from '.';
import { Loader2Icon } from '@lucide/vue';
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '.';

interface Props extends PrimitiveProps {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    class?: HTMLAttributes['class'];
    loading?: boolean;
    loadingLabel?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
    loading: false,
    loadingLabel: 'Working...',
    disabled: false,
});
</script>

<template>
    <Primitive
        data-slot="button"
        :data-variant="variant"
        :data-size="size"
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
        :disabled="disabled || loading"
        :aria-disabled="disabled || loading || undefined"
        :aria-busy="loading || undefined"
    >
        <Loader2Icon v-if="loading" class="size-4 animate-spin" aria-hidden="true" />
        <span v-if="loading && loadingLabel">{{ loadingLabel }}</span>
        <slot v-else />
    </Primitive>
</template>
