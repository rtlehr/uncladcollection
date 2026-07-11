<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();
</script>

<template>
    <Breadcrumb aria-label="Breadcrumb">
        <BreadcrumbList>
            <template
                v-for="(item, index) in breadcrumbs"
                :key="`${item.title}-${index}`"
            >
                <BreadcrumbItem>
                    <BreadcrumbPage
                        v-if="index === breadcrumbs.length - 1"
                        aria-current="page"
                    >
                        {{ item.title }}
                    </BreadcrumbPage>

                    <BreadcrumbLink v-else as-child>
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </BreadcrumbLink>
                </BreadcrumbItem>

                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    aria-hidden="true"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
