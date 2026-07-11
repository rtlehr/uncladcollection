<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        viewHref?: string | null;
        editHref?: string | null;
        viewLabel?: string;
        editLabel?: string;
        deleteLabel?: string;
        showDelete?: boolean;
        deleteDisabled?: boolean;
        compact?: boolean;
    }>(),
    {
        viewHref: null,
        editHref: null,
        viewLabel: 'View',
        editLabel: 'Edit',
        deleteLabel: 'Delete',
        showDelete: true,
        deleteDisabled: false,
        compact: false,
    },
);

const emit = defineEmits<{
    delete: [];
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button
            v-if="viewHref"
            :size="compact ? 'sm' : 'default'"
            variant="outline"
            as-child
        >
            <Link :href="viewHref">
                {{ viewLabel }}
            </Link>
        </Button>

        <Button
            v-if="editHref"
            :size="compact ? 'sm' : 'default'"
            variant="outline"
            as-child
        >
            <Link :href="editHref">
                {{ editLabel }}
            </Link>
        </Button>

        <slot />

        <Button
            v-if="showDelete"
            :size="compact ? 'sm' : 'default'"
            variant="destructive"
            :disabled="deleteDisabled"
            @click="emit('delete')"
        >
            {{ deleteLabel }}
        </Button>
    </div>
</template>
